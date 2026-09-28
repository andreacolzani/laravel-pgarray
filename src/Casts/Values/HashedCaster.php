<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use Illuminate\Support\Facades\Hash;
use SensitiveParameter;
use Stringable;

/**
 * Mirrors Laravel's hashed cast element by element: existing hashes are
 * stored unchanged, so hashes read from the database can be assigned again.
 *
 * @internal
 */
final class HashedCaster implements PgArrayValueCaster
{
    public function get(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    public function set(#[SensitiveParameter] mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! is_int($value) && ! is_float($value) && ! $value instanceof Stringable) {
            throw new InvalidValueException(sprintf(
                'Unable to hash [%s].',
                get_debug_type($value),
            ));
        }

        $value = (string) $value;

        if (! Hash::isHashed($value)) {
            return Hash::make($value);
        }

        // Same check as Laravel's hashed cast; HashManager documents the hash as an array.
        /** @phpstan-ignore argument.type */
        if (! Hash::verifyConfiguration($value)) {
            throw new InvalidValueException("Could not verify the hashed value's configuration.");
        }

        return $value;
    }
}
