<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\DecimalCaster;

it('preserves decimal values as strings', function (
    mixed $value,
    ?string $expected,
): void {
    expect((new DecimalCaster)->get($value))->toBe($expected)
        ->and((new DecimalCaster)->set($value))->toBe($expected);
})->with([
    ['123.45', '123.45'],
    ['123456789.123456789', '123456789.123456789'],
    [123, '123'],
    [123.45, '123.45'],
    [null, null],
]);
