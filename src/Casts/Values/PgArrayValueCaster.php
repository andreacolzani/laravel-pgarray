<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

/**
 * Converts a single array element between PHP and PostgreSQL.
 *
 * Custom element types are supported through PgArrayValue, PgArrayJsonValue
 * or external serializers (PgArrayValueSerializer), not through this interface.
 *
 * @internal
 */
interface PgArrayValueCaster
{
    /**
     * Convert an element read from the database (its PostgreSQL text
     * representation, or null) into its PHP value.
     */
    public function get(mixed $value): mixed;

    /**
     * Convert a PHP element into its logical value (scalar or null), which
     * PgArrayParser serializes into the PostgreSQL array literal.
     */
    public function set(mixed $value): mixed;
}
