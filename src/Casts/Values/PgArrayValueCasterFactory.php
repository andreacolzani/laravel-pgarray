<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Enums\PgArrayCast;

final class PgArrayValueCasterFactory
{
    public static function make(PgArrayCast $type): PgArrayValueCaster
    {
        return match ($type) {
            PgArrayCast::Boolean => new BooleanCaster,
            PgArrayCast::Decimal => new DecimalCaster,
            PgArrayCast::Double => new DoubleCaster,
            PgArrayCast::Float => new FloatCaster,
            PgArrayCast::Integer => new IntegerCaster,
            PgArrayCast::Real => new RealCaster,
            PgArrayCast::String => new StringCaster,
            PgArrayCast::Stringable => new StringableCaster,
            PgArrayCast::Hashed => new HashedCaster,
            PgArrayCast::Date => new DateCaster,
            PgArrayCast::DateTime => new DateTimeCaster,
            PgArrayCast::ImmutableDate => new ImmutableDateCaster,
            PgArrayCast::ImmutableDateTime => new ImmutableDateTimeCaster,
            PgArrayCast::Uri => new UriCaster,
            PgArrayCast::Uuid => new UuidCaster,
            PgArrayCast::Ulid => new UlidCaster,
            PgArrayCast::Bytea => new ByteaCaster,
            PgArrayCast::Inet => new InetCaster,
            PgArrayCast::MacAddr => new MacAddrCaster,
        };
    }
}
