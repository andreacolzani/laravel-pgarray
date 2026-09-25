<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\GeometryCaster;
use AndreaColzani\PgArray\Types\Point;
use Illuminate\Support\Stringable;

it('retrieves elements unchanged', function (): void {
    expect((new GeometryCaster)->get('0101000020E6100000000000000000F03F0000000000000040'))
        ->toBe('0101000020E6100000000000000000F03F0000000000000040');
});

it('passes strings through unchanged', function (mixed $value, string $expected): void {
    expect((new GeometryCaster)->set($value))->toBe($expected);
})->with([
    'wkt' => ['POLYGON((0 0,1 0,1 1,0 0))', 'POLYGON((0 0,1 0,1 1,0 0))'],
    'ewkt' => ['SRID=4326;LINESTRING(9 45,10 46)', 'SRID=4326;LINESTRING(9 45,10 46)'],
    'ewkb' => ['0101000020E6100000000000000000F03F0000000000000040', '0101000020E6100000000000000000F03F0000000000000040'],
    'stringable' => [new Stringable('POINT(1 2)'), 'POINT(1 2)'],
]);

it('serializes points to EWKT', function (): void {
    expect((new GeometryCaster)->set(new Point(45.5, 9.25)))
        ->toBe('SRID=4326;POINT(9.25 45.5)');
});

it('preserves null', function (): void {
    $caster = new GeometryCaster;

    expect($caster->get(null))->toBeNull()
        ->and($caster->set(null))->toBeNull();
});

it('rejects values that are not geometries', function (mixed $value): void {
    (new GeometryCaster)->set($value);
})->with([
    'integer' => [1],
    'object' => [new stdClass],
])->throws(UnexpectedValueException::class, 'to a geometry.');
