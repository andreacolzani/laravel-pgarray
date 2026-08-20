<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

final class FloatCaster implements PgArrayValueCaster
{
    public function get(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    public function set(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
