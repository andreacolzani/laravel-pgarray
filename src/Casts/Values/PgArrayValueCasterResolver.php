<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Contracts\PgArrayJsonSerializer;
use AndreaColzani\PgArray\Contracts\PgArrayJsonValue;
use AndreaColzani\PgArray\Contracts\PgArrayValue;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Support\PgArraySerializerRegistry;
use BackedEnum;
use Illuminate\Container\Container;
use UnitEnum;

/**
 * Resolution layer between PgArray and individual value casters.
 *
 *   Resolver
 *    ├── PgArrayValueCaster → used as is (casters configured by castables)
 *    ├── PgArrayCast  →  PgArrayValueCasterFactory
 *    └── class-string →  JsonSerializerCaster / SerializerCaster (external serializer)
 *                        / JsonObjectCaster (PgArrayJsonValue) / ObjectCaster (PgArrayValue)
 *                        / EnumCaster (BackedEnum)
 *
 * Built-in PgArrayCast values are delegated to PgArrayValueCasterFactory,
 * keeping that factory focused on built-in casters. Class-strings are
 * resolved in order of precedence:
 *
 *   1. an external serializer found by PgArraySerializerRegistry (configured
 *      or declared on the class): PgArrayJsonSerializer implementations
 *      resolve to JsonSerializerCaster, the others to SerializerCaster
 *   2. PgArrayJsonValue implementations resolve to JsonObjectCaster, other
 *      PgArrayValue implementations to ObjectCaster
 *   3. BackedEnum class-strings resolve to EnumCaster; pure (UnitEnum) enums
 *      have no storable representation and are rejected explicitly
 *
 * Explicit configuration wins over class-level defaults, so serializers can
 * override PgArrayValue implementations and backed enums without modifying them.
 *
 * Encrypted definitions wrap the resolved caster in EncryptedCaster, so any
 * element type can be encrypted element by element.
 */
final class PgArrayValueCasterResolver
{
    public static function resolve(PgArrayElementDefinition $definition): PgArrayValueCaster
    {
        $type = $definition->type;

        $caster = match (true) {
            $type instanceof PgArrayValueCaster => $type,
            $type instanceof PgArrayCast => PgArrayValueCasterFactory::make($type),
            default => self::resolveClassString($type),
        };

        return $definition->encrypted
            ? new EncryptedCaster($caster)
            : $caster;
    }

    /**
     * @throws UnsupportedElementException
     */
    private static function resolveClassString(string $type): PgArrayValueCaster
    {
        if (! class_exists($type)) {
            throw UnsupportedElementException::unknownClass($type);
        }

        $serializer = Container::getInstance()
            ->make(PgArraySerializerRegistry::class)
            ->resolve($type);

        if ($serializer instanceof PgArrayJsonSerializer) {
            return new JsonSerializerCaster($type, $serializer);
        }

        if ($serializer !== null) {
            return new SerializerCaster($type, $serializer);
        }

        if (is_subclass_of($type, PgArrayJsonValue::class)) {
            return new JsonObjectCaster($type);
        }

        if (is_subclass_of($type, PgArrayValue::class)) {
            return new ObjectCaster($type);
        }

        if (is_subclass_of($type, BackedEnum::class)) {
            return new EnumCaster($type);
        }

        if (is_subclass_of($type, UnitEnum::class)) {
            throw UnsupportedElementException::pureEnum($type);
        }

        throw UnsupportedElementException::unsupportedClass($type);
    }
}
