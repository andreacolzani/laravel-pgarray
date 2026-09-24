<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Contracts\PgArrayJsonValue;
use AndreaColzani\PgArray\Contracts\PgArrayValue;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use BackedEnum;
use UnitEnum;

/**
 * Resolution layer between PgArray and individual value casters.
 *
 *   Resolver
 *    ├── PgArrayCast  →  PgArrayValueCasterFactory
 *    └── class-string →  JsonObjectCaster (PgArrayJsonValue) / ObjectCaster (PgArrayValue)
 *                        / EnumCaster (BackedEnum)
 *
 * Built-in PgArrayCast values are delegated to PgArrayValueCasterFactory,
 * keeping that factory focused on built-in casters. PgArrayJsonValue
 * implementations resolve to JsonObjectCaster, other PgArrayValue
 * implementations resolve to ObjectCaster. Both take precedence over the
 * automatic BackedEnum support, since implementing the contract is an explicit
 * choice. BackedEnum class-strings resolve to EnumCaster; pure (UnitEnum)
 * enums have no storable representation and are rejected explicitly.
 */
final class PgArrayValueCasterResolver
{
    public static function resolve(PgArrayElementDefinition $definition): PgArrayValueCaster
    {
        $type = $definition->type;

        if ($type instanceof PgArrayCast) {
            return PgArrayValueCasterFactory::make($type);
        }

        return self::resolveClassString($type);
    }

    /**
     * @throws UnsupportedElementException
     */
    private static function resolveClassString(string $type): PgArrayValueCaster
    {
        if (! class_exists($type)) {
            throw UnsupportedElementException::unknownClass($type);
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
