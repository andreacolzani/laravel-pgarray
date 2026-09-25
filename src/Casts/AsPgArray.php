<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Casts\Values\UnsupportedElementException;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use Illuminate\Contracts\Database\Eloquent\Castable;
use InvalidArgumentException;

final class AsPgArray implements Castable
{
    private const ENCRYPTED = 'encrypted';

    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array{0?: value-of<PgArrayCast>|class-string, 1?: value-of<PgArrayContainer>, 2?: 'encrypted'}  $arguments
     */
    public static function castUsing(array $arguments): PgArray
    {
        $container = PgArrayContainer::from(
            $arguments[1] ?? PgArrayContainer::Array->value,
        );

        return new PgArray(
            type: self::resolveType($arguments[0] ?? PgArrayCast::String->value),
            container: $container,
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
     * Encrypt each element individually (see EncryptedCaster).
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
     * Built-in cast values take precedence over class-strings. Anything else
     * is rejected by PgArrayCast::from() with a ValueError.
     */
    private static function resolveType(string $type): PgArrayCast|string
    {
        return PgArrayCast::tryFrom($type)
            ?? (class_exists($type) ? $type : PgArrayCast::from($type));
    }

    private static function resolveEncrypted(?string $modifier): bool
    {
        if ($modifier === null) {
            return false;
        }

        if ($modifier !== self::ENCRYPTED) {
            throw new InvalidArgumentException(
                "Unsupported element modifier [{$modifier}]. Expected [".self::ENCRYPTED.'].',
            );
        }

        return true;
    }
}
