<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Types;

use AndreaColzani\PgArray\Contracts\PgArrayDelimited;
use AndreaColzani\PgArray\Contracts\PgArrayValue;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use JsonSerializable;

/**
 * Immutable 2D spatial point for PostGIS geometry[] / geography[] columns.
 *
 *   AsPgArray::of(Point::class)
 *
 *   PHP → EWKT (SRID=4326;POINT(longitude latitude))
 *   DB  → hex EWKB, as returned by PostgreSQL → Point
 *
 * Assigned strings may also be WKT or EWKT points. Points without an SRID
 * (WKT, or EWKB without the SRID flag) get SRID 0, as in PostGIS.
 *
 * Richer geometries can be mapped through external serializers, or kept as
 * strings with AsGeometryArray / AsGeographyArray.
 */
final class Point implements JsonSerializable, PgArrayDelimited, PgArrayValue
{
    private const WKB_POINT = 1;

    private const EWKB_Z = 0x80000000;

    private const EWKB_M = 0x40000000;

    private const EWKB_SRID = 0x20000000;

    private const NUMBER = '[-+]?(?:\d+\.?\d*|\.\d+)(?:[eE][-+]?\d+)?';

    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $srid = 4326,
    ) {
        if (! is_finite($latitude) || ! is_finite($longitude)) {
            throw new InvalidValueException('Point coordinates must be finite numbers.');
        }

        if ($srid < 0) {
            throw new InvalidValueException("Invalid SRID [{$srid}].");
        }
    }

    /**
     * PostGIS separates the elements of geometry[] / geography[] with ':'.
     */
    public static function pgArrayDelimiter(): string
    {
        return ':';
    }

    /**
     * Extended Well-Known Text: SRID=4326;POINT(longitude latitude).
     */
    public function toPgArrayValue(): string
    {
        $wkt = 'POINT('.self::coordinate($this->longitude).' '.self::coordinate($this->latitude).')';

        return $this->srid === 0
            ? $wkt
            : "SRID={$this->srid};{$wkt}";
    }

    public static function fromPgArrayValue(mixed $value): static
    {
        if (! is_string($value)) {
            throw new InvalidValueException(sprintf(
                'Unable to create a point from [%s].',
                get_debug_type($value),
            ));
        }

        $value = trim($value);

        if (preg_match('/^(?:SRID=(\d+);)?\s*POINT\s*\(\s*('.self::NUMBER.')\s+('.self::NUMBER.')\s*\)$/i', $value, $matches) === 1) {
            return new self(
                latitude: (float) $matches[3],
                longitude: (float) $matches[2],
                srid: (int) $matches[1],
            );
        }

        if ($value !== '' && strlen($value) % 2 === 0 && ctype_xdigit($value)) {
            return self::fromEwkb((string) hex2bin($value));
        }

        throw new InvalidValueException("Invalid point value [{$value}].");
    }

    /**
     * GeoJSON representation: coordinates are [longitude, latitude].
     *
     * @return array{type: 'Point', coordinates: array{float, float}}
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => 'Point',
            'coordinates' => [$this->longitude, $this->latitude],
        ];
    }

    /**
     * The shortest representation that round trips: (string) only keeps 14
     * significant digits.
     */
    private static function coordinate(float $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private static function fromEwkb(string $wkb): self
    {
        $littleEndian = match ($wkb[0]) {
            "\x01" => true,
            "\x00" => false,
            default => throw new InvalidValueException('Invalid EWKB byte order.'),
        };

        $offset = 1;
        $type = self::readUnsignedInt($wkb, $offset, $littleEndian);

        if (($type & (self::EWKB_Z | self::EWKB_M)) !== 0 || ($type & 0x0FFFFFFF) !== self::WKB_POINT) {
            throw new InvalidValueException('Only 2D EWKB points are supported.');
        }

        $srid = ($type & self::EWKB_SRID) !== 0
            ? self::readUnsignedInt($wkb, $offset, $littleEndian)
            : 0;

        $longitude = self::readDouble($wkb, $offset, $littleEndian);
        $latitude = self::readDouble($wkb, $offset, $littleEndian);

        if ($offset !== strlen($wkb)) {
            throw self::invalidLength();
        }

        return new self($latitude, $longitude, $srid);
    }

    private static function readUnsignedInt(string $wkb, int &$offset, bool $littleEndian): int
    {
        $value = self::read($wkb, $offset, 4, $littleEndian ? 'V' : 'N');

        return is_int($value) ? $value : throw self::invalidLength();
    }

    private static function readDouble(string $wkb, int &$offset, bool $littleEndian): float
    {
        $value = self::read($wkb, $offset, 8, $littleEndian ? 'e' : 'E');

        return is_float($value) ? $value : throw self::invalidLength();
    }

    private static function read(string $wkb, int &$offset, int $length, string $format): mixed
    {
        if (strlen($wkb) < $offset + $length) {
            throw self::invalidLength();
        }

        $value = unpack($format, $wkb, $offset);
        $offset += $length;

        return $value === false ? null : $value[1];
    }

    private static function invalidLength(): InvalidValueException
    {
        return new InvalidValueException('Invalid EWKB point length.');
    }
}
