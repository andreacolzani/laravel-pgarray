<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

interface StringValue
{
    public static function of(string $value): static;

    public function value(): string;
}
