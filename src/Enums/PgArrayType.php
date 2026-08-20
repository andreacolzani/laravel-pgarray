<?php

namespace AndreaColzani\PgArray\Enums;

enum PgArrayType: string
{
    case Char = 'char';
    case Varchar = 'varchar';
    case Text = 'text';

    case SmallInt = 'smallint';
    case Integer = 'integer';
    case BigInt = 'bigint';

    case Real = 'real';
    case DoublePrecision = 'double precision';
    case Decimal = 'decimal';
    case Numeric = 'numeric';

    case Boolean = 'boolean';

    case Date = 'date';
    case Time = 'time';
    case TimeTz = 'timetz';
    case Timestamp = 'timestamp';
    case TimestampTz = 'timestamptz';

    case Bytea = 'bytea';
    case Uuid = 'uuid';
    case Inet = 'inet';
    case MacAddr = 'macaddr';

    case Json = 'json';
    case Jsonb = 'jsonb';

    case Geometry = 'geometry';
    case Geography = 'geography';

    case Vector = 'vector';
}
