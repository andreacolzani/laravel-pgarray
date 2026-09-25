<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Contracts\PgArrayValue;
use AndreaColzani\PgArray\Types\Point;

/**
 * Hex EWKB point, as returned by PostgreSQL for geometry / geography elements.
 */
function ewkbPoint(float $x, float $y, ?int $srid = 4326, bool $littleEndian = true): string
{
    $type = $srid === null ? 1 : 0x20000001;

    $wkb = $littleEndian
        ? "\x01".pack('V', $type).($srid === null ? '' : pack('V', $srid)).pack('e', $x).pack('e', $y)
        : "\x00".pack('N', $type).($srid === null ? '' : pack('N', $srid)).pack('E', $x).pack('E', $y);

    return strtoupper(bin2hex($wkb));
}

it('implements PgArrayValue', function (): void {
    expect(Point::class)->toImplement(PgArrayValue::class);
});

it('defaults to WGS 84', function (): void {
    $point = new Point(latitude: 45.4642, longitude: 9.19);

    expect($point->latitude)->toBe(45.4642)
        ->and($point->longitude)->toBe(9.19)
        ->and($point->srid)->toBe(4326);
});

it('serializes to EWKT with longitude first', function (): void {
    expect((new Point(45.4642, 9.19))->toPgArrayValue())
        ->toBe('SRID=4326;POINT(9.19 45.4642)');
});

it('serializes to WKT without SRID', function (): void {
    expect((new Point(2, -1.5, 0))->toPgArrayValue())
        ->toBe('POINT(-1.5 2)');
});

it('parses the EWKB returned by PostGIS', function (): void {
    // SELECT ST_SetSRID(ST_MakePoint(1, 2), 4326)
    expect(Point::fromPgArrayValue('0101000020E6100000000000000000F03F0000000000000040'))
        ->toEqual(new Point(latitude: 2, longitude: 1, srid: 4326));
});

it('parses EWKB points', function (string $ewkb, Point $expected): void {
    expect(Point::fromPgArrayValue($ewkb))->toEqual($expected);
})->with([
    'little endian' => [ewkbPoint(9.19, 45.4642), new Point(45.4642, 9.19)],
    'big endian' => [ewkbPoint(9.19, 45.4642, littleEndian: false), new Point(45.4642, 9.19)],
    'without srid' => [ewkbPoint(-73.9857, 40.7484, null), new Point(40.7484, -73.9857, 0)],
    'projected srid' => [ewkbPoint(514000.5, 5034000.25, 32632), new Point(5034000.25, 514000.5, 32632)],
    'lowercase hex' => [strtolower(ewkbPoint(1, 2)), new Point(2, 1)],
]);

it('parses WKT and EWKT points', function (string $wkt, Point $expected): void {
    expect(Point::fromPgArrayValue($wkt))->toEqual($expected);
})->with([
    'ewkt' => ['SRID=4326;POINT(9.19 45.4642)', new Point(45.4642, 9.19)],
    'wkt' => ['POINT(9.19 45.4642)', new Point(45.4642, 9.19, 0)],
    'spaces and case' => [' point ( -9.19   -45.4642 ) ', new Point(-45.4642, -9.19, 0)],
    'exponent' => ['POINT(1e-7 2.5E+1)', new Point(25, 1.0E-7, 0)],
]);

it('round trips through EWKT', function (): void {
    $point = new Point(45.46420001, 9.18999999, 3857);

    expect(Point::fromPgArrayValue($point->toPgArrayValue()))->toEqual($point);
});

it('serializes to GeoJSON', function (): void {
    expect(json_encode(new Point(45.5, 9.25)))
        ->toBe('{"type":"Point","coordinates":[9.25,45.5]}');
});

it('rejects unsupported EWKB geometries', function (string $ewkb): void {
    Point::fromPgArrayValue($ewkb);
})->with([
    // SELECT ST_MakePoint(1, 2, 3)
    'point z' => ['0101000080000000000000F03F00000000000000400000000000000840'],
    // SELECT 'POINTM(1 2 3)'::geometry
    'point m' => ['0101000040000000000000F03F00000000000000400000000000000840'],
    // SELECT 'LINESTRING(0 0,1 1)'::geometry
    'linestring' => ['010200000002000000000000000000000000000000000000000000000000000000F03F000000000000F03F'],
])->throws(UnexpectedValueException::class, 'Only 2D EWKB points are supported.');

it('rejects invalid EWKB', function (string $ewkb, string $message): void {
    expect(fn () => Point::fromPgArrayValue($ewkb))
        ->toThrow(UnexpectedValueException::class, $message);
})->with([
    'truncated' => [substr(ewkbPoint(1, 2), 0, -2), 'Invalid EWKB point length.'],
    'trailing bytes' => [ewkbPoint(1, 2).'00', 'Invalid EWKB point length.'],
    'byte order' => ['02'.substr(ewkbPoint(1, 2), 2), 'Invalid EWKB byte order.'],
    // SELECT 'POINT EMPTY'::geometry
    'empty point' => ['0101000000000000000000F87F000000000000F87F', 'Point coordinates must be finite numbers.'],
]);

it('rejects invalid point values', function (mixed $value): void {
    Point::fromPgArrayValue($value);
})->with([
    'empty' => [''],
    'polygon wkt' => ['POLYGON((0 0,1 0,1 1,0 0))'],
    'point z wkt' => ['POINT(1 2 3)'],
    'odd hex' => ['010'],
    'integer' => [1],
    'array' => [[1, 2]],
])->throws(UnexpectedValueException::class);

it('rejects non finite coordinates', function (): void {
    new Point(NAN, 1);
})->throws(UnexpectedValueException::class, 'Point coordinates must be finite numbers.');

it('rejects negative SRIDs', function (): void {
    new Point(1, 1, -1);
})->throws(UnexpectedValueException::class, 'Invalid SRID [-1].');
