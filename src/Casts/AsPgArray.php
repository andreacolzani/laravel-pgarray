<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Casts\Values\UnsupportedElementException;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use Illuminate\Contracts\Database\Eloquent\Castable;

final class AsPgArray implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array{0?: value-of<PgArrayCast>|class-string, 1?: value-of<PgArrayContainer>}  $arguments
     */
    public static function castUsing(array $arguments): PgArray
    {
        $container = PgArrayContainer::from(
            $arguments[1] ?? PgArrayContainer::Array->value,
        );

        return new PgArray(
            type: self::resolveType($arguments[0] ?? PgArrayCast::String->value),
            container: $container,
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
        if (is_string($type) && ! class_exists($type)) {
            throw UnsupportedElementException::unknownClass($type);
        }

        $definition = $type instanceof PgArrayCast
            ? $type->value
            : $type;

        return self::class.':'.$definition.','.$container->value;
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
}
