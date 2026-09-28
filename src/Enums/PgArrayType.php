<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Enums;

use AndreaColzani\PgArray\Support\PgArrayParser;

/**
 * PostgreSQL element types, used by the pgArray() migration helper and the
 * query builder operators, e.g. $table->pgArray('tags', PgArrayType::Text).
 *
 * Type modifiers (varchar(50), numeric(10,2), …) are declared through
 * PgArrayTypeDefinition or the pgArray() column modifiers.
 */
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

    /** Not a PostgreSQL type: ULIDs are stored as char(26), like Laravel's ulid() columns. */
    case Ulid = 'ulid';

    /**
     * The SQL element type, without type modifiers.
     */
    public function sql(): string
    {
        return match ($this) {
            self::Ulid => 'char(26)',
            default => $this->value,
        };
    }

    /**
     * The array delimiter of the type (pg_type.typdelim): ':' for PostGIS
     * geometry and geography, ',' for every other type.
     */
    public function delimiter(): string
    {
        return match ($this) {
            self::Geometry, self::Geography => ':',
            default => PgArrayParser::DEFAULT_DELIMITER,
        };
    }
}
