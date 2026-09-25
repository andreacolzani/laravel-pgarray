<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use Stringable;

/**
 * Casts bytea array elements to and from raw binary strings.
 *
 *   DB  → hex format (\x0a0b…) or legacy escape format → binary string
 *   PHP → binary string → hex format (\x0a0b…)
 *
 * Assigned strings are always treated as raw binary data, never as values
 * already encoded in the PostgreSQL bytea format.
 *
 * @internal
 */
final class ByteaCaster implements PgArrayValueCaster
{
    public function get(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        return str_starts_with($value, '\x')
            ? $this->decodeHex(substr($value, 2))
            : $this->decodeEscape($value);
    }

    public function set(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! $value instanceof Stringable) {
            throw new InvalidValueException(sprintf(
                'Unable to cast [%s] to bytea.',
                get_debug_type($value),
            ));
        }

        return '\x'.bin2hex((string) $value);
    }

    private function decodeHex(string $hex): string
    {
        if ($hex === '') {
            return '';
        }

        $binary = strlen($hex) % 2 === 0 && ctype_xdigit($hex)
            ? hex2bin($hex)
            : false;

        if ($binary === false) {
            throw new InvalidValueException('Invalid bytea hex value.');
        }

        return $binary;
    }

    /**
     * Legacy escape format (bytea_output = 'escape'): backslashes are doubled
     * and non-printable bytes are written as \ooo octal sequences.
     */
    private function decodeEscape(string $value): string
    {
        if (preg_match('/^(?:[^\\\\]|\\\\\\\\|\\\\[0-3][0-7]{2})*$/s', $value) !== 1) {
            throw new InvalidValueException('Invalid bytea escape value.');
        }

        // Only \\ and \ooo sequences are left: both are decoded like in C.
        return stripcslashes($value);
    }
}
