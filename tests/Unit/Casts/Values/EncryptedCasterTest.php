<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\BooleanCaster;
use AndreaColzani\PgArray\Casts\Values\EncryptedCaster;
use AndreaColzani\PgArray\Casts\Values\EnumCaster;
use AndreaColzani\PgArray\Casts\Values\IntegerCaster;
use AndreaColzani\PgArray\Casts\Values\JsonObjectCaster;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCaster;
use AndreaColzani\PgArray\Casts\Values\StringCaster;
use AndreaColzani\PgArray\Tests\Fixtures\Address;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;

afterEach(function (): void {
    Model::encryptUsing(null);
});

it('encrypts string elements', function (): void {
    $encrypted = (new EncryptedCaster(new StringCaster))->set('secret');

    expect($encrypted)
        ->toBeString()
        ->not->toBe('secret')
        ->and(Crypt::decryptString($encrypted))->toBe('secret');
});

it('decrypts string elements', function (): void {
    expect((new EncryptedCaster(new StringCaster))->get(Crypt::encryptString('secret')))
        ->toBe('secret');
});

it('uses a fresh initialization vector for every element', function (): void {
    $caster = new EncryptedCaster(new StringCaster);

    expect($caster->set('secret'))->not->toBe($caster->set('secret'));
});

it('round-trips values through the element caster', function (PgArrayValueCaster $caster, mixed $value): void {
    $encrypted = new EncryptedCaster($caster);

    expect($encrypted->get($encrypted->set($value)))->toEqual($value);
})->with([
    'string' => [new StringCaster, 'secret'],
    'empty string' => [new StringCaster, ''],
    'NULL string' => [new StringCaster, 'NULL'],
    'special characters' => [new StringCaster, "a,b {c} \"d\" \\e\nf"],
    'integer' => [new IntegerCaster, 42],
    'true' => [new BooleanCaster, true],
    'false' => [new BooleanCaster, false],
    'backed enum' => [new EnumCaster(Status::class), Status::Active],
    'JSON object' => [new JsonObjectCaster(Address::class), new Address('Via Roma 1', 'Milano')],
]);

it('encrypts the PostgreSQL text representation of logical values', function (): void {
    $caster = new EncryptedCaster(new BooleanCaster);

    expect(Crypt::decryptString($caster->set(true)))->toBe('t')
        ->and(Crypt::decryptString($caster->set(false)))->toBe('f')
        ->and(Crypt::decryptString((new EncryptedCaster(new IntegerCaster))->set(42)))->toBe('42')
        ->and(Crypt::decryptString((new EncryptedCaster(new EnumCaster(Status::class)))->set(Status::Active)))->toBe('active');
});

it('validates values through the element caster before encrypting', function (): void {
    (new EncryptedCaster(new EnumCaster(Status::class)))->set('unknown');
})->throws(UnexpectedValueException::class);

it('preserves null without encrypting it', function (): void {
    $caster = new EncryptedCaster(new StringCaster);

    expect($caster->get(null))->toBeNull()
        ->and($caster->set(null))->toBeNull();
});

it('preserves null logical values returned by the element caster', function (): void {
    $caster = new EncryptedCaster(new JsonObjectCaster(Address::class));

    expect($caster->set('null'))->toBeNull();
});

it('rejects values that are not encrypted', function (): void {
    (new EncryptedCaster(new StringCaster))->get('secret');
})->throws(DecryptException::class);

it('rejects non-scalar logical values', function (): void {
    $caster = new class implements PgArrayValueCaster
    {
        public function get(mixed $value): mixed
        {
            return $value;
        }

        public function set(mixed $value): mixed
        {
            return ['nested'];
        }
    };

    (new EncryptedCaster($caster))->set('secret');
})->throws(
    UnexpectedValueException::class,
    'Unable to encrypt [array]: element casters must return a scalar value or null.',
);

it('uses the encrypter configured for eloquent models', function (): void {
    $encrypter = new Encrypter(Encrypter::generateKey('aes-256-cbc'), 'aes-256-cbc');

    Model::encryptUsing($encrypter);

    $caster = new EncryptedCaster(new StringCaster);
    $encrypted = $caster->set('secret');

    expect($encrypter->decryptString($encrypted))->toBe('secret')
        ->and($caster->get($encrypted))->toBe('secret')
        ->and(fn () => Crypt::decryptString($encrypted))->toThrow(DecryptException::class);
});

it('decrypts elements encrypted with a previous key', function (): void {
    $previous = new Encrypter(Encrypter::generateKey('aes-256-cbc'), 'aes-256-cbc');
    $current = (new Encrypter(Encrypter::generateKey('aes-256-cbc'), 'aes-256-cbc'))
        ->previousKeys([$previous->getKey()]);

    Model::encryptUsing($current);

    expect((new EncryptedCaster(new StringCaster))->get($previous->encryptString('secret')))
        ->toBe('secret');
});
