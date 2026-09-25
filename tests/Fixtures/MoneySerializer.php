<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

use AndreaColzani\PgArray\Contracts\PgArrayJsonSerializer;
use InvalidArgumentException;

final class MoneySerializer implements PgArrayJsonSerializer
{
    /**
     * @return array{amount: int, currency: string}
     */
    public function serialize(object $value): array
    {
        if (! $value instanceof Money) {
            throw new InvalidArgumentException('Expected a Money instance.');
        }

        return [
            'amount' => $value->amount,
            'currency' => $value->currency,
        ];
    }

    public function deserialize(mixed $value, string $class): Money
    {
        if (! is_array($value) || ! isset($value['amount'], $value['currency'])) {
            throw new InvalidArgumentException('Invalid money value.');
        }

        return new Money((int) $value['amount'], (string) $value['currency']);
    }
}
