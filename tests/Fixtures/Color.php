<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

use AndreaColzani\PgArray\Contracts\PgArrayValue;

/**
 * A backed enum that also implements PgArrayValue: the contract takes
 * precedence, so elements are stored as upper-case labels.
 */
enum Color: string implements PgArrayValue
{
    case Red = 'red';
    case Green = 'green';

    public function toPgArrayValue(): string
    {
        return strtoupper($this->value);
    }

    public static function fromPgArrayValue(mixed $value): static
    {
        return self::from(strtolower((string) $value));
    }
}
