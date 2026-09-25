<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\HashedCaster;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Stringable;

it('hashes elements', function (): void {
    $hash = (new HashedCaster)->set('secret');

    expect($hash)
        ->toBeString()
        ->not->toBe('secret')
        ->and(Hash::isHashed($hash))->toBeTrue()
        ->and(Hash::check('secret', $hash))->toBeTrue();
});

it('hashes numeric and stringable elements as strings', function (mixed $value, string $plain): void {
    expect(Hash::check($plain, (new HashedCaster)->set($value)))->toBeTrue();
})->with([
    'integer' => [1234, '1234'],
    'float' => [1.5, '1.5'],
    'stringable' => [new Stringable('secret'), 'secret'],
    'empty string' => ['', ''],
]);

it('uses a fresh salt for every element', function (): void {
    $caster = new HashedCaster;

    expect($caster->set('secret'))->not->toBe($caster->set('secret'));
});

it('preserves hashes when retrieving', function (): void {
    $hash = Hash::make('secret');

    expect((new HashedCaster)->get($hash))->toBe($hash);
});

it('does not hash values that are already hashed', function (): void {
    $hash = Hash::make('secret');

    expect((new HashedCaster)->set($hash))->toBe($hash);
});

it('rejects hashes that do not match the configured algorithm', function (): void {
    (new HashedCaster)->set(password_hash('secret', PASSWORD_ARGON2ID));
})->throws(RuntimeException::class, "Could not verify the hashed value's configuration.");

it('preserves null without hashing it', function (): void {
    $caster = new HashedCaster;

    expect($caster->get(null))->toBeNull()
        ->and($caster->set(null))->toBeNull();
});

it('rejects values that cannot be hashed', function (mixed $value): void {
    (new HashedCaster)->set($value);
})->with([
    'boolean' => [true],
    'object' => [new stdClass],
])->throws(UnexpectedValueException::class, 'Unable to hash [');
