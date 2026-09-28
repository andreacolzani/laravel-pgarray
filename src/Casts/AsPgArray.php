<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use AndreaColzani\PgArray\Exceptions\InvalidDefinitionException;
use AndreaColzani\PgArray\Exceptions\UnsupportedElementException;
use Illuminate\Contracts\Database\Eloquent\Castable;

/**
 * Generic PostgreSQL array cast, for any element type.
 *
 *   'scores'   => AsPgArray::of(PgArrayCast::Integer),
 *   'statuses' => AsPgArray::of(Status::class, PgArrayContainer::Collection),
 *   'secrets'  => AsPgArray::encrypted(PgArrayCast::Date),
 *
 * Element types are PgArrayCast values or classes: PgArrayValue /
 * PgArrayJsonValue implementations, backed enums, or classes mapped to an
 * external serializer. Without arguments, elements are strings.
 */
final class AsPgArray implements Castable
{
    private const ENCRYPTED = 'encrypted';

    /**
     * @param  array{0?: value-of<PgArrayCast>|class-string, 1?: value-of<PgArrayContainer>, 2?: 'encrypted'}  $arguments
     */
    public static function castUsing(array $arguments): PgArray
    {
        return new PgArray(
            type: self::resolveType($arguments[0] ?? PgArrayCast::String->value),
            container: PgArrayContainer::fromCastArgument($arguments[1] ?? null),
            encrypted: self::resolveEncrypted($arguments[2] ?? null),
        );
    }

    /**
     * @param  PgArrayCast|class-string  $type
     *
     * @throws UnsupportedElementException
     */
    public static function of(
        PgArrayCast|string $type,
        PgArrayContainer $container = PgArrayContainer::Array,
    ): string {
        return self::class.':'.self::definition($type).','.$container->value;
    }

    /**
     * Encrypt each element individually: the column must be text[].
     *
     * @param  PgArrayCast|class-string  $type
     *
     * @throws UnsupportedElementException
     */
    public static function encrypted(
        PgArrayCast|string $type,
        PgArrayContainer $container = PgArrayContainer::Array,
    ): string {
        return self::of($type, $container).','.self::ENCRYPTED;
    }

    /**
     * @param  PgArrayCast|class-string  $type
     *
     * @throws UnsupportedElementException
     */
    private static function definition(PgArrayCast|string $type): string
    {
        if (is_string($type) && ! class_exists($type)) {
            throw UnsupportedElementException::unknownClass($type);
        }

        return $type instanceof PgArrayCast
            ? $type->value
            : $type;
    }

    /**
     * Built-in cast values take precedence over class-strings.
     *
     * @throws UnsupportedElementException
     */
    private static function resolveType(string $type): PgArrayCast|string
    {
        return PgArrayCast::tryFrom($type)
            ?? (class_exists($type) ? $type : throw UnsupportedElementException::unknownClass($type));
    }

    private static function resolveEncrypted(?string $modifier): bool
    {
        if ($modifier === null) {
            return false;
        }

        if ($modifier !== self::ENCRYPTED) {
            throw new InvalidDefinitionException(
                "Unsupported element modifier [{$modifier}]. Expected [".self::ENCRYPTED.'].',
            );
        }

        return true;
    }
}
