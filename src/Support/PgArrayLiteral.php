<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Support;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use Stringable;

/**
 * Builds PostgreSQL array literals (e.g. {a,b}) from PHP values, as used by
 * pgArray() column defaults and query builder operators.
 */
final class PgArrayLiteral
{
    /**
     * Arrays and Arrayable values (Collections) are serialized as arrays, any
     * other value as a one-element array.
     */
    public static function from(mixed $values): string
    {
        /** @var array<int, string|int|float|bool|null|array<int, mixed>> $normalized */
        $normalized = self::normalize($values);

        return PgArrayParser::serialize($normalized);
    }

    /**
     * @return list<mixed>
     */
    public static function normalize(mixed $values): array
    {
        if ($values === null) {
            throw new InvalidArgumentException('A PostgreSQL array value cannot be null.');
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
            default => throw new InvalidArgumentException(sprintf(
                'Unsupported PostgreSQL array element of type [%s].',
                get_debug_type($element),
            )),
        }, array_values($values));
    }
}
