<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use Symfony\Component\Uid\Ulid;

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
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
