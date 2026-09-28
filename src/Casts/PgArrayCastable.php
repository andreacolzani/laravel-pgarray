<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Enums\PgArrayContainer;
use Illuminate\Contracts\Database\Eloquent\Castable;

/**
 * Base class of the built-in As*Array casts.
 *
 *   'tags' => AsStringArray::class,       // array
 *   'tags' => AsStringArray::collect(),   // Collection
 */
abstract class PgArrayCastable implements Castable
{
    public static function collect(): string
    {
        return static::class.':'.PgArrayContainer::Collection->value;
    }
}
