<?php

namespace AndreaColzani\PgArray\Tests\Models;

use AndreaColzani\PgArray\Casts\AsPgArray;
use AndreaColzani\PgArray\Enums\PgArrayType;
use Illuminate\Database\Eloquent\Model;

final class TestModel extends Model
{
    protected function casts(): array
    {
        return [
            'tags' => AsPgArray::class,
            'numbers' => AsPgArray::of(PgArrayType::Integer),
        ];
    }
}
