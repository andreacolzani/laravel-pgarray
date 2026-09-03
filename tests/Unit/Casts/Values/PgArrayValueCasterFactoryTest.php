<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\BooleanCaster;
use AndreaColzani\PgArray\Casts\Values\DateCaster;
use AndreaColzani\PgArray\Casts\Values\DateTimeCaster;
use AndreaColzani\PgArray\Casts\Values\DecimalCaster;
use AndreaColzani\PgArray\Casts\Values\DoubleCaster;
use AndreaColzani\PgArray\Casts\Values\FloatCaster;
use AndreaColzani\PgArray\Casts\Values\ImmutableDateCaster;
use AndreaColzani\PgArray\Casts\Values\ImmutableDateTimeCaster;
use AndreaColzani\PgArray\Casts\Values\IntegerCaster;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCasterFactory;
use AndreaColzani\PgArray\Casts\Values\RealCaster;
use AndreaColzani\PgArray\Casts\Values\StringableCaster;
use AndreaColzani\PgArray\Casts\Values\StringCaster;
use AndreaColzani\PgArray\Casts\Values\UriCaster;
use AndreaColzani\PgArray\Enums\PgArrayCast;

it('creates the boolean caster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::Boolean))
        ->toBeInstanceOf(BooleanCaster::class);
});

it('creates the integer caster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::Integer))
        ->toBeInstanceOf(IntegerCaster::class);
});

it('creates the string caster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::String))
        ->toBeInstanceOf(StringCaster::class);
});

it('creates the stringable caster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::Stringable))
        ->toBeInstanceOf(StringableCaster::class);
});

it('rejects an unimplemented caster', function (): void {
    PgArrayValueCasterFactory::make(PgArrayCast::Ulid);
})->throws(LogicException::class);

it('creates the decimal caster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::Decimal))
        ->toBeInstanceOf(DecimalCaster::class);
});

it('creates the double caster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::Double))
        ->toBeInstanceOf(DoubleCaster::class);
});

it('creates the float caster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::Float))
        ->toBeInstanceOf(FloatCaster::class);
});

it('creates the real caster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::Real))
        ->toBeInstanceOf(RealCaster::class);
});

it('creates a DateCaster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::Date))
        ->toBeInstanceOf(DateCaster::class);
});

it('creates a DateTimeCaster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::DateTime))
        ->toBeInstanceOf(DateTimeCaster::class);
});

it('creates an ImmutableDateCaster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::ImmutableDate))
        ->toBeInstanceOf(ImmutableDateCaster::class);
});

it('creates an ImmutableDateTimeCaster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::ImmutableDateTime))
        ->toBeInstanceOf(ImmutableDateTimeCaster::class);
});

it('creates a UriCaster', function (): void {
    expect(PgArrayValueCasterFactory::make(PgArrayCast::Uri))
        ->toBeInstanceOf(UriCaster::class);
});
