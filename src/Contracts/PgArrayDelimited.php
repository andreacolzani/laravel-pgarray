<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Contracts;

/**
 * Declares the array delimiter of the PostgreSQL element type (pg_type.typdelim).
 *
 * Most types use ',' ({a,b}), which is the default. PostGIS geometry and
 * geography use ':' ({0101…:0101…}): PostgreSQL rejects a comma-separated
 * literal with more than one element, and returns elements separated by ':'.
 *
 * It can be implemented by value casters (PgArrayValueCaster), PgArrayValue
 * classes and external serializers (PgArrayValueSerializer). A serializer's
 * delimiter takes precedence over the one of the class it serializes.
 * Encrypted elements are stored in text[] columns, so they always use ','.
 *
 *   GeometryCaster, Point → ':'
 */
interface PgArrayDelimited
{
    public static function pgArrayDelimiter(): string;
}
