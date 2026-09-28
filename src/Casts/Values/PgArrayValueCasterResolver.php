<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Contracts\PgArrayDelimited;
use AndreaColzani\PgArray\Contracts\PgArrayJsonSerializer;
use AndreaColzani\PgArray\Contracts\PgArrayJsonValue;
use AndreaColzani\PgArray\Contracts\PgArrayValue;
use AndreaColzani\PgArray\Contracts\PgArrayValueSerializer;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Exceptions\UnsupportedElementException;
use AndreaColzani\PgArray\Support\PgArrayParser;
use AndreaColzani\PgArray\Support\PgArraySerializerRegistry;
use BackedEnum;
use Illuminate\Container\Container;
use UnitEnum;

/**
 * Resolves the caster of an element definition. Class-strings are resolved in
 * order of precedence: external serializer, PgArrayJsonValue, PgArrayValue,
 * BackedEnum. Encrypted definitions wrap the caster in EncryptedCaster.
 *
 * The delimiter is the one declared (PgArrayDelimited) by the caster, or by
 * the serializer then the class of a class-string; encrypted elements live in
 * text[] columns and always use ','.
 *
 * @internal
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

    public static function delimiter(PgArrayElementDefinition $definition, PgArrayValueCaster $caster): string
    {
        $type = $definition->type;

        $delimiter = match (true) {
            $definition->encrypted => null,
            is_string($type) && class_exists($type) => self::declaredDelimiter(self::serializer($type)) ?? self::declaredDelimiter($type),
            default => self::declaredDelimiter($caster),
        };

        if ($delimiter === null) {
            return PgArrayParser::DEFAULT_DELIMITER;
        }

        PgArrayParser::ensureDelimiter($delimiter);

        return $delimiter;
    }

    /**
     * @param  object|class-string|null  $declarer
     */
    private static function declaredDelimiter(object|string|null $declarer): ?string
    {
        return $declarer !== null && is_subclass_of($declarer, PgArrayDelimited::class)
            ? $declarer::pgArrayDelimiter()
            : null;
    }

    /**
     * @param  class-string  $type
     */
    private static function serializer(string $type): ?PgArrayValueSerializer
    {
        return Container::getInstance()
            ->make(PgArraySerializerRegistry::class)
            ->resolve($type);
    }

    /**
     * @throws UnsupportedElementException
     */
    private static function resolveClassString(string $type): PgArrayValueCaster
    {
        if (! class_exists($type)) {
            throw UnsupportedElementException::unknownClass($type);
        }

        $serializer = self::serializer($type);

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
