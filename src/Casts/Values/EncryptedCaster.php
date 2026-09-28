<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use AndreaColzani\PgArray\Support\PgArrayParser;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;

/**
 * Encrypts each element around another caster, keeping the array structure
 * and NULL elements visible (unlike Laravel's encrypted:array cast).
 *
 * The logical value is encrypted in its PostgreSQL text form, so the same
 * caster can parse it after decryption. Model::currentEncrypter() supports
 * Model::encryptUsing() and previous keys.
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
