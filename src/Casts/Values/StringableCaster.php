<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use Illuminate\Support\Stringable;

/**
 * @internal
 */
final class StringableCaster implements PgArrayValueCaster
{
    public function get(mixed $value): ?Stringable
    {
        return $value === null ? null : new Stringable($value);
    }

    public function set(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
