<?php

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Enums\PgArrayType;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class AsPgArray implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array{ 0?: value-of<PgArrayType> }  $arguments
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        $type = isset($arguments[0])
            ? PgArrayType::from($arguments[0])
            : PgArrayType::String;

        return new PgArray(
            type: $type,
        );
    }

    public static function of(PgArrayType $type): string
    {
        return self::class.':'.$type->value;
    }
}
