<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use Illuminate\Support\Facades\Hash;
use RuntimeException;
use SensitiveParameter;
use Stringable;
use UnexpectedValueException;

/**
 * Hashes individual array elements.
 *
 *   DB  → hash string (unchanged)
 *   PHP → Hash::make() (hashes are kept as they are)
 *
 * Hashing is one-way: retrieved elements are always hash strings, so values
 * are verified through PgArrayHash::check() / PgArrayHash::find().
 *
 * Mirrors Laravel's hashed cast element by element: values that are already
 * hashed are stored unchanged, so hashes read from the database can be
 * assigned again without being hashed twice; they must match the configured
 * hashing algorithm. NULL elements are not hashed.
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
            throw new UnexpectedValueException(sprintf(
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
            throw new RuntimeException("Could not verify the hashed value's configuration.");
        }

        return $value;
    }
}
