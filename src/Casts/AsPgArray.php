<?php

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class AsPgArray implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array{0?: value-of<PgArrayCast>, 1?: value-of<PgArrayContainer>}  $arguments
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        $type = PgArrayCast::from(
            $arguments[0] ?? PgArrayCast::String->value,
        );

        $container = PgArrayContainer::from(
            $arguments[1] ?? PgArrayContainer::Array->value,
        );

        return new PgArray(
            type: $type,
            container: $container,
        );
    }

    public static function of(
        PgArrayCast $type,
        PgArrayContainer $container = PgArrayContainer::Array,
    ): string {
        return self::class.':'.$type->value.','.$container->value;
    }
}
