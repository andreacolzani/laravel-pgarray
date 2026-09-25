<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Database;

use AndreaColzani\PgArray\Enums\PgArrayType;
use AndreaColzani\PgArray\Support\PgArrayLiteral;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;

/**
 * The value of an append / prepend update, e.g. "tags" || '{new}'.
 *
 * The literal is escaped and inlined: update() does not support bindings
 * inside expressions.
 *
 * @internal
 */
final class PgArrayConcatenation implements Expression
{
    private readonly string $literal;

    public function __construct(
        private readonly string $column,
        mixed $values,
        private readonly PgArrayType|PgArrayTypeDefinition|null $type = null,
        private readonly bool $prepend = false,
    ) {
        $this->literal = PgArrayLiteral::from($values, PgArrayQuery::delimiter($type));
    }

    public function getValue(Grammar $grammar): string
    {
        $operands = [
            $grammar->wrap($this->column),
            $grammar->escape($this->literal).PgArrayQuery::cast($this->type),
        ];

        return implode(' || ', $this->prepend ? array_reverse($operands) : $operands);
    }
}
