<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use Stringable;
use UnexpectedValueException;

/**
 * Casts inet array elements (IPv4 / IPv6 host address with optional prefix).
 *
 *   DB  → string
 *   PHP → validated and normalized string
 *
 * Normalization matches the PostgreSQL output: IPv6 addresses are written in
 * compressed lowercase form and full-length prefixes (/32, /128) are omitted.
 */
final class InetCaster implements PgArrayValueCaster
{
    public function get(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    public function set(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! $value instanceof Stringable) {
            throw new UnexpectedValueException(sprintf(
                'Unable to cast [%s] to inet.',
                get_debug_type($value),
            ));
        }

        return $this->normalize(trim((string) $value));
    }

    private function normalize(string $value): string
    {
        $separator = strpos($value, '/');

        $address = $separator === false ? $value : substr($value, 0, $separator);
        $prefix = $separator === false ? null : substr($value, $separator + 1);

        $binary = filter_var($address, FILTER_VALIDATE_IP) !== false
            ? inet_pton($address)
            : false;

        if ($binary === false) {
            throw $this->invalid($value);
        }

        $address = (string) inet_ntop($binary);
        $bits = strlen($binary) * 8;

        if ($prefix === null) {
            return $address;
        }

        if (! ctype_digit($prefix) || (int) $prefix > $bits) {
            throw $this->invalid($value);
        }

        return (int) $prefix === $bits
            ? $address
            : $address.'/'.(int) $prefix;
    }

    private function invalid(string $value): UnexpectedValueException
    {
        return new UnexpectedValueException("Invalid inet value [{$value}].");
    }
}
