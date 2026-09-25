<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Types;

use Countable;
use JsonSerializable;
use Stringable;
use UnexpectedValueException;

/**
 * Immutable pgvector value ([1,2,3]) used as element of vector[] columns.
 *
 * Vectors are objects rather than plain list<float> arrays because PHP arrays
 * are treated as nested dimensions of the PostgreSQL array.
 *
 * pgvector stores single-precision floats, so values read from the database
 * may differ slightly from the double-precision values assigned.
 */
final class Vector implements Countable, JsonSerializable, Stringable
{
    /** @var non-empty-list<float> */
    private readonly array $values;

    /**
     * @param  array<array-key, int|float>  $values
     */
    public function __construct(array $values)
    {
        if ($values === []) {
            throw new UnexpectedValueException('A vector must have at least one dimension.');
        }

        $this->values = array_map(self::component(...), array_values($values));
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if (! str_starts_with($value, '[') || ! str_ends_with($value, ']')) {
            throw new UnexpectedValueException("Invalid vector value [{$value}].");
        }

        $components = array_map(
            static function (string $component) use ($value): float {
                $component = trim($component);

                if (! is_numeric($component)) {
                    throw new UnexpectedValueException("Invalid vector value [{$value}].");
                }

                return (float) $component;
            },
            explode(',', substr($value, 1, -1)),
        );

        return new self($components);
    }

    /**
     * @return list<float>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * @return int<1, max>
     */
    public function dimensions(): int
    {
        return count($this->values);
    }

    /**
     * @return int<1, max>
     */
    public function count(): int
    {
        return $this->dimensions();
    }

    /**
     * @return list<float>
     */
    public function jsonSerialize(): array
    {
        return $this->values;
    }

    /**
     * The pgvector text representation, e.g. [1,2.5,-3].
     */
    public function __toString(): string
    {
        return '['.implode(',', array_map(
            static fn (float $value): string => (string) $value,
            $this->values,
        )).']';
    }

    private static function component(mixed $value): float
    {
        if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value)) {
            throw new UnexpectedValueException(sprintf(
                'Vector components must be finite numbers, [%s] given.',
                is_float($value) ? var_export($value, true) : get_debug_type($value),
            ));
        }

        return (float) $value;
    }
}
