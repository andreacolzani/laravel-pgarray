<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\RealCaster;

it('casts values to reals', function (mixed $value, ?float $expected): void {
    expect((new RealCaster)->get($value))->toBe($expected)
        ->and((new RealCaster)->set($value))->toBe($expected);
})->with([
    ['123.45', 123.45],
    [123, 123.0],
    [123.45, 123.45],
    [true, 1.0],
    [false, 0.0],
    [null, null],
]);
