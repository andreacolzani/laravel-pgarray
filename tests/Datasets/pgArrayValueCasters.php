<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\BooleanCaster;
use AndreaColzani\PgArray\Casts\Values\DateCaster;
use AndreaColzani\PgArray\Casts\Values\DateTimeCaster;
use AndreaColzani\PgArray\Casts\Values\DecimalCaster;
use AndreaColzani\PgArray\Casts\Values\DoubleCaster;
use AndreaColzani\PgArray\Casts\Values\EnumCaster;
use AndreaColzani\PgArray\Casts\Values\FloatCaster;
use AndreaColzani\PgArray\Casts\Values\HashedCaster;
use AndreaColzani\PgArray\Casts\Values\ImmutableDateCaster;
use AndreaColzani\PgArray\Casts\Values\ImmutableDateTimeCaster;
use AndreaColzani\PgArray\Casts\Values\IntegerCaster;
use AndreaColzani\PgArray\Casts\Values\JsonObjectCaster;
use AndreaColzani\PgArray\Casts\Values\ObjectCaster;
use AndreaColzani\PgArray\Casts\Values\RealCaster;
use AndreaColzani\PgArray\Casts\Values\StringableCaster;
use AndreaColzani\PgArray\Casts\Values\StringCaster;
use AndreaColzani\PgArray\Casts\Values\UlidCaster;
use AndreaColzani\PgArray\Casts\Values\UriCaster;
use AndreaColzani\PgArray\Casts\Values\UuidCaster;

dataset('pg array value casters', [
    BooleanCaster::class,
    DateCaster::class,
    DateTimeCaster::class,
    DecimalCaster::class,
    DoubleCaster::class,
    EnumCaster::class,
    FloatCaster::class,
    HashedCaster::class,
    ImmutableDateCaster::class,
    ImmutableDateTimeCaster::class,
    IntegerCaster::class,
    JsonObjectCaster::class,
    ObjectCaster::class,
    RealCaster::class,
    StringCaster::class,
    StringableCaster::class,
    UriCaster::class,
    UuidCaster::class,
    UlidCaster::class,
]);
