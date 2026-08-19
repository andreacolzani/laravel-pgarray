<?php

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Enums\PgArrayType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

final class PgArray implements CastsAttributes
{
    public function __construct(
        private readonly PgArrayType $type,
    ) {}

    public function get(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): mixed {
        //
    }

    public function set(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): mixed {
        //
    }
}
