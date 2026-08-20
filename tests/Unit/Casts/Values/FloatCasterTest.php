<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\FloatCaster;

it('casts values to floats', function (mixed $value, ?float $expected): void {
    expect((new FloatCaster)->get($value))->toBe($expected)
        ->and((new FloatCaster)->set($value))->toBe($expected);
})->with([
    ['123.45', 123.45],
    [123, 123.0],
    [123.45, 123.45],
    [true, 1.0],
    [false, 0.0],
    [null, null],
]);
