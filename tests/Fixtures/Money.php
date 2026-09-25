<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

/**
 * A class that "cannot be modified": it implements no package contract and is
 * mapped to MoneySerializer through the pgarray.serializers configuration.
 */
final class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    ) {}
}
