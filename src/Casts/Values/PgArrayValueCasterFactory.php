<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Enums\PgArrayCast;
use LogicException;

final class PgArrayValueCasterFactory
{
    public static function make(PgArrayCast $type): PgArrayValueCaster
    {
        return match ($type) {
            PgArrayCast::Boolean => new BooleanCaster,
            PgArrayCast::Integer => new IntegerCaster,
            PgArrayCast::String => new StringCaster,
            default => throw new LogicException(
                "No value caster is registered for [{$type->value}].",
            ),
        };
    }
}
