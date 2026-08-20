<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\DoubleCaster;

it('casts values to double precision', function (mixed $value, ?float $expected): void {
    expect((new DoubleCaster)->get($value))->toBe($expected)
        ->and((new DoubleCaster)->set($value))->toBe($expected);
})->with([
    ['123.45', 123.45],
    [123, 123.0],
    [123.45, 123.45],
    [true, 1.0],
    [false, 0.0],
    [null, null],
]);
