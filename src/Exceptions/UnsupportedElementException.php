<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when the PgArrayValueCasterResolver cannot resolve an element type
 * to a supported PgArrayValueCaster.
 */
final class UnsupportedElementException extends InvalidArgumentException implements PgArrayException
{
    public static function unknownClass(string $type): self
    {
        return new self(
            "Unknown element type [{$type}]. Expected a PgArrayCast value or an existing class.",
        );
    }

    public static function pureEnum(string $type): self
    {
        return new self(
            "Pure enum [{$type}] is not supported. Use a backed enum instead.",
        );
    }

    public static function unsupportedClass(string $type): self
    {
        return new self(
            "Unsupported element type [{$type}]. Implement PgArrayValue, or map a PgArrayValueSerializer to it through the pgarray.serializers configuration, the #[PgArraySerializer] attribute or PgArraySerializable.",
        );
    }

    public static function invalidSerializer(string $type, string $serializer): self
    {
        return new self(
            "Invalid serializer [{$serializer}] for element type [{$type}]. Serializers must implement PgArrayValueSerializer.",
        );
    }
}
