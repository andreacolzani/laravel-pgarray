<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Database;

use AndreaColzani\PgArray\Enums\PgArrayType;
use Illuminate\Database\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Fluent;
use RuntimeException;

/**
 * Registers the pgArray() migration helper:
 *
 *   $table->pgArray('tags', PgArrayType::Text);
 *   $table->pgArray('codes', PgArrayType::Varchar)->length(50)->nullable();
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
            throw new RuntimeException(sprintf(
                'pgArray() columns are only supported by PostgreSQL, [%s] given.',
                $grammar::class,
            ));
        }

        if (! $column instanceof PgArrayColumnDefinition) {
            throw new RuntimeException('pgArray() columns must be declared with the pgArray() helper.');
        }

        $sql = $column->toArraySql();

        if (! $column->forbidsNullElements()) {
            return $sql;
        }

        if ($column->get('change')) {
            throw new RuntimeException(
                'withoutNullElements() cannot be used when changing a column: add the CHECK constraint separately.',
            );
        }

        return $sql.' check (array_position('.$grammar->wrap($column).', NULL) is null)';
    }
}
