<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Enums\PgArrayCast;

/**
 * The element type of a cast: a built-in PgArrayCast, a class-string or an
 * already configured caster (e.g. a VectorCaster with dimensions).
 *
 * @internal
 */
final class PgArrayElementDefinition
{
    public function __construct(
        public readonly PgArrayCast|PgArrayValueCaster|string $type,
        public readonly bool $encrypted = false,
    ) {}
}
