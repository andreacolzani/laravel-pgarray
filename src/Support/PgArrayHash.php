<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Support;

use Illuminate\Support\Facades\Hash;
use SensitiveParameter;

/**
 * Verifies plain values against arrays of hashes (see AsHashedArray).
 *
 * Works with arrays and Collections. Only top-level string elements are
 * checked: NULL elements and nested arrays are skipped.
 */
final class PgArrayHash
{
    /**
     * Determine whether the value matches any of the hashes.
     *
     * @param  iterable<array-key, mixed>|null  $hashes
     */
    public static function check(#[SensitiveParameter] string $value, ?iterable $hashes): bool
    {
        return self::find($value, $hashes) !== null;
    }

    /**
     * Get the key of the first hash matching the value, or null.
     *
     * Useful to remove a consumed value, such as a used recovery code.
     *
     * @param  iterable<array-key, mixed>|null  $hashes
     */
    public static function find(#[SensitiveParameter] string $value, ?iterable $hashes): int|string|null
    {
        foreach ($hashes ?? [] as $key => $hash) {
            if (is_string($hash) && Hash::check($value, $hash)) {
                return $key;
            }
        }

        return null;
    }
}
