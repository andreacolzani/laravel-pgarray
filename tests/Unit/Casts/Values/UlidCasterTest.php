<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\UlidCaster;
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
