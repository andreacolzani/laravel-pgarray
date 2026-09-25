<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

use AndreaColzani\PgArray\Attributes\PgArraySerializer;

#[PgArraySerializer(StringValueSerializer::class)]
final class Sku implements StringValue
{
    public function __construct(
        public readonly string $value,
    ) {}

    public static function of(string $value): static
    {
        return new self(strtoupper($value));
    }

    public function value(): string
    {
        return $this->value;
    }
}
