<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\JsonSerializerCaster;
use AndreaColzani\PgArray\Contracts\PgArrayJsonSerializer;
use AndreaColzani\PgArray\Tests\Fixtures\Money;
use AndreaColzani\PgArray\Tests\Fixtures\MoneySerializer;
use AndreaColzani\PgArray\Tests\Fixtures\Sku;

function moneyCaster(): JsonSerializerCaster
{
    return new JsonSerializerCaster(Money::class, new MoneySerializer);
}

it('creates objects from JSON values', function (): void {
    expect(moneyCaster()->get('{"currency": "EUR", "amount": 1250}'))
        ->toEqual(new Money(1250, 'EUR'));
});

it('returns instances unchanged when retrieving', function (): void {
    $money = new Money(1250, 'EUR');

    expect(moneyCaster()->get($money))->toBe($money);
});

it('serializes objects to JSON', function (): void {
    expect(moneyCaster()->set(new Money(1250, 'EUR')))
        ->toBe('{"amount":1250,"currency":"EUR"}');
});

it('normalizes raw JSON strings through the serializer when serializing', function (): void {
    expect(moneyCaster()->set('{"currency": "EUR", "amount": 1250}'))
        ->toBe('{"amount":1250,"currency":"EUR"}');
});

it('lets the serializer reject invalid values', function (): void {
    moneyCaster()->get('{"amount": 1250}');
})->throws(InvalidArgumentException::class, 'Invalid money value.');

it('preserves null', function (): void {
    expect(moneyCaster()->get(null))->toBeNull()
        ->and(moneyCaster()->set(null))->toBeNull();
});

it('treats JSON null elements as null', function (): void {
    expect(moneyCaster()->get('null'))->toBeNull()
        ->and(moneyCaster()->set('null'))->toBeNull();
});

it('stores null logical values as null', function (): void {
    $serializer = new class implements PgArrayJsonSerializer
    {
        public function serialize(object $value): mixed
        {
            return null;
        }

        public function deserialize(mixed $value, string $class): object
        {
            return new Money(0, 'EUR');
        }
    };

    expect((new JsonSerializerCaster(Money::class, $serializer))->set(new Money(1, 'EUR')))->toBeNull();
});

it('fails explicitly on invalid JSON', function (): void {
    moneyCaster()->get('{"amount":');
})->throws(JsonException::class);

it('rejects non-string raw values', function (): void {
    moneyCaster()->set(123);
})->throws(
    UnexpectedValueException::class,
    'Unable to cast [int] to ['.Money::class.']: expected a JSON string.',
);

it('rejects objects of a different class when serializing', function (): void {
    moneyCaster()->set(new Sku('AB-1'));
})->throws(
    UnexpectedValueException::class,
    'Unable to cast ['.Sku::class.'] to ['.Money::class.'].',
);

it('rejects deserialized values of a different class', function (): void {
    $serializer = new class implements PgArrayJsonSerializer
    {
        public function serialize(object $value): mixed
        {
            return [];
        }

        public function deserialize(mixed $value, string $class): object
        {
            return new stdClass;
        }
    };

    (new JsonSerializerCaster(Money::class, $serializer))->get('{}');
})->throws(
    UnexpectedValueException::class,
    '::deserialize() must return an instance of ['.Money::class.'], [stdClass] given.',
);
