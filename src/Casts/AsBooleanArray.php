<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class AsBooleanArray extends PgArrayCastable
{
    /**
     * @param  array{0?: value-of<PgArrayContainer>}  $arguments
     * @return CastsAttributes<list<mixed>, list<bool>>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new PgArray(
            type: PgArrayCast::Boolean,
            container: PgArrayContainer::from(
                $arguments[0] ?? PgArrayContainer::Array->value,
            ),
        );
    }
}
