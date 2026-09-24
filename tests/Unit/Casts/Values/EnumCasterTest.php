<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\EnumCaster;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;
use AndreaColzani\PgArray\Tests\Fixtures\Status;

it('casts string backing values to string-backed enum cases', function (): void {
    $caster = new EnumCaster(Status::class);

    expect($caster->get('active'))->toBe(Status::Active)
        ->and($caster->get('inactive'))->toBe(Status::Inactive);
});

it('casts postgres integer representations to int-backed enum cases', function (mixed $value, Priority $expected): void {
    expect((new EnumCaster(Priority::class))->get($value))->toBe($expected);
})->with([
    ['1', Priority::Low],
    ['3', Priority::High],
    [2, Priority::Medium],
]);

it('returns enum instances unchanged when retrieving', function (): void {
    expect((new EnumCaster(Status::class))->get(Status::Active))->toBe(Status::Active);
});

it('serializes enum cases to their backing values', function (): void {
    expect((new EnumCaster(Status::class))->set(Status::Inactive))->toBe('inactive')
        ->and((new EnumCaster(Priority::class))->set(Priority::High))->toBe(3);
});

it('serializes valid raw backing values', function (): void {
    expect((new EnumCaster(Status::class))->set('active'))->toBe('active')
        ->and((new EnumCaster(Priority::class))->set(2))->toBe(2)
        ->and((new EnumCaster(Priority::class))->set('2'))->toBe(2);
});

it('preserves null', function (string $enum): void {
    $caster = new EnumCaster($enum);

    expect($caster->get(null))->toBeNull()
        ->and($caster->set(null))->toBeNull();
})->with([Status::class, Priority::class]);

it('rejects invalid string backing values', function (mixed $value): void {
    (new EnumCaster(Status::class))->get($value);
})->with([
    'unknown case' => 'archived',
    'wrong case' => 'Active',
    'empty string' => '',
    'integer' => 1,
])->throws(UnexpectedValueException::class);

it('rejects invalid int backing values', function (mixed $value): void {
    (new EnumCaster(Priority::class))->get($value);
})->with([
    'out of range' => '4',
    'non numeric' => 'high',
    'decimal' => '1.5',
    'empty string' => '',
])->throws(UnexpectedValueException::class);

it('rejects cases of a different enum when serializing', function (): void {
    (new EnumCaster(Status::class))->set(Priority::Low);
})->throws(
    UnexpectedValueException::class,
    'Unable to cast ['.Priority::class.'] to enum ['.Status::class.'].',
);

it('rejects invalid backing values when serializing', function (): void {
    (new EnumCaster(Status::class))->set('archived');
})->throws(
    UnexpectedValueException::class,
    'Unable to cast [archived] to enum ['.Status::class.'].',
);
