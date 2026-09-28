<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\UuidCaster;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

it('casts values to Uuid instances', function (mixed $value, ?string $expected): void {
    $result = (new UuidCaster)->get($value);

    if ($expected === null) {
        expect($result)->toBeNull();

        return;
    }

    expect($result)
        ->toBeInstanceOf(UuidInterface::class)
        ->and((string) $result)
        ->toBe($expected);
})->with([
    ['550e8400-e29b-41d4-a716-446655440000', '550e8400-e29b-41d4-a716-446655440000'],
    [null, null],
]);

it('accepts an existing Uuid instance', function (): void {
    $caster = new UuidCaster;
    $uuid = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');

    expect($caster->get($uuid))
        ->toBeInstanceOf(UuidInterface::class)
        ->and((string) $caster->get($uuid))
        ->toBe('550e8400-e29b-41d4-a716-446655440000');
});

it('serializes Uuid values to strings', function (): void {
    $caster = new UuidCaster;
    $uuid = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');

    expect($caster->set($uuid))
        ->toBe('550e8400-e29b-41d4-a716-446655440000')
        ->and($caster->set('550e8400-e29b-41d4-a716-446655440000'))
        ->toBe('550e8400-e29b-41d4-a716-446655440000')
        ->and($caster->set(null))
        ->toBeNull();
});

it('normalizes assigned UUID strings', function (mixed $value): void {
    expect((new UuidCaster)->set($value))
        ->toBe('550e8400-e29b-41d4-a716-446655440000');
})->with([
    'uppercase' => ['550E8400-E29B-41D4-A716-446655440000'],
    'braces' => ['{550e8400-e29b-41d4-a716-446655440000}'],
    'symfony uuid' => [SymfonyUuid::fromString('550e8400-e29b-41d4-a716-446655440000')],
]);

it('rejects invalid UUIDs', function (mixed $value): void {
    (new UuidCaster)->set($value);
})->with([
    'invalid string' => ['not-a-uuid'],
    'integer' => [42],
])->throws(InvalidValueException::class, 'to a UUID');
