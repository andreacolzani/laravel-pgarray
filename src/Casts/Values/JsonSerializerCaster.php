<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Contracts\PgArrayJsonSerializer;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use JsonException;

/**
 * json[] / jsonb[] elements handled by a PgArrayJsonSerializer, with the same
 * rules as JsonObjectCaster.
 *
 * @internal
 */
final class JsonSerializerCaster implements PgArrayValueCaster
{
    /**
     * @param  class-string  $class
     */
    public function __construct(
        private readonly string $class,
        private readonly PgArrayJsonSerializer $serializer,
    ) {}

    /**
     * @throws JsonException
     */
    public function get(mixed $value): ?object
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof $this->class
            ? $value
            : $this->fromJson($value);
    }

    /**
     * @throws JsonException
     */
    public function set(mixed $value): ?string
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
            : $this->fromJson($value);

        $logical = $object === null
            ? null
            : $this->serializer->serialize($object);

        return $logical === null
            ? null
            : json_encode($logical, JsonObjectCaster::ENCODE_FLAGS);
    }

    /**
     * @throws JsonException
     */
    private function fromJson(mixed $value): ?object
    {
        if (! is_string($value)) {
            throw new InvalidValueException(sprintf(
                'Unable to cast [%s] to [%s]: expected a JSON string.',
                get_debug_type($value),
                $this->class,
            ));
        }

        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        if ($decoded === null) {
            return null;
        }

        $object = $this->serializer->deserialize($decoded, $this->class);

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
}
