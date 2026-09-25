<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Contracts\PgArrayValueSerializer;
use AndreaColzani\PgArray\Exceptions\UnsupportedElementException;
use AndreaColzani\PgArray\Support\PgArraySerializerRegistry;
use AndreaColzani\PgArray\Tests\Fixtures\CountryCode;
use AndreaColzani\PgArray\Tests\Fixtures\Email;
use AndreaColzani\PgArray\Tests\Fixtures\Money;
use AndreaColzani\PgArray\Tests\Fixtures\MoneySerializer;
use AndreaColzani\PgArray\Tests\Fixtures\Sku;
use AndreaColzani\PgArray\Tests\Fixtures\StringValueSerializer;

function serializerRegistry(array $serializers = []): PgArraySerializerRegistry
{
    return new PgArraySerializerRegistry(app(), $serializers);
}

it('is registered as a singleton loaded from the configuration', function (): void {
    $registry = app(PgArraySerializerRegistry::class);

    expect($registry)->toBe(app(PgArraySerializerRegistry::class))
        ->and($registry->resolve(Money::class))->toBeInstanceOf(MoneySerializer::class);
});

it('resolves serializers mapped at construction', function (): void {
    expect(serializerRegistry([Money::class => MoneySerializer::class])->resolve(Money::class))
        ->toBeInstanceOf(MoneySerializer::class);
});

it('resolves registered serializer classes and instances', function (): void {
    $registry = serializerRegistry();
    $instance = new MoneySerializer;

    $registry->register(Money::class, StringValueSerializer::class);
    $registry->register(Email::class, $instance);

    expect($registry->resolve(Money::class))->toBeInstanceOf(StringValueSerializer::class)
        ->and($registry->resolve(Email::class))->toBe($instance);
});

it('registers a serializer shared by multiple classes', function (): void {
    $registry = serializerRegistry();

    $registry->register([Money::class, Email::class], StringValueSerializer::class);

    expect($registry->resolve(Money::class))
        ->toBeInstanceOf(StringValueSerializer::class)
        ->toBe($registry->resolve(Email::class));
});

it('shares serializer instances between declared classes', function (): void {
    $registry = serializerRegistry();

    expect($registry->resolve(Sku::class))
        ->toBeInstanceOf(StringValueSerializer::class)
        ->toBe($registry->resolve(CountryCode::class));
});

it('resolves serializers declared through the attribute', function (): void {
    expect(serializerRegistry()->resolve(Sku::class))
        ->toBeInstanceOf(StringValueSerializer::class);
});

it('resolves serializers declared through PgArraySerializable', function (): void {
    expect(serializerRegistry()->resolve(CountryCode::class))
        ->toBeInstanceOf(StringValueSerializer::class);
});

it('prefers registered serializers over declared ones', function (): void {
    $registry = serializerRegistry([Sku::class => MoneySerializer::class]);

    expect($registry->resolve(Sku::class))->toBeInstanceOf(MoneySerializer::class);
});

it('matches classes by exact name only', function (): void {
    $registry = serializerRegistry([Money::class => MoneySerializer::class]);

    $subclass = new class('x') extends Exception {};

    expect($registry->resolve(Email::class))->toBeNull()
        ->and($registry->resolve($subclass::class))->toBeNull();
});

it('instantiates serializers through the container', function (): void {
    $instance = new StringValueSerializer;
    app()->instance(StringValueSerializer::class, $instance);

    expect(serializerRegistry()->resolve(Sku::class))->toBe($instance);
});

it('returns null for classes without a serializer', function (): void {
    expect(serializerRegistry()->resolve(Email::class))->toBeNull();
});

it('rejects serializers not implementing the contract', function (string $serializer): void {
    serializerRegistry([Money::class => $serializer])->resolve(Money::class);
})->with([stdClass::class, 'App\Missing\Serializer'])->throws(
    UnsupportedElementException::class,
    'Serializers must implement PgArrayValueSerializer.',
);

it('names the element type and serializer in invalid serializer errors', function (): void {
    serializerRegistry([Money::class => stdClass::class])->resolve(Money::class);
})->throws(
    UnsupportedElementException::class,
    'Invalid serializer [stdClass] for element type ['.Money::class.'].',
);

it('accepts serializer instances implementing the contract', function (): void {
    $serializer = new class implements PgArrayValueSerializer
    {
        public function serialize(object $value): mixed
        {
            return null;
        }

        public function deserialize(mixed $value, string $class): object
        {
            return new $class;
        }
    };

    expect(serializerRegistry([Money::class => $serializer])->resolve(Money::class))->toBe($serializer);
});
