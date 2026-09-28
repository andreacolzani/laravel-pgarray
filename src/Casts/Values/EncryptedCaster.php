<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use AndreaColzani\PgArray\Support\PgArrayParser;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;

/**
 * Encrypts and decrypts individual array elements around another caster.
 *
 *   DB  → decrypt → element caster get()
 *   PHP → element caster set() → encrypt
 *
 * Each element is encrypted separately, so the PostgreSQL array keeps its
 * structure (dimensions, length and NULL elements) and only element values
 * are hidden. This differs from Laravel's encrypted:array cast, which
 * encrypts the whole array as a single JSON payload.
 *
 * The element caster's logical value is encrypted in its PostgreSQL text
 * representation (booleans as t / f, like PgArrayParser::serialize()), so it
 * can be parsed again by the same caster after decryption. Ciphertexts are
 * strings, so encrypted arrays require a text[] (or varchar[]) column.
 *
 * Encryption uses the encrypter configured for Eloquent models
 * (Model::encryptUsing()), falling back to the application encrypter, so
 * custom encrypters and previous keys work as with Laravel's encrypted casts.
 * NULL elements are not encrypted.
 *
 * @internal
 */
final class EncryptedCaster implements PgArrayValueCaster
{
    public function __construct(
        private readonly PgArrayValueCaster $caster,
    ) {}

    public function get(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return $this->caster->get(
            $this->encrypter()->decrypt($value, false),
        );
    }

    public function set(mixed $value): ?string
    {
        $value = $this->caster->set($value);

        if ($value === null) {
            return null;
        }

        return $this->encrypter()->encrypt(
            $this->toText($value),
            false,
        );
    }

    private function toText(mixed $value): string
    {
        return match (true) {
            is_scalar($value) => PgArrayParser::toText($value),
            default => throw new InvalidValueException(sprintf(
                'Unable to encrypt [%s]: element casters must return a scalar value or null.',
                get_debug_type($value),
            )),
        };
    }

    private function encrypter(): Encrypter
    {
        return Model::currentEncrypter();
    }
}
