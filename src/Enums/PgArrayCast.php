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

    case Uri = 'uri';
    case Uuid = 'uuid';
    case Ulid = 'ulid';
}
