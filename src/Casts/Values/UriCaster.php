<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use Illuminate\Support\Uri;

final class UriCaster implements PgArrayValueCaster
{
    public function get(mixed $value): ?Uri
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof Uri
            ? $value
            : new Uri((string) $value);
    }

    public function set(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
