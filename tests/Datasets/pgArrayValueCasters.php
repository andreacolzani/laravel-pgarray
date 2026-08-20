<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\BooleanCaster;
use AndreaColzani\PgArray\Casts\Values\IntegerCaster;
use AndreaColzani\PgArray\Casts\Values\StringCaster;

dataset('pg array value casters', [
    BooleanCaster::class,
    IntegerCaster::class,
    StringCaster::class,
]);
