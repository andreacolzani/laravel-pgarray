<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\StringCaster;

it('casts values to strings', function (mixed $value, ?string $expected): void {
    expect((new StringCaster)->get($value))->toBe($expected)
        ->and((new StringCaster)->set($value))->toBe($expected);
})->with([
    ['foo', 'foo'],
    [123, '123'],
    [true, '1'],
    [null, null],
]);
