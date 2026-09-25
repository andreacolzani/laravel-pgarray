<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Contracts\PgArrayDelimited;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use AndreaColzani\PgArray\Types\Point;
use Stringable;

/**
 * Passthrough caster for PostGIS geometry[] / geography[] elements.
 *
 *   DB  → string, as returned by PostgreSQL (hex EWKB)
 *   PHP → WKT / EWKT / hex EWKB string (unchanged) or Point (EWKT)
 *
 * No GIS parsing happens here: use AsPgArray::of(Point::class) to retrieve
 * points as objects, or an external serializer for richer geometries.
 *
 * PostGIS separates the elements of geometry[] / geography[] with ':'.
 *
 * @internal
 */
final class GeometryCaster implements PgArrayDelimited, PgArrayValueCaster
{
    public static function pgArrayDelimiter(): string
    {
        return ':';
    }

    public function get(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    public function set(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof Point => $value->toPgArrayValue(),
            is_string($value), $value instanceof Stringable => (string) $value,
            default => throw new InvalidValueException(sprintf(
                'Unable to cast [%s] to a geometry.',
                get_debug_type($value),
            )),
        };
    }
}
