<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Exceptions\InvalidValueException;

/**
 * @internal
 */
final class BooleanCaster implements PgArrayValueCaster
{
    public function get(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return match (strtolower((string) $value)) {
            't', 'true', '1', 'yes', 'on' => true,
            'f', 'false', '0', 'no', 'off' => false,
            default => throw new InvalidValueException(
                "Unable to cast [{$value}] to boolean.",
            ),
        };
    }

    public function set(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }
}
