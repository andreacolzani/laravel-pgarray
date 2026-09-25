<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

use AndreaColzani\PgArray\Contracts\PgArraySerializable;
use InvalidArgumentException;

final class CountryCode implements PgArraySerializable, StringValue
{
    public function __construct(
        public readonly string $value,
    ) {
        if (preg_match('/^[A-Z]{2}$/', $value) !== 1) {
            throw new InvalidArgumentException("Invalid country code [{$value}].");
        }
    }

    public static function of(string $value): static
    {
        return new self(strtoupper($value));
    }

    public function value(): string
    {
        return $this->value;
    }

    public static function pgArraySerializer(): string
    {
        return StringValueSerializer::class;
    }
}
