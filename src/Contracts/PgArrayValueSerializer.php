<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Contracts;

use AndreaColzani\PgArray\Attributes\PgArraySerializer;

/**
 * External serializer for PostgreSQL array elements.
 *
 * For classes that cannot be modified or share serialization rules: one
 * serializer can be mapped to several classes, so deserialize() receives the
 * target class.
 *
 *   PHP → serialize()        → logical value → PostgreSQL element
 *   DB  → PostgreSQL element → deserialize() → PHP object
 *
 * serialize() returns a logical PHP value, never an already-encoded
 * PostgreSQL string. Only scalar logical values (string|int|float|bool) or
 * null are supported; implement PgArrayJsonSerializer to store structured
 * values in json[] / jsonb[] columns.
 *
 * deserialize() receives the element as read from the database (the
 * PostgreSQL text representation) or, when assigning, a raw value that is not
 * yet an instance of the class. It must return an instance of $class and is
 * never called with null.
 *
 * A serializer takes precedence over PgArrayValue and BackedEnum support.
 *
 * @see PgArraySerializer
 * @see PgArraySerializable
 */
interface PgArrayValueSerializer
{
    public function serialize(object $value): mixed;

    /**
     * @param  class-string  $class
     */
    public function deserialize(mixed $value, string $class): object;
}
