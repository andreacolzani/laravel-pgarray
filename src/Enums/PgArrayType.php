<?php

namespace AndreaColzani\PgArray\Enums;

enum PgArrayType: string
{
    case BigInt = 'bigint';
    case Integer = 'integer';
    case SmallInt = 'smallint';

    case Decimal = 'decimal';
    case Numeric = 'numeric';
    case Real = 'real';
    case DoublePrecision = 'double precision';

    case Boolean = 'boolean';

    case String = 'string';
    case Stringable = 'stringable';
    case Text = 'text';
    case Varchar = 'varchar';
    case Uuid = 'uuid';

    case Date = 'date';
    case Timestamp = 'timestamp';
    case TimestampTz = 'timestamptz';
}
