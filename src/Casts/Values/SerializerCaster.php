<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Contracts\PgArrayJsonSerializer;
use AndreaColzani\PgArray\Contracts\PgArrayValueSerializer;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;

/**
 * Elements handled by an external PgArrayValueSerializer, with the same rules
 * as ObjectCaster.
 *
 * @internal
 */
final class SerializerCaster implements PgArrayValueCaster
{
    /**
     * @param  class-string  $class
     */
    public function __construct(
        private readonly string $class,
        private readonly PgArrayValueSerializer $serializer,
    ) {}

    public function get(mixed $value): ?object
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof $this->class
            ? $value
            : $this->deserialize($value);
    }

    public function set(mixed $value): string|int|float|bool|null
    {
        if ($value === null) {
            return null;
        }

        if (is_object($value) && ! $value instanceof $this->class) {
            throw new InvalidValueException(sprintf(
                'Unable to cast [%s] to [%s].',
                get_debug_type($value),
                $this->class,
            ));
        }

        $object = $value instanceof $this->class
            ? $value
            : $this->deserialize($value);

        return $this->toLogicalValue($object);
    }

    private function deserialize(mixed $value): object
    {
        $object = $this->serializer->deserialize($value, $this->class);

        if (! $object instanceof $this->class) {
            throw new InvalidValueException(sprintf(
                '[%s]::deserialize() must return an instance of [%s], [%s] given.',
                $this->serializer::class,
                $this->class,
                get_debug_type($object),
            ));
        }

        return $object;
    }

    private function toLogicalValue(object $object): string|int|float|bool|null
    {
        $value = $this->serializer->serialize($object);

        if ($value === null || is_scalar($value)) {
            return $value;
        }

        throw new InvalidValueException(sprintf(
            '[%s]::serialize() must return a scalar value or null, [%s] given. Implement [%s] to store structured values as JSON.',
            $this->serializer::class,
            get_debug_type($value),
            PgArrayJsonSerializer::class,
        ));
    }
}
