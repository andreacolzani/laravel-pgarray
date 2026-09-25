<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Database;

use AndreaColzani\PgArray\Enums\PgArrayType;
use AndreaColzani\PgArray\Exceptions\InvalidDefinitionException;
use AndreaColzani\PgArray\Exceptions\UnsupportedDriverException;
use Illuminate\Database\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Fluent;

/**
 * Registers the pgArray() migration helper:
 *
 *   $table->pgArray('tags', PgArrayType::Text);
 *   $table->pgArray('codes', PgArrayType::Varchar)->length(50)->nullable();
 *
 * @internal
 */
final class PgArraySchema
{
    public const COLUMN_TYPE = 'pgArray';

    public static function register(): void
    {
        Blueprint::macro('pgArray', function (string $column, PgArrayType|PgArrayTypeDefinition $type): PgArrayColumnDefinition {
            $definition = new PgArrayColumnDefinition($type, [
                'type' => PgArraySchema::COLUMN_TYPE,
                'name' => $column,
            ]);

            /** @var Blueprint $this */
            $this->addColumnDefinition($definition);

            return $definition;
        });

        // Laravel compiles a column type by calling type{Type}() on the schema grammar.
        Grammar::macro('type'.ucfirst(self::COLUMN_TYPE), function (Fluent $column): string {
            /** @var Grammar $this */
            return PgArraySchema::compileType($this, $column);
        });
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    public static function compileType(Grammar $grammar, Fluent $column): string
    {
        if (! $grammar instanceof PostgresGrammar) {
            throw UnsupportedDriverException::for('pgArray()', $grammar);
        }

        if (! $column instanceof PgArrayColumnDefinition) {
            throw new InvalidDefinitionException('pgArray() columns must be declared with the pgArray() helper.');
        }

        $sql = $column->toArraySql();

        if ($column->get('change')) {
            if ($column->forbidsNullElements()) {
                throw new InvalidDefinitionException(
                    'withoutNullElements() cannot be used when changing a column: add the CHECK constraint separately.',
                );
            }

            return $sql.self::compileUsing($grammar, $column);
        }

        if (! $column->forbidsNullElements()) {
            return $sql;
        }

        return $sql.' check (array_position('.$grammar->wrap($column).', NULL) is null)';
    }

    /**
     * The USING clause is appended to the type: Laravel compiles the altered
     * type with getType(), and adds the COLLATE clause after it.
     */
    private static function compileUsing(Grammar $grammar, PgArrayColumnDefinition $column): string
    {
        $expression = $column->usingExpression();

        if ($expression === null) {
            return '';
        }

        if ($column->get('collation') !== null) {
            throw new InvalidDefinitionException(
                'using() cannot be combined with collation() when changing a pgArray() column: change the collation separately.',
            );
        }

        return ' using '.$grammar->getValue($expression);
    }
}
