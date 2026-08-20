<?php

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Enums\PgArrayContainer;
use Illuminate\Contracts\Database\Eloquent\Castable;

abstract class PgArrayCastable implements Castable
{
    public static function collect(): string
    {
        return static::class.':'.PgArrayContainer::Collection->value;
    }
}
