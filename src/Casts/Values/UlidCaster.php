<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use Stringable;
use Symfony\Component\Uid\Ulid;

/**
 * ULIDs have no PostgreSQL type: they are validated on set(), since char(26)[]
 * and text[] columns accept any string.
 *
 * @internal
 */
final class UlidCaster implements PgArrayValueCaster
{
    public function get(mixed $value): ?Ulid
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof Ulid
            ? $value
            : new Ulid((string) $value);
    }

    public function set(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof Ulid => (string) $value,
            (is_string($value) || $value instanceof Stringable) && Ulid::isValid((string) $value) => (string) new Ulid((string) $value),
            default => throw new InvalidValueException(sprintf(
                'Unable to cast [%s] to a ULID.',
                is_string($value) ? $value : get_debug_type($value),
            )),
        };
    }
}
