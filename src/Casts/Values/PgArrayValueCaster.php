<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

interface PgArrayValueCaster
{
    public function get(mixed $value): mixed;

    public function set(mixed $value): mixed;
}
