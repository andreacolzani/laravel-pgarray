<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Enums;

use AndreaColzani\PgArray\Exceptions\InvalidDefinitionException;

/**
 * The PHP container of a retrieved array: a plain array or a Collection.
 */
enum PgArrayContainer: string
{
    case Array = 'array';
    case Collection = 'collection';

    /**
     * Resolve the container of a cast argument, e.g. AsStringArray:collection.
     *
     * @internal
     *
     * @throws InvalidDefinitionException
     */
    public static function fromCastArgument(?string $value): self
    {
        if ($value === null) {
            return self::Array;
        }

        return self::tryFrom($value) ?? throw new InvalidDefinitionException(sprintf(
            'Unsupported array container [%s]. Expected one of [%s].',
            $value,
            implode(', ', array_column(self::cases(), 'value')),
        ));
    }
}
