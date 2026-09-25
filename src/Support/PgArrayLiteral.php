<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Support;

use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Stringable;

/**
 * Builds PostgreSQL array literals (e.g. {a,b}) from PHP values, as used by
 * pgArray() column defaults and query builder operators.
 *
 * @internal
 */
final class PgArrayLiteral
{
    /**
     * Arrays and Arrayable values (Collections) are serialized as arrays, any
     * other value as a one-element array. The delimiter is the one of the
     * element type (':' for PostGIS geometry / geography).
     */
    public static function from(mixed $values, string $delimiter = PgArrayParser::DEFAULT_DELIMITER): string
    {
        /** @var array<int, string|int|float|bool|null|array<int, mixed>> $normalized */
        $normalized = self::normalize($values);

        return PgArrayParser::serialize($normalized, $delimiter);
    }

    /**
     * @return list<mixed>
     */
    public static function normalize(mixed $values): array
    {
        if ($values === null) {
            throw new InvalidValueException('A PostgreSQL array value cannot be null.');
        }

        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        if (! is_array($values)) {
            $values = [$values];
        }

        return array_map(static fn (mixed $element): mixed => match (true) {
            is_array($element), $element instanceof Arrayable => self::normalize($element),
            $element instanceof BackedEnum => $element->value,
            // With the offset, like DateTimeCaster, so timestamptz compares the right instant.
            $element instanceof DateTimeInterface => $element->format('Y-m-d H:i:s.uP'),
            $element instanceof Stringable => (string) $element,
            $element === null, is_string($element), is_int($element), is_float($element), is_bool($element) => $element,
            default => throw new InvalidValueException(sprintf(
                'Unsupported PostgreSQL array element of type [%s].',
                get_debug_type($element),
            )),
        }, array_values($values));
    }
}
