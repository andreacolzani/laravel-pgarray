<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\EnumCaster;
use AndreaColzani\PgArray\Casts\Values\IntegerCaster;
use AndreaColzani\PgArray\Casts\Values\PgArrayElementDefinition;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCasterFactory;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCasterResolver;
use AndreaColzani\PgArray\Casts\Values\UnsupportedElementException;
use AndreaColzani\PgArray\Enums\PgArrayCast;
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
