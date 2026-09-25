<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use Stringable;

/**
 * Casts macaddr array elements (6-byte MAC addresses).
 *
 *   DB  → string
 *   PHP → validated string normalized to 08:00:2b:01:02:03
 *
 * Accepts the input formats supported by PostgreSQL:
 * 08:00:2b:01:02:03, 08-00-2b-01-02-03, 08002b:010203, 08002b-010203,
 * 0800.2b01.0203, 0800-2b01-0203 and 08002b010203 (case-insensitive).
 *
 * @internal
 */
final class MacAddrCaster implements PgArrayValueCaster
{
    private const FORMATS = [
        '/^[0-9a-f]{2}(:[0-9a-f]{2}){5}$/i',
        '/^[0-9a-f]{2}(-[0-9a-f]{2}){5}$/i',
        '/^[0-9a-f]{6}[:-][0-9a-f]{6}$/i',
        '/^[0-9a-f]{4}\.[0-9a-f]{4}\.[0-9a-f]{4}$/i',
        '/^[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}$/i',
        '/^[0-9a-f]{12}$/i',
    ];

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
            throw new InvalidValueException(sprintf(
                'Unable to cast [%s] to macaddr.',
                get_debug_type($value),
            ));
        }

        $value = trim((string) $value);

        foreach (self::FORMATS as $format) {
            if (preg_match($format, $value) === 1) {
                $hex = strtolower((string) preg_replace('/[^0-9a-f]/i', '', $value));

                return implode(':', str_split($hex, 2));
            }
        }

        throw new InvalidValueException("Invalid macaddr value [{$value}].");
    }
}
