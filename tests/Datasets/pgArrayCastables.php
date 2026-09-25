<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\AsBooleanArray;
use AndreaColzani\PgArray\Casts\AsByteaArray;
use AndreaColzani\PgArray\Casts\AsDateArray;
use AndreaColzani\PgArray\Casts\AsDateTimeArray;
use AndreaColzani\PgArray\Casts\AsDecimalArray;
use AndreaColzani\PgArray\Casts\AsDoubleArray;
use AndreaColzani\PgArray\Casts\AsEncryptedArray;
use AndreaColzani\PgArray\Casts\AsFloatArray;
use AndreaColzani\PgArray\Casts\AsHashedArray;
use AndreaColzani\PgArray\Casts\AsImmutableDateArray;
use AndreaColzani\PgArray\Casts\AsImmutableDateTimeArray;
use AndreaColzani\PgArray\Casts\AsInetArray;
use AndreaColzani\PgArray\Casts\AsIntegerArray;
use AndreaColzani\PgArray\Casts\AsMacAddrArray;
use AndreaColzani\PgArray\Casts\AsRealArray;
use AndreaColzani\PgArray\Casts\AsStringableArray;
use AndreaColzani\PgArray\Casts\AsStringArray;
use AndreaColzani\PgArray\Casts\AsUlidArray;
use AndreaColzani\PgArray\Casts\AsUriArray;
use AndreaColzani\PgArray\Casts\AsUuidArray;

dataset('pg array castables', [
    AsBooleanArray::class,
    AsDecimalArray::class,
    AsDoubleArray::class,
    AsFloatArray::class,
    AsIntegerArray::class,
    AsRealArray::class,
    AsStringArray::class,
    AsStringableArray::class,
    AsDateArray::class,
    AsDateTimeArray::class,
    AsImmutableDateArray::class,
    AsImmutableDateTimeArray::class,
    AsUriArray::class,
    AsUlidArray::class,
    AsUuidArray::class,
    AsEncryptedArray::class,
    AsHashedArray::class,
    AsByteaArray::class,
    AsInetArray::class,
    AsMacAddrArray::class,
]);
