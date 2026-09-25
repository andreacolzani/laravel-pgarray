<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Enums\PgArrayCast;

/**
 * Internal value object representing the element definition of a PostgreSQL array.
 *
 * This object encapsulates whether the element type is a built-in PgArrayCast
 * enum, a user-provided class string or an already configured caster (e.g. a
 * VectorCaster with dimensions), and whether each element is encrypted.
 * It is used by PgArrayValueCasterResolver to route resolution to the
 * appropriate caster.
 */
final class PgArrayElementDefinition
{
    public function __construct(
        public readonly PgArrayCast|PgArrayValueCaster|string $type,
        public readonly bool $encrypted = false,
    ) {}
}
