<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

use AndreaColzani\PgArray\Contracts\PgArrayValue;
use InvalidArgumentException;

final class Email implements PgArrayValue
{
    public readonly string $address;

    public function __construct(string $address)
    {
        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid email address [{$address}].");
        }

        $this->address = strtolower($address);
    }

    public function toPgArrayValue(): string
    {
        return $this->address;
    }

    public static function fromPgArrayValue(mixed $value): static
    {
        return new self((string) $value);
    }
}
