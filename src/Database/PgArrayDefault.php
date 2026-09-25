<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Database;

use AndreaColzani\PgArray\Support\PgArrayLiteral;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Grammar;

/**
 * Default value of a pgArray() column, e.g. '{a,b}'::text[].
 *
 * The cast is rendered when the migration is compiled, so the column type
 * modifiers can be declared before or after default().
 */
final class PgArrayDefault implements Expression
{
    private readonly string $literal;

    /**
     * @param  array<array-key, mixed>|Arrayable<array-key, mixed>  $value
     */
    public function __construct(
        private readonly PgArrayColumnDefinition $column,
        array|Arrayable $value,
    ) {
        $this->literal = PgArrayLiteral::from($value, $column->definition()->type->delimiter());
    }

    /**
     * The PostgreSQL array literal, e.g. {a,b}.
     */
    public function literal(): string
    {
        return $this->literal;
    }

    public function getValue(Grammar $grammar): string
    {
        return "'".str_replace("'", "''", $this->literal)."'::".$this->column->toArraySql();
    }
}
