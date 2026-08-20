<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Models;

use AndreaColzani\PgArray\Casts\AsIntegerArray;
use AndreaColzani\PgArray\Casts\AsPgArray;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use Illuminate\Database\Eloquent\Model;

final class TestModel extends Model
{
    protected $fillable = ['numbers'];

    protected function casts(): array
    {
        return [
            'tags' => AsPgArray::class,
            'numbers' => AsPgArray::of(PgArrayCast::Integer),
            'number_collection' => AsPgArray::of(
                PgArrayCast::Integer,
                PgArrayContainer::Collection,
            ),
            'integer_array' => AsIntegerArray::class,
            'integer_collection' => AsIntegerArray::collect(),
        ];
    }
}
