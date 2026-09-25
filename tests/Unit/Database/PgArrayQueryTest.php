<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Database\PgArrayTypeDefinition;
use AndreaColzani\PgArray\Enums\PgArrayType;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use AndreaColzani\PgArray\Exceptions\UnsupportedDriverException;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * A query on the pgsql connection: compiling it needs no server.
 */
function pgsqlQuery(): Builder
{
    return DB::connection('pgsql')->table('posts');
}

/**
 * Replaces the PDO of the pgsql connection, so values can be escaped
 * without a server. Queries are run in pretend mode, with the bindings
 * substituted.
 *
 * @return list<array{query: string, bindings: array<int, mixed>}>
 */
function pgsqlPretend(Closure $callback): array
{
    $pdo = Mockery::mock(PDO::class);
    $pdo->shouldReceive('quote')->andReturnUsing(fn (string $value): string => "'".str_replace("'", "''", $value)."'");

    $connection = DB::connection('pgsql');
    $connection->setPdo($pdo)->setReadPdo($pdo);

    /** @var list<array{query: string, bindings: array<int, mixed>}> */
    return $connection->pretend($callback);
}

it('compiles array operators', function (string $method, string $sql): void {
    $query = pgsqlQuery()->where('id', 1)->{$method}('tags', ['php', 'laravel']);

    expect($query->toSql())->toBe('select * from "posts" where "id" = ? '.$sql)
        ->and($query->getBindings())->toBe([1, '{php,laravel}']);
})->with([
    ['wherePgArrayContains', 'and "tags" @> ?'],
    ['orWherePgArrayContains', 'or "tags" @> ?'],
    ['wherePgArrayDoesntContain', 'and not ("tags" @> ?)'],
    ['orWherePgArrayDoesntContain', 'or not ("tags" @> ?)'],
    ['wherePgArrayContainedBy', 'and "tags" <@ ?'],
    ['orWherePgArrayContainedBy', 'or "tags" <@ ?'],
    ['wherePgArrayNotContainedBy', 'and not ("tags" <@ ?)'],
    ['orWherePgArrayNotContainedBy', 'or not ("tags" <@ ?)'],
    ['wherePgArrayOverlaps', 'and "tags" && ?'],
    ['orWherePgArrayOverlaps', 'or "tags" && ?'],
    ['wherePgArrayDoesntOverlap', 'and not ("tags" && ?)'],
    ['orWherePgArrayDoesntOverlap', 'or not ("tags" && ?)'],
]);

it('binds values as array literals', function (mixed $values, string $binding): void {
    expect(pgsqlQuery()->wherePgArrayContains('tags', $values)->getBindings())->toBe([$binding]);
})->with([
    'strings' => [['php', 'laravel'], '{php,laravel}'],
    'numbers' => [[1, 2.5], '{1,2.5}'],
    'uuids' => [[Uuid::fromString('0b8e4f3a-5d3c-4d7e-9f1a-2b3c4d5e6f70'), '5c1d7b4e-0f7a-4c2b-8e3d-9a8b7c6d5e4f'], '{0b8e4f3a-5d3c-4d7e-9f1a-2b3c4d5e6f70,5c1d7b4e-0f7a-4c2b-8e3d-9a8b7c6d5e4f}'],
    'enums' => [[Status::Active], '{active}'],
    'collection' => [collect(['a', 'b']), '{a,b}'],
    'empty' => [[], '{}'],
    'multidimensional' => [[[1, 2], [3, 4]], '{{1,2},{3,4}}'],
    'scalar' => ['php', '{php}'],
    'special characters' => [['a,b', "it's"], '{"a,b",it\'s}'],
]);

it('casts values to the given type', function (PgArrayType|PgArrayTypeDefinition $type, string $sql): void {
    expect(pgsqlQuery()->wherePgArrayOverlaps('ids', [1, 2], $type)->toSql())
        ->toBe('select * from "posts" where "ids" && ?'.$sql);
})->with([
    'type' => [PgArrayType::BigInt, '::bigint[]'],
    'type definition' => [PgArrayTypeDefinition::varchar(50), '::varchar(50)[]'],
]);

it('wraps qualified and expression columns', function (): void {
    $query = pgsqlQuery()
        ->wherePgArrayContains('posts.tags', ['a'])
        ->wherePgArrayOverlaps(DB::raw('array_remove(tags, NULL)'), ['b']);

    expect($query->toSql())->toBe('select * from "posts" where "posts"."tags" @> ? and array_remove(tags, NULL) && ?');
});

it('supports nested wheres', function (): void {
    $query = pgsqlQuery()->where(fn (Builder $query) => $query
        ->wherePgArrayContains('tags', ['a'])
        ->orWherePgArrayDoesntOverlap('tags', ['b']));

    expect($query->toSql())->toBe('select * from "posts" where ("tags" @> ? or not ("tags" && ?))')
        ->and($query->getBindings())->toBe(['{a}', '{b}']);
});

it('rejects null values', function (): void {
    pgsqlQuery()->wherePgArrayContains('tags', null);
})->throws(InvalidValueException::class, 'A PostgreSQL array value cannot be null.');

it('is only supported by PostgreSQL', function (): void {
    DB::connection('testing')->table('posts')->wherePgArrayContains('tags', ['a']);
})->throws(UnsupportedDriverException::class, 'wherePgArrayContains() requires PostgreSQL, [Illuminate\Database\Query\Grammars\SQLiteGrammar] given.');

it('appends and prepends values', function (string $method, mixed $values, array $extra, string $sql): void {
    $queries = pgsqlPretend(fn () => pgsqlQuery()->where('id', 1)->{$method}('tags', $values, null, $extra));

    expect($queries)->toHaveCount(1)
        ->and($queries[0]['query'])->toBe($sql);
})->with([
    'append' => ['pgArrayAppend', ['a', "it's"], [], 'update "posts" set "tags" = "tags" || \'{a,it\'\'s}\' where "id" = 1'],
    'prepend' => ['pgArrayPrepend', collect(['a']), [], 'update "posts" set "tags" = \'{a}\' || "tags" where "id" = 1'],
    'scalar' => ['pgArrayAppend', 'a', [], 'update "posts" set "tags" = "tags" || \'{a}\' where "id" = 1'],
    'multidimensional' => ['pgArrayAppend', [[5, 6]], [], 'update "posts" set "tags" = "tags" || \'{{5,6}}\' where "id" = 1'],
    'extra columns' => ['pgArrayAppend', ['a'], ['title' => 'x'], 'update "posts" set "tags" = "tags" || \'{a}\', "title" = \'x\' where "id" = 1'],
]);

it('casts appended values to the given type', function (): void {
    $queries = pgsqlPretend(fn () => pgsqlQuery()->pgArrayPrepend('ids', [1], PgArrayType::BigInt));

    expect($queries[0]['query'])->toBe('update "posts" set "ids" = \'{1}\'::bigint[] || "ids"');
});

it('only appends with PostgreSQL', function (): void {
    DB::connection('testing')->table('posts')->pgArrayAppend('tags', ['a']);
})->throws(UnsupportedDriverException::class, 'pgArrayAppend() requires PostgreSQL, [Illuminate\Database\Query\Grammars\SQLiteGrammar] given.');

it('does not add a where clause for invalid values', function (): void {
    $query = pgsqlQuery();

    expect(fn () => $query->wherePgArrayContains('tags', [new stdClass]))->toThrow(InvalidValueException::class)
        ->and($query->wheres)->toBe([])
        ->and($query->getBindings())->toBe([]);
});

it('uses the delimiter of the given type', function (): void {
    $query = pgsqlQuery()->wherePgArrayOverlaps('places', ['POINT(1 2)', 'POINT(3 4)'], PgArrayType::Geography);

    expect($query->getBindings())->toBe(['{"POINT(1 2)":"POINT(3 4)"}'])
        ->and(pgsqlPretend(fn () => pgsqlQuery()->pgArrayAppend('shapes', ['POINT(1 2)', 'POINT(3 4)'], PgArrayTypeDefinition::geometry('Point')))[0]['query'])
        ->toBe('update "posts" set "shapes" = "shapes" || \'{"POINT(1 2)":"POINT(3 4)"}\'::geometry(Point)[]');
});
