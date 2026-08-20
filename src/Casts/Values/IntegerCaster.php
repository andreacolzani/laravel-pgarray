<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

final class IntegerCaster implements PgArrayValueCaster
{
    public function get(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function set(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
