<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\IntegerCaster;

it('casts values to integers', function (mixed $value, ?int $expected): void {
    expect((new IntegerCaster)->get($value))->toBe($expected)
        ->and((new IntegerCaster)->set($value))->toBe($expected);
})->with([
    ['123', 123],
    [123, 123],
    [123.9, 123],
    [true, 1],
    [null, null],
]);
