<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\EnumCaster;
use AndreaColzani\PgArray\Casts\Values\IntegerCaster;
use AndreaColzani\PgArray\Casts\Values\JsonObjectCaster;
use AndreaColzani\PgArray\Casts\Values\ObjectCaster;
use AndreaColzani\PgArray\Casts\Values\PgArrayElementDefinition;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCasterFactory;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCasterResolver;
use AndreaColzani\PgArray\Casts\Values\UnsupportedElementException;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Tests\Fixtures\Address;
use AndreaColzani\PgArray\Tests\Fixtures\Color;
use AndreaColzani\PgArray\Tests\Fixtures\Email;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use AndreaColzani\PgArray\Tests\Fixtures\Suit;

it('resolves a built-in cast to the factory caster', function (PgArrayCast $type): void {
    expect(PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition($type)))
        ->toBeInstanceOf(PgArrayValueCasterFactory::make($type)::class);
})->with(PgArrayCast::cases());

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
