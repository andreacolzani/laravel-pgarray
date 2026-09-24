<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Contracts\PgArrayJsonValue;
use AndreaColzani\PgArray\Contracts\PgArrayValue;
use UnexpectedValueException;

/**
 * Casts array elements to and from a PgArrayValue implementation.
 *
 *   DB  → fromPgArrayValue()
 *   PHP → toPgArrayValue() (scalar logical value)
 *
 * Structured logical values require PgArrayJsonValue (see JsonObjectCaster).
 *
 * On set(), raw values that are not yet instances of the class are normalized
 * through fromPgArrayValue() first, so the class can validate them. Objects of
 * any other class are rejected.
 */
final class ObjectCaster implements PgArrayValueCaster
{
    /**
     * @param  class-string<PgArrayValue>  $class
     */
    public function __construct(
        private readonly string $class,
    ) {}

    public function get(mixed $value): ?PgArrayValue
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof $this->class
            ? $value
            : $this->class::fromPgArrayValue($value);
    }

    public function set(mixed $value): string|int|float|bool|null
    {
        if ($value === null) {
            return null;
        }

        if (is_object($value) && ! $value instanceof $this->class) {
            throw new UnexpectedValueException(sprintf(
                'Unable to cast [%s] to [%s].',
                get_debug_type($value),
                $this->class,
            ));
        }

        $object = $value instanceof $this->class
            ? $value
            : $this->class::fromPgArrayValue($value);

        return $this->toLogicalValue($object);
    }

    private function toLogicalValue(PgArrayValue $object): string|int|float|bool|null
    {
        $value = $object->toPgArrayValue();

        if ($value === null || is_scalar($value)) {
            return $value;
        }

        throw new UnexpectedValueException(sprintf(
            '[%s]::toPgArrayValue() must return a scalar value or null, [%s] given. Implement [%s] to store structured values as JSON.',
            $object::class,
            get_debug_type($value),
            PgArrayJsonValue::class,
        ));
    }
}
