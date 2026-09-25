<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

use AndreaColzani\PgArray\Contracts\PgArrayDelimited;
use AndreaColzani\PgArray\Contracts\PgArrayValueSerializer;
use InvalidArgumentException;

/**
 * Writes shapes as EWKT and reads them back through PostGIS output (hex
 * EWKB is kept as the "WKT" of the shape, no GIS parsing here). PostGIS
 * arrays are delimited by ':'.
 */
final class ShapeSerializer implements PgArrayDelimited, PgArrayValueSerializer
{
    public static function pgArrayDelimiter(): string
    {
        return ':';
    }

    public function serialize(object $value): string
    {
        if (! $value instanceof Shape) {
            throw new InvalidArgumentException('Expected a Shape instance.');
        }

        return "SRID={$value->srid};{$value->wkt}";
    }

    public function deserialize(mixed $value, string $class): Shape
    {
        $value = (string) $value;

        if (preg_match('/^SRID=(\d+);(.+)$/', $value, $matches) === 1) {
            return new Shape($matches[2], (int) $matches[1]);
        }

        return new Shape($value);
    }
}
