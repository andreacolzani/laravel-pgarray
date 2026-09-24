<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\IntegerCaster;
use AndreaColzani\PgArray\Casts\Values\PgArrayElementDefinition;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCasterFactory;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCasterResolver;
use AndreaColzani\PgArray\Casts\Values\UnsupportedElementException;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Tests\Fixtures\Status;

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

it('rejects a backed enum class string until enums are supported', function (): void {
    PgArrayValueCasterResolver::resolve(new PgArrayElementDefinition(Status::class));
})->throws(
    UnsupportedElementException::class,
    'Element type ['.Status::class.'] is not supported yet.',
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
