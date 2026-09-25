<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Exceptions;

use RuntimeException;

/**
 * Thrown when a PostgreSQL-only feature (pgArray() columns, wherePgArray*()
 * clauses, pgArrayAppend() / pgArrayPrepend()) is used with another driver.
 */
final class UnsupportedDriverException extends RuntimeException implements PgArrayException
{
    public static function for(string $feature, object $grammar): self
    {
        return new self(sprintf(
            '%s requires PostgreSQL, [%s] given.',
            $feature,
            $grammar::class,
        ));
    }
}
