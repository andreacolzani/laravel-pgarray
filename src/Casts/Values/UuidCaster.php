<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Stringable;

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
        return match (true) {
            $value === null => null,
            $value instanceof UuidInterface => $value->toString(),
            (is_string($value) || $value instanceof Stringable) && Uuid::isValid((string) $value) => Uuid::fromString((string) $value)->toString(),
            default => throw new InvalidValueException(sprintf(
                'Unable to cast [%s] to a UUID.',
                is_string($value) ? $value : get_debug_type($value),
            )),
        };
    }
}
