<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;

final class AsDecimalArray extends PgArrayCastable
{
    /**
     * @param  array{0?: value-of<PgArrayContainer>}  $arguments
     */
    public static function castUsing(array $arguments): PgArray
    {
        return new PgArray(
            type: PgArrayCast::Decimal,
            container: PgArrayContainer::fromCastArgument($arguments[0] ?? null),
        );
    }
}
