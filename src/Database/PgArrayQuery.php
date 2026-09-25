<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Database;

use AndreaColzani\PgArray\Enums\PgArrayType;
use AndreaColzani\PgArray\Support\PgArrayLiteral;
use AndreaColzani\PgArray\Support\PgArrayParser;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use RuntimeException;

/**
 * Registers the PostgreSQL array operators on the query builder:
 *
 *   ->wherePgArrayContains('tags', ['php', 'laravel']);   // "tags" @> ?
 *   ->wherePgArrayContainedBy('tags', $allowed);          // "tags" <@ ?
 *   ->wherePgArrayOverlaps('tags', collect(['php']));     // "tags" && ?
 *   ->pgArrayAppend('tags', ['new']);                     // set "tags" = "tags" || '{new}'
 */
final class PgArrayQuery
{
    public const WHERE_TYPE = 'PgArray';

    /**
     * Where macro names by operator: [where, negated where].
     */
    private const OPERATORS = [
        '@>' => ['wherePgArrayContains', 'wherePgArrayDoesntContain'],
        '<@' => ['wherePgArrayContainedBy', 'wherePgArrayNotContainedBy'],
        '&&' => ['wherePgArrayOverlaps', 'wherePgArrayDoesntOverlap'],
    ];

    public static function register(): void
    {
        foreach (self::OPERATORS as $operator => $methods) {
            foreach ($methods as $index => $method) {
                $not = $index === 1;

                self::registerWhere($method, $operator, $not, 'and');
                self::registerWhere('or'.ucfirst($method), $operator, $not, 'or');
            }
        }

        // Laravel compiles a where clause by calling where{Type}() on the query grammar.
        Grammar::macro('where'.self::WHERE_TYPE, function (Builder $query, array $where): string {
            /** @var array{column: string|Expression, operator: string, not: bool, cast: PgArrayType|PgArrayTypeDefinition|null} $where */
            return PgArrayQuery::compileWhere(PgArrayQuery::grammar($query, 'wherePgArray'), $where);
        });

        foreach (['pgArrayAppend' => false, 'pgArrayPrepend' => true] as $method => $prepend) {
            Builder::macro($method, function (
                string $column,
                mixed $values,
                PgArrayType|PgArrayTypeDefinition|null $type = null,
                array $extra = [],
            ) use ($prepend): int {
                /** @var Builder $this */
                return $this->update(PgArrayQuery::concatenation($this, $column, $values, $type, $prepend) + $extra);
            });

            // Eloquent updates also touch the updated_at column.
            EloquentBuilder::macro($method, function (
                string $column,
                mixed $values,
                PgArrayType|PgArrayTypeDefinition|null $type = null,
                array $extra = [],
            ) use ($prepend): int {
                /** @var EloquentBuilder<Model> $this */
                return $this->update(PgArrayQuery::concatenation($this->getQuery(), $column, $values, $type, $prepend) + $extra);
            });
        }
    }

    /**
     * The update values of an append / prepend, e.g. ["tags" => "tags" || '{new}'].
     *
     * @internal
     *
     * @return array<string, PgArrayConcatenation>
     */
    public static function concatenation(
        Builder $query,
        string $column,
        mixed $values,
        PgArrayType|PgArrayTypeDefinition|null $type,
        bool $prepend,
    ): array {
        self::grammar($query, $prepend ? 'pgArrayPrepend' : 'pgArrayAppend');

        return [$column => new PgArrayConcatenation($column, $values, $type, $prepend)];
    }

    private static function registerWhere(string $method, string $operator, bool $not, string $boolean): void
    {
        Builder::macro($method, function (
            string|Expression $column,
            mixed $values,
            PgArrayType|PgArrayTypeDefinition|null $type = null,
        ) use ($method, $operator, $not, $boolean): Builder {
            /** @var Builder $this */
            PgArrayQuery::grammar($this, $method);

            $literal = PgArrayLiteral::from($values, PgArrayQuery::delimiter($type));

            $this->wheres[] = [
                'type' => PgArrayQuery::WHERE_TYPE,
                'column' => $column,
                'operator' => $operator,
                'not' => $not,
                'cast' => $type,
                'boolean' => $boolean,
            ];

            return $this->addBinding($literal, 'where');
        });
    }

    /**
     * @internal
     *
     * @param  array{column: string|Expression, operator: string, not: bool, cast: PgArrayType|PgArrayTypeDefinition|null}  $where
     */
    public static function compileWhere(PostgresGrammar $grammar, array $where): string
    {
        $sql = $grammar->wrap($where['column']).' '.$where['operator'].' ?'.self::cast($where['cast']);

        return $where['not'] ? 'not ('.$sql.')' : $sql;
    }

    /**
     * @internal
     */
    public static function grammar(Builder $query, string $method): PostgresGrammar
    {
        $grammar = $query->getGrammar();

        if (! $grammar instanceof PostgresGrammar) {
            throw new RuntimeException(sprintf(
                '%s() is only supported by PostgreSQL, [%s] given.',
                $method,
                $grammar::class,
            ));
        }

        return $grammar;
    }

    /**
     * @internal
     *
     * Without a type the default ',' delimiter is used: geometry / geography
     * values need their type to be passed.
     */
    public static function delimiter(PgArrayType|PgArrayTypeDefinition|null $type): string
    {
        return match (true) {
            $type === null => PgArrayParser::DEFAULT_DELIMITER,
            $type instanceof PgArrayType => $type->delimiter(),
            default => $type->type->delimiter(),
        };
    }

    /**
     * @internal
     */
    public static function cast(PgArrayType|PgArrayTypeDefinition|null $type): string
    {
        if ($type === null) {
            return '';
        }

        if ($type instanceof PgArrayType) {
            $type = PgArrayTypeDefinition::of($type);
        }

        return '::'.$type->toArraySql();
    }
}
