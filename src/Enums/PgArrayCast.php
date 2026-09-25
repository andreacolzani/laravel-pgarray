<?php

namespace AndreaColzani\PgArray\Enums;

enum PgArrayCast: string
{
    case Boolean = 'boolean';

    case Date = 'date';
    case DateTime = 'datetime';
    case ImmutableDate = 'immutable_date';
    case ImmutableDateTime = 'immutable_datetime';

    case Decimal = 'decimal';
    case Double = 'double';
    case Float = 'float';
    case Integer = 'integer';
    case Real = 'real';

    case String = 'string';
    case Stringable = 'stringable';

    case Hashed = 'hashed';

    case Bytea = 'bytea';
    case Inet = 'inet';
    case MacAddr = 'macaddr';

    case Vector = 'vector';

    case Geometry = 'geometry';
    case Geography = 'geography';

    case Uri = 'uri';
    case Uuid = 'uuid';
    case Ulid = 'ulid';
}
