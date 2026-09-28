<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\UlidCaster;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use Symfony\Component\Uid\Ulid;

it('casts values to Ulid instances', function (mixed $value, ?string $expected): void {
    $result = (new UlidCaster)->get($value);

    if ($expected === null) {
        expect($result)->toBeNull();

        return;
    }

    expect($result)
        ->toBeInstanceOf(Ulid::class)
        ->and((string) $result)
        ->toBe($expected);
})->with([
    ['01H455P6D1K3YFZJ9A8E0S7N5X', '01H455P6D1K3YFZJ9A8E0S7N5X'],
    [null, null],
]);

it('accepts an existing Ulid instance', function (): void {
    $caster = new UlidCaster;
    $ulid = new Ulid('01H455P6D1K3YFZJ9A8E0S7N5X');

    expect($caster->get($ulid))
        ->toBeInstanceOf(Ulid::class)
        ->and((string) $caster->get($ulid))
        ->toBe('01H455P6D1K3YFZJ9A8E0S7N5X');
});

it('serializes Ulid values to strings', function (): void {
    $caster = new UlidCaster;
    $ulid = new Ulid('01H455P6D1K3YFZJ9A8E0S7N5X');

    expect($caster->set($ulid))
        ->toBe('01H455P6D1K3YFZJ9A8E0S7N5X')
        ->and($caster->set('01H455P6D1K3YFZJ9A8E0S7N5X'))
        ->toBe('01H455P6D1K3YFZJ9A8E0S7N5X')
        ->and($caster->set(null))
        ->toBeNull();
});

it('normalizes assigned ULID strings to uppercase', function (): void {
    expect((new UlidCaster)->set('01h455p6d1k3yfzj9a8e0s7n5x'))
        ->toBe('01H455P6D1K3YFZJ9A8E0S7N5X');
});

it('rejects invalid ULIDs', function (mixed $value): void {
    (new UlidCaster)->set($value);
})->with([
    'invalid string' => ['not-a-ulid'],
    'uuid string' => ['550e8400-e29b-41d4-a716-446655440000'],
    'integer' => [42],
])->throws(InvalidValueException::class, 'to a ULID');
