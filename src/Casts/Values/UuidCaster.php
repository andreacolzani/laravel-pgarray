<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @internal
 */
final class UuidCaster implements PgArrayValueCaster
{
    public function get(mixed $value): ?UuidInterface
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof UuidInterface
            ? $value
            : Uuid::fromString((string) $value);
    }

    public function set(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof UuidInterface
            ? $value->toString()
            : (string) $value;
    }
}
