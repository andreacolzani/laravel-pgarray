<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Contracts;

/**
 * Class-level contract for custom value objects stored as PostgreSQL array elements.
 *
 * Classes implementing this contract are resolved automatically when used as
 * an element type, e.g. AsPgArray::of(Email::class). It takes precedence over
 * the automatic BackedEnum support.
 *
 *   PHP → toPgArrayValue()   → logical value → PostgreSQL element
 *   DB  → PostgreSQL element → fromPgArrayValue() → PHP object
 *
 * toPgArrayValue() returns a logical PHP value, never an already-encoded
 * PostgreSQL string: escaping and quoting are handled by the package. Only
 * scalar logical values (string|int|float|bool) or null are currently
 * supported; structured values will be supported by JSON / JSONB element
 * serialization.
 *
 * fromPgArrayValue() receives the element as read from the database (the
 * PostgreSQL text representation) or, when assigning, a raw value that is
 * not yet an instance of the class. It is never called with null.
 */
interface PgArrayValue
{
    public function toPgArrayValue(): mixed;

    public static function fromPgArrayValue(mixed $value): static;
}
