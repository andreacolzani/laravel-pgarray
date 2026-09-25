<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

use AndreaColzani\PgArray\Contracts\PgArrayValueSerializer;
use InvalidArgumentException;

/**
 * A serializer shared by multiple string value objects (Sku, CountryCode).
 */
final class StringValueSerializer implements PgArrayValueSerializer
{
    public function serialize(object $value): string
    {
        if (! $value instanceof StringValue) {
            throw new InvalidArgumentException('Expected a string value object.');
        }

        return $value->value();
    }

    public function deserialize(mixed $value, string $class): object
    {
        if (! is_subclass_of($class, StringValue::class)) {
            throw new InvalidArgumentException("Unsupported class [{$class}].");
        }

        return $class::of((string) $value);
    }
}
