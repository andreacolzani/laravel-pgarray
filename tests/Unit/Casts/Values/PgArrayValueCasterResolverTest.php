<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\EncryptedCaster;
use AndreaColzani\PgArray\Casts\Values\EnumCaster;
use AndreaColzani\PgArray\Casts\Values\IntegerCaster;
use AndreaColzani\PgArray\Casts\Values\JsonObjectCaster;
use AndreaColzani\PgArray\Casts\Values\JsonSerializerCaster;
use AndreaColzani\PgArray\Casts\Values\ObjectCaster;
use AndreaColzani\PgArray\Casts\Values\PgArrayElementDefinition;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCasterFactory;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCasterResolver;
use AndreaColzani\PgArray\Casts\Values\SerializerCaster;
use AndreaColzani\PgArray\Casts\Values\UnsupportedElementException;
use AndreaColzani\PgArray\Casts\Values\VectorCaster;
use AndreaColzani\PgArray\Contracts\PgArrayValueSerializer;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Support\PgArraySerializerRegistry;
use AndreaColzani\PgArray\Tests\Fixtures\Address;
use AndreaColzani\PgArray\Tests\Fixtures\Color;
use AndreaColzani\PgArray\Tests\Fixtures\CountryCode;
use AndreaColzani\PgArray\Tests\Fixtures\Email;
use AndreaColzani\PgArray\Tests\Fixtures\Money;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;
use AndreaColzani\PgArray\Tests\Fixtures\Sku;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use AndreaColzani\PgArray\Tests\Fixtures\Suit;

it('resolves a built-in cast to the factory caster', function (PgArrayCast $type): void {
    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition($type)))
        ->toBeInstanceOf(PgArrayValueCasterFactory::make($type)::class);
})->with(PgArrayCast::cases());

it('uses a configured caster as is', function (): void {
    $caster = new VectorCaster(3);

    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition($caster)))
        ->toBe($caster);
});

it('wraps a configured caster when encrypted', function (): void {
    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(new VectorCaster(3), encrypted: true)))
        ->toBeInstanceOf(EncryptedCaster::class);
});

it('resolves an integer cast to the integer caster', function (): void {
    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(PgArrayCast::Integer)))
        ->toBeInstanceOf(IntegerCaster::class);
});

it('rejects an unknown class string', function (): void {
    PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition('App\\Missing\\Element'));
})->throws(
    UnsupportedElementException::class,
    'Unknown element type [App\\Missing\\Element]',
);

it('resolves a backed enum class string to the enum caster', function (string $enum): void {
    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition($enum)))
        ->toBeInstanceOf(EnumCaster::class);
})->with([Status::class, Priority::class]);

it('resolves a PgArrayValue class string to the object caster', function (): void {
    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(Email::class)))
        ->toBeInstanceOf(ObjectCaster::class);
});

it('resolves a PgArrayJsonValue class string to the JSON object caster', function (): void {
    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(Address::class)))
        ->toBeInstanceOf(JsonObjectCaster::class);
});

it('prefers the PgArrayValue contract over backed enum support', function (): void {
    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(Color::class)))
        ->toBeInstanceOf(ObjectCaster::class);
});

it('rejects a pure enum class string', function (): void {
    PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(Suit::class));
})->throws(
    UnsupportedElementException::class,
    'Pure enum ['.Suit::class.'] is not supported. Use a backed enum instead.',
);

it('rejects an unsupported class string', function (): void {
    PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(stdClass::class));
})->throws(
    UnsupportedElementException::class,
    'Unsupported element type [stdClass].',
);

it('rejects unsupported element types with an invalid argument exception', function (): void {
    PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(stdClass::class));
})->throws(InvalidArgumentException::class);

it('explains how to support an unsupported class string', function (): void {
    PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(stdClass::class));
})->throws(
    UnsupportedElementException::class,
    'Implement PgArrayValue, or map a PgArrayValueSerializer to it',
);

it('resolves a configured JSON serializer to the JSON serializer caster', function (): void {
    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(Money::class)))
        ->toBeInstanceOf(JsonSerializerCaster::class);
});

it('resolves declared scalar serializers to the serializer caster', function (string $class): void {
    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition($class)))
        ->toBeInstanceOf(SerializerCaster::class);
})->with([Sku::class, CountryCode::class]);

it('prefers a registered serializer over the PgArrayValue contract and backed enum support', function (string $class): void {
    app(PgArraySerializerRegistry::class)->register($class, new class implements PgArrayValueSerializer
    {
        public function serialize(object $value): mixed
        {
            return 'serialized';
        }

        public function deserialize(mixed $value, string $class): object
        {
            return new stdClass;
        }
    });

    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition($class)))
        ->toBeInstanceOf(SerializerCaster::class);
})->with([Email::class, Address::class, Color::class, Status::class]);

it('resolves a registered serializer for a class without other support', function (): void {
    app(PgArraySerializerRegistry::class)->register(stdClass::class, new class implements PgArrayValueSerializer
    {
        public function serialize(object $value): mixed
        {
            return 'serialized';
        }

        public function deserialize(mixed $value, string $class): object
        {
            return new stdClass;
        }
    });

    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(stdClass::class)))
        ->toBeInstanceOf(SerializerCaster::class);
});

it('rejects an invalid configured serializer', function (): void {
    app(PgArraySerializerRegistry::class)->register(Money::class, stdClass::class);

    PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(Money::class));
})->throws(
    UnsupportedElementException::class,
    'Invalid serializer [stdClass] for element type ['.Money::class.'].',
);

it('wraps encrypted built-in casts in the encrypted caster', function (): void {
    $caster = PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(PgArrayCast::Integer, encrypted: true));

    expect($caster)->toBeInstanceOf(EncryptedCaster::class)
        ->and($caster->get($caster->set('42')))->toBe(42);
});

it('wraps encrypted class-string types in the encrypted caster', function (string $type, mixed $value): void {
    $caster = PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition($type, encrypted: true));

    expect($caster)->toBeInstanceOf(EncryptedCaster::class)
        ->and($caster->get($caster->set($value)))->toEqual($value);
})->with([
    'backed enum' => [Status::class, Status::Active],
    'PgArrayValue' => [Email::class, new Email('mario@example.com')],
    'PgArrayJsonValue' => [Address::class, new Address('Via Roma 1', 'Milano')],
    'serializer' => [Money::class, new Money(1250, 'EUR')],
]);

it('rejects unsupported encrypted class-string types', function (): void {
    PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(stdClass::class, encrypted: true));
})->throws(UnsupportedElementException::class, 'Unsupported element type [stdClass].');
