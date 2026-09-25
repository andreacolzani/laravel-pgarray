<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\SerializerCaster;
use AndreaColzani\PgArray\Contracts\PgArrayJsonSerializer;
use AndreaColzani\PgArray\Contracts\PgArrayValueSerializer;
use AndreaColzani\PgArray\Tests\Fixtures\CountryCode;
use AndreaColzani\PgArray\Tests\Fixtures\Money;
use AndreaColzani\PgArray\Tests\Fixtures\MoneySerializer;
use AndreaColzani\PgArray\Tests\Fixtures\Sku;
use AndreaColzani\PgArray\Tests\Fixtures\StringValueSerializer;

it('creates objects from logical values', function (): void {
    expect((new SerializerCaster(CountryCode::class, new StringValueSerializer))->get('IT'))
        ->toEqual(new CountryCode('IT'));
});

it('passes the target class to shared serializers', function (): void {
    $serializer = new StringValueSerializer;

    expect((new SerializerCaster(Sku::class, $serializer))->get('ab-1'))
        ->toEqual(new Sku('AB-1'))
        ->and((new SerializerCaster(CountryCode::class, $serializer))->get('it'))
        ->toEqual(new CountryCode('IT'));
});

it('returns instances unchanged when retrieving', function (): void {
    $code = new CountryCode('IT');

    expect((new SerializerCaster(CountryCode::class, new StringValueSerializer))->get($code))->toBe($code);
});

it('serializes objects to their logical values', function (): void {
    expect((new SerializerCaster(CountryCode::class, new StringValueSerializer))->set(new CountryCode('IT')))
        ->toBe('IT');
});

it('normalizes raw values through the serializer when serializing', function (): void {
    expect((new SerializerCaster(CountryCode::class, new StringValueSerializer))->set('it'))
        ->toBe('IT');
});

it('lets the class reject invalid raw values', function (): void {
    (new SerializerCaster(CountryCode::class, new StringValueSerializer))->set('ITA');
})->throws(InvalidArgumentException::class, 'Invalid country code [ITA].');

it('lets the class reject invalid database values', function (): void {
    (new SerializerCaster(CountryCode::class, new StringValueSerializer))->get('ITA');
})->throws(InvalidArgumentException::class, 'Invalid country code [ITA].');

it('preserves null', function (): void {
    $caster = new SerializerCaster(CountryCode::class, new StringValueSerializer);

    expect($caster->get(null))->toBeNull()
        ->and($caster->set(null))->toBeNull();
});

it('rejects objects of a different class when serializing', function (): void {
    (new SerializerCaster(CountryCode::class, new StringValueSerializer))->set(new Sku('AB-1'));
})->throws(
    UnexpectedValueException::class,
    'Unable to cast ['.Sku::class.'] to ['.CountryCode::class.'].',
);

it('rejects non-scalar logical values', function (): void {
    (new SerializerCaster(Money::class, new MoneySerializer))->set(new Money(100, 'EUR'));
})->throws(
    UnexpectedValueException::class,
    '['.MoneySerializer::class.']::serialize() must return a scalar value or null, [array] given. Implement ['.PgArrayJsonSerializer::class.'] to store structured values as JSON.',
);

it('rejects deserialized values of a different class', function (): void {
    $serializer = new class implements PgArrayValueSerializer
    {
        public function serialize(object $value): mixed
        {
            return 'x';
        }

        public function deserialize(mixed $value, string $class): object
        {
            return new stdClass;
        }
    };

    (new SerializerCaster(CountryCode::class, $serializer))->get('IT');
})->throws(
    UnexpectedValueException::class,
    '::deserialize() must return an instance of ['.CountryCode::class.'], [stdClass] given.',
);
