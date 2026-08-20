<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\BooleanCaster;

it('casts postgres boolean representations', function (string $value, bool $expected): void {
    expect((new BooleanCaster)->get($value))->toBe($expected);
})->with([
    ['t', true],
    ['true', true],
    ['1', true],
    ['yes', true],
    ['on', true],
    ['f', false],
    ['false', false],
    ['0', false],
    ['no', false],
    ['off', false],
]);

it('casts boolean values', function (mixed $value, ?bool $expected): void {
    expect((new BooleanCaster)->set($value))->toBe($expected);
})->with([
    [true, true],
    [false, false],
    [1, true],
    [0, false],
    [null, null],
]);

it('rejects unsupported boolean representations', function (mixed $value): void {
    (new BooleanCaster)->get($value);
})->with([
    'unsupported string' => 'foo',
    'empty string' => '',
    'whitespace' => ' ',
])->throws(UnexpectedValueException::class);
