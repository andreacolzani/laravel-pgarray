<?php

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Enums\PgArrayCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class AsPgArray implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array{ 0?: value-of<PgArrayCast> }  $arguments
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        $cast = isset($arguments[0])
            ? PgArrayCast::from($arguments[0])
            : PgArrayCast::String;

        return new PgArray(
            cast: $cast,
        );
    }

    public static function of(PgArrayCast $type): string
    {
        return self::class.':'.$type->value;
    }
}
