<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use InvalidArgumentException;

/**
 * Thrown when the PgArrayValueCasterResolver cannot resolve an element type
 * to a supported PgArrayValueCaster.
 */
final class UnsupportedElementException extends InvalidArgumentException
{
    public static function unknownClass(string $type): self
    {
        return new self(
            "Unknown element type [{$type}]. Expected a PgArrayCast value or an existing class.",
        );
    }

    public static function notYetSupported(string $type): self
    {
        return new self(
            "Element type [{$type}] is not supported yet.",
        );
    }

    public static function unsupportedClass(string $type): self
    {
        return new self(
            "Unsupported element type [{$type}].",
        );
    }
}
