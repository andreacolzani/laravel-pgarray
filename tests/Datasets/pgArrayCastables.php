<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\AsBooleanArray;
use AndreaColzani\PgArray\Casts\AsDateArray;
use AndreaColzani\PgArray\Casts\AsDateTimeArray;
use AndreaColzani\PgArray\Casts\AsImmutableDateArray;
use AndreaColzani\PgArray\Casts\AsImmutableDateTimeArray;
use AndreaColzani\PgArray\Casts\AsIntegerArray;
use AndreaColzani\PgArray\Casts\AsStringArray;

dataset('pg array castables', [
    AsBooleanArray::class,
    AsIntegerArray::class,
    AsStringArray::class,
    AsDateArray::class,
    AsDateTimeArray::class,
    AsImmutableDateArray::class,
    AsImmutableDateTimeArray::class,
]);
