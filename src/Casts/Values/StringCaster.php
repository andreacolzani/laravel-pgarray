<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

/**
 * @internal
 */
final class StringCaster implements PgArrayValueCaster
{
    public function get(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    public function set(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
