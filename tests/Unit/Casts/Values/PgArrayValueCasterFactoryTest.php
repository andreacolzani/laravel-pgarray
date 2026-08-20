<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\BooleanCaster;
use AndreaColzani\PgArray\Casts\Values\IntegerCaster;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCasterFactory;
use AndreaColzani\PgArray\Casts\Values\StringCaster;
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

it('rejects an unimplemented caster', function (): void {
    PgArrayValueCasterFactory::make(PgArrayCast::Date);
})->throws(LogicException::class);
