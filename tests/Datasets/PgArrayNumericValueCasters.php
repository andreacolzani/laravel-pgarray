<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\DoubleCaster;
use AndreaColzani\PgArray\Casts\Values\FloatCaster;
use AndreaColzani\PgArray\Casts\Values\IntegerCaster;
use AndreaColzani\PgArray\Casts\Values\RealCaster;

dataset('pg array numeric value casters', [
    IntegerCaster::class,
    FloatCaster::class,
    DoubleCaster::class,
    RealCaster::class,
]);
