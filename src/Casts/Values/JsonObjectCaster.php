<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Contracts\PgArrayJsonValue;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use JsonException;

/**
 * Casts json[] / jsonb[] elements to and from a PgArrayJsonValue implementation.
 *
 *   DB  → json_decode (objects as associative arrays) → fromPgArrayValue()
 *   PHP → toPgArrayValue() → json_encode
 *
 * SQL NULL elements, JSON null elements and null logical values are all
 * treated as null. On set(), raw JSON strings are normalized through
 * fromPgArrayValue() first, so the class can validate them. Objects of any
 * other class are rejected.
 *
 * @internal
 */
final class JsonObjectCaster implements PgArrayValueCaster
{
    public const ENCODE_FLAGS = JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_PRESERVE_ZERO_FRACTION;

    /**
     * @param  class-string<PgArrayJsonValue>  $class
     */
    public function __construct(
        private readonly string $class,
    ) {}

    /**
     * @throws JsonException
     */
    public function get(mixed $value): ?PgArrayJsonValue
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

        $logical = $object?->toPgArrayValue();

        return $logical === null
            ? null
            : json_encode($logical, self::ENCODE_FLAGS);
    }

    /**
     * @throws JsonException
     */
    private function fromJson(mixed $value): ?PgArrayJsonValue
    {
        if (! is_string($value)) {
            throw new InvalidValueException(sprintf(
                'Unable to cast [%s] to [%s]: expected a JSON string.',
                get_debug_type($value),
                $this->class,
            ));
        }

        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return $decoded === null
            ? null
            : $this->class::fromPgArrayValue($decoded);
    }
}
