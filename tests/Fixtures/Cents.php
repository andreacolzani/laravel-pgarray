<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

use AndreaColzani\PgArray\Contracts\PgArrayValue;

final class Cents implements PgArrayValue
{
    public function __construct(
        public readonly int $amount,
    ) {}

    public function toPgArrayValue(): int
    {
        return $this->amount;
    }

    public static function fromPgArrayValue(mixed $value): static
    {
        return new self((int) $value);
    }
}
