<?php

namespace AndreaColzani\PgArray\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AndreaColzani\PgArray\PgArray
 */
class PgArray extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AndreaColzani\PgArray\PgArray::class;
    }
}
