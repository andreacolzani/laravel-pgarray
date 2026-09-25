<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\MacAddrCaster;
use Illuminate\Support\Stringable;

it('normalizes the PostgreSQL macaddr input formats', function (string $value): void {
    expect((new MacAddrCaster)->set($value))->toBe('08:00:2b:01:02:03');
})->with([
    '08:00:2b:01:02:03',
    '08-00-2b-01-02-03',
    '08002b:010203',
    '08002b-010203',
    '0800.2b01.0203',
    '0800-2b01-0203',
    '08002b010203',
    '08:00:2B:01:02:03',
    ' 08002B010203 ',
]);

it('accepts stringable values', function (): void {
    expect((new MacAddrCaster)->set(new Stringable('08002b010203')))->toBe('08:00:2b:01:02:03');
});

it('retrieves macaddr values as strings', function (): void {
    expect((new MacAddrCaster)->get('08:00:2b:01:02:03'))->toBe('08:00:2b:01:02:03');
});

it('preserves null', function (): void {
    $caster = new MacAddrCaster;

    expect($caster->get(null))->toBeNull()
        ->and($caster->set(null))->toBeNull();
});

it('rejects invalid macaddr values', function (string $value): void {
    (new MacAddrCaster)->set($value);
})->with([
    'too short' => ['08:00:2b:01:02'],
    'too long' => ['08:00:2b:01:02:03:04'],
    'macaddr8' => ['08:00:2b:01:02:03:04:05'],
    'invalid characters' => ['08:00:2b:01:02:zz'],
    'mixed separators' => ['08:00-2b:01:02:03'],
    'empty' => [''],
])->throws(UnexpectedValueException::class, 'Invalid macaddr value');

it('rejects values that are not strings', function (mixed $value): void {
    (new MacAddrCaster)->set($value);
})->with([
    'integer' => [1],
    'array' => [[1]],
    'object' => [new stdClass],
])->throws(UnexpectedValueException::class, 'to macaddr.');
