<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Exceptions\InvalidDefinitionException;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use AndreaColzani\PgArray\Types\Vector;
use Stringable;

/**
 * Casts pgvector array elements (vector[] columns) to and from Vector.
 *
 *   DB  → [1,2,3] → Vector
 *   PHP → Vector or [1,2,3] string → [1,2,3]
 *
 * Plain list<float> elements cannot be assigned: PHP arrays are nested
 * dimensions of the PostgreSQL array, so vectors must be Vector instances.
 *
 * When dimensions are configured, every element must have exactly that many
 * dimensions, both when setting and when retrieving.
 *
 * @internal
 */
final class VectorCaster implements PgArrayValueCaster
{
    public function __construct(
        private readonly ?int $dimensions = null,
    ) {
        if ($dimensions !== null && $dimensions < 1) {
            throw new InvalidDefinitionException(
                "Vector dimensions must be greater than zero, [{$dimensions}] given.",
            );
        }
    }

    public function get(mixed $value): ?Vector
    {
        if ($value === null) {
            return null;
        }

        return $this->validate($this->toVector($value));
    }

    public function set(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $this->validate($this->toVector($value));
    }

    private function toVector(mixed $value): Vector
    {
        return match (true) {
            $value instanceof Vector => $value,
            is_string($value), $value instanceof Stringable => Vector::fromString((string) $value),
            default => throw new InvalidValueException(sprintf(
                'Unable to cast [%s] to [%s].',
                get_debug_type($value),
                Vector::class,
            )),
        };
    }

    private function validate(Vector $vector): Vector
    {
        if ($this->dimensions !== null && $vector->dimensions() !== $this->dimensions) {
            throw new InvalidValueException(sprintf(
                'Expected a vector with %d dimensions, %d given.',
                $this->dimensions,
                $vector->dimensions(),
            ));
        }

        return $vector;
    }
}
