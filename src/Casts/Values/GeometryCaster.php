<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Contracts\PgArrayDelimited;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use AndreaColzani\PgArray\Types\Point;
use Stringable;

/**
 * Passthrough for PostGIS geometry[] / geography[] elements: reads hex EWKB,
 * writes WKT / EWKT / hex EWKB strings unchanged (or a Point as EWKT).
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
