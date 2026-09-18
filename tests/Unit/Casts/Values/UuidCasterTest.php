<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\UuidCaster;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

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
