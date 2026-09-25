<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;

/**
 * String array whose elements are encrypted individually (text[] column).
 *
 * Other element types can be encrypted through AsPgArray::encrypted().
 */
final class AsEncryptedArray extends PgArrayCastable
{
    /**
     * @param  array{0?: value-of<PgArrayContainer>}  $arguments
     */
    public static function castUsing(array $arguments): PgArray
    {
        return new PgArray(
            type: PgArrayCast::String,
            container: PgArrayContainer::from(
                $arguments[0] ?? PgArrayContainer::Array->value,
            ),
            encrypted: true,
        );
    }
}
