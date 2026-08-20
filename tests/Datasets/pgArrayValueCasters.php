<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\BooleanCaster;
use AndreaColzani\PgArray\Casts\Values\DecimalCaster;
use AndreaColzani\PgArray\Casts\Values\DoubleCaster;
use AndreaColzani\PgArray\Casts\Values\FloatCaster;
use AndreaColzani\PgArray\Casts\Values\IntegerCaster;
use AndreaColzani\PgArray\Casts\Values\RealCaster;
use AndreaColzani\PgArray\Casts\Values\StringCaster;

dataset('pg array value casters', [
    BooleanCaster::class,
    DecimalCaster::class,
    DoubleCaster::class,
    FloatCaster::class,
    IntegerCaster::class,
    RealCaster::class,
    StringCaster::class,
]);
