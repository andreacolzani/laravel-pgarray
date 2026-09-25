<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;

/**
 * Array whose elements are hashed individually (text[] column).
 *
 * Retrieved elements are hash strings; verify values with PgArrayHash.
 */
final class AsHashedArray extends PgArrayCastable
{
    /**
     * @param  array{0?: value-of<PgArrayContainer>}  $arguments
     */
    public static function castUsing(array $arguments): PgArray
    {
        return new PgArray(
            type: PgArrayCast::Hashed,
            container: PgArrayContainer::from(
                $arguments[0] ?? PgArrayContainer::Array->value,
            ),
        );
    }
}
