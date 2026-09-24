<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

use AndreaColzani\PgArray\Contracts\PgArrayValue;

final class Address implements PgArrayValue
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}

    /**
     * @return array{street: string, city: string}
     */
    public function toPgArrayValue(): array
    {
        return [
            'street' => $this->street,
            'city' => $this->city,
        ];
    }

    public static function fromPgArrayValue(mixed $value): static
    {
        return new self($value['street'], $value['city']);
    }
}
