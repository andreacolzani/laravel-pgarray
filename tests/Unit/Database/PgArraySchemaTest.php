<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Database\PgArrayColumnDefinition;
use AndreaColzani\PgArray\Database\PgArrayTypeDefinition;
use AndreaColzani\PgArray\Enums\PgArrayType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Compiles schema changes on the pgsql connection in pretend mode: no server is needed.
 *
 * @return list<string>
 */
function pgsqlSchemaSql(Closure $callback): array
{
    return array_column(DB::connection('pgsql')->pretend(
        fn () => $callback(Schema::connection('pgsql')),
    ), 'query');
}

function pgsqlCreateSql(Closure $columns): string
{
    return pgsqlSchemaSql(
        fn ($schema) => $schema->create('posts', $columns),
    )[0];
}

it('returns a pgArray column definition', function (): void {
    pgsqlSchemaSql(fn ($schema) => $schema->create('posts', function (Blueprint $table): void {
        expect($table->pgArray('tags', PgArrayType::Text))->toBeInstanceOf(PgArrayColumnDefinition::class);
    }));
});

it('creates array columns', function (Closure $column, string $sql): void {
    expect(pgsqlCreateSql($column))->toBe('create table "posts" ('.$sql.')');
})->with([
    'varchar' => [fn (Blueprint $table) => $table->pgArray('tags', PgArrayType::Varchar), '"tags" varchar[] not null'],
    'text' => [fn (Blueprint $table) => $table->pgArray('tags', PgArrayType::Text), '"tags" text[] not null'],
    'integer' => [fn (Blueprint $table) => $table->pgArray('numbers', PgArrayType::Integer), '"numbers" integer[] not null'],
    'double precision' => [fn (Blueprint $table) => $table->pgArray('numbers', PgArrayType::DoublePrecision), '"numbers" double precision[] not null'],
    'uuid' => [fn (Blueprint $table) => $table->pgArray('ids', PgArrayType::Uuid), '"ids" uuid[] not null'],
    'json' => [fn (Blueprint $table) => $table->pgArray('data', PgArrayType::Json), '"data" json[] not null'],
    'jsonb' => [fn (Blueprint $table) => $table->pgArray('data', PgArrayType::Jsonb), '"data" jsonb[] not null'],
    'varchar(50)' => [fn (Blueprint $table) => $table->pgArray('codes', PgArrayType::Varchar)->length(50), '"codes" varchar(50)[] not null'],
    'decimal(10,2)' => [fn (Blueprint $table) => $table->pgArray('prices', PgArrayType::Decimal)->precision(10, 2), '"prices" decimal(10,2)[] not null'],
    'timestamp(6)' => [fn (Blueprint $table) => $table->pgArray('seen_at', PgArrayType::Timestamp)->precision(6), '"seen_at" timestamp(6)[] not null'],
    'vector(1536)' => [fn (Blueprint $table) => $table->pgArray('embeddings', PgArrayType::Vector)->size(1536), '"embeddings" vector(1536)[] not null'],
    'geography' => [fn (Blueprint $table) => $table->pgArray('places', PgArrayType::Geography)->subtype('Point'), '"places" geography(Point,4326)[] not null'],
    'type definition' => [fn (Blueprint $table) => $table->pgArray('codes', PgArrayTypeDefinition::varchar(50)), '"codes" varchar(50)[] not null'],
    'multidimensional' => [fn (Blueprint $table) => $table->pgArray('matrix', PgArrayType::Integer)->dimensions(2), '"matrix" integer[][] not null'],
    'nullable' => [fn (Blueprint $table) => $table->pgArray('tags', PgArrayType::Text)->nullable(), '"tags" text[] null'],
    'array default' => [fn (Blueprint $table) => $table->pgArray('tags', PgArrayType::Text)->default(['a', "b'c"]), '"tags" text[] not null default \'{a,b\'\'c}\'::text[]'],
    'empty default' => [fn (Blueprint $table) => $table->pgArray('tags', PgArrayType::Text)->default([]), '"tags" text[] not null default \'{}\'::text[]'],
    'geometry default' => [fn (Blueprint $table) => $table->pgArray('shapes', PgArrayType::Geometry)->default(['POINT(1 2)', 'POINT(3 4)']), '"shapes" geometry[] not null default \'{"POINT(1 2)":"POINT(3 4)"}\'::geometry[]'],
    'literal default' => [fn (Blueprint $table) => $table->pgArray('tags', PgArrayType::Text)->default('{a,b}'), '"tags" text[] not null default \'{a,b}\''],
    'without null elements' => [
        fn (Blueprint $table) => $table->pgArray('tags', PgArrayType::Text)->withoutNullElements()->nullable(),
        '"tags" text[] check (array_position("tags", NULL) is null) null',
    ],
]);

it('creates several array columns', function (): void {
    $sql = pgsqlCreateSql(function (Blueprint $table): void {
        $table->id();
        $table->pgArray('tags', PgArrayType::Text)->default([]);
        $table->pgArray('matrix', PgArrayType::Integer)->dimensions(2)->nullable()->default([[1, 2], [3, 4]]);
    });

    expect($sql)->toBe(
        'create table "posts" ("id" bigserial not null primary key, '
        .'"tags" text[] not null default \'{}\'::text[], '
        .'"matrix" integer[][] null default \'{{1,2},{3,4}}\'::integer[][])',
    );
});

it('adds array columns to an existing table', function (): void {
    $queries = pgsqlSchemaSql(fn ($schema) => $schema->table('posts', function (Blueprint $table): void {
        $table->pgArray('tags', PgArrayType::Text)->nullable();
        $table->pgArray('codes', PgArrayType::Varchar)->length(20)->withoutNullElements()->default(['a']);
    }));

    expect($queries)->toBe([
        'alter table "posts" add column "tags" text[] null',
        'alter table "posts" add column "codes" varchar(20)[] check (array_position("codes", NULL) is null) not null default \'{a}\'::varchar(20)[]',
    ]);
});

it('changes array columns', function (): void {
    $queries = pgsqlSchemaSql(fn ($schema) => $schema->table('posts', function (Blueprint $table): void {
        $table->pgArray('prices', PgArrayType::Decimal)->precision(12, 2)->nullable()->change();
    }));

    expect($queries[0])->toBe(
        'alter table "posts" alter column "prices" type decimal(12,2)[], '
        .'alter column "prices" drop not null, '
        .'alter column "prices" drop default, '
        .'alter column "prices" drop identity if exists',
    );
});

it('changes array columns with a casting expression and a default', function (): void {
    $queries = pgsqlSchemaSql(fn ($schema) => $schema->table('posts', function (Blueprint $table): void {
        $table->pgArray('numbers', PgArrayType::Integer)->using('numbers::integer[]')->default([1])->change();
    }));

    expect($queries[0])->toBe(
        'alter table "posts" alter column "numbers" type integer[] using numbers::integer[], '
        .'alter column "numbers" set not null, '
        .'alter column "numbers" set default \'{1}\'::integer[], '
        .'alter column "numbers" drop identity if exists',
    );
});

it('changes array columns with a casting expression object', function (): void {
    $queries = pgsqlSchemaSql(fn ($schema) => $schema->table('posts', function (Blueprint $table): void {
        $table->pgArray('numbers', PgArrayType::BigInt)->using(DB::raw('"numbers"::bigint[]'))->nullable()->change();
    }));

    expect($queries[0])->toStartWith('alter table "posts" alter column "numbers" type bigint[] using "numbers"::bigint[], ');
});

it('ignores the casting expression when not changing a column', function (): void {
    $queries = pgsqlSchemaSql(fn ($schema) => $schema->table('posts', function (Blueprint $table): void {
        $table->pgArray('numbers', PgArrayType::Integer)->using('numbers::integer[]')->nullable();
    }));

    expect($queries)->toBe(['alter table "posts" add column "numbers" integer[] null']);
});

it('does not change a column with both a casting expression and a collation', function (): void {
    pgsqlSchemaSql(fn ($schema) => $schema->table('posts', function (Blueprint $table): void {
        $table->pgArray('tags', PgArrayType::Text)->using('tags::text[]')->collation('C')->change();
    }));
})->throws(RuntimeException::class, 'using() cannot be combined with collation()');

it('does not change a column forbidding null elements', function (): void {
    pgsqlSchemaSql(fn ($schema) => $schema->table('posts', function (Blueprint $table): void {
        $table->pgArray('tags', PgArrayType::Text)->withoutNullElements()->change();
    }));
})->throws(RuntimeException::class, 'withoutNullElements() cannot be used when changing a column');

it('drops array columns', function (): void {
    $queries = pgsqlSchemaSql(fn ($schema) => $schema->table('posts', function (Blueprint $table): void {
        $table->dropColumn('tags');
    }));

    expect($queries)->toBe(['alter table "posts" drop column "tags"']);
});

it('rejects other database drivers', function (): void {
    DB::connection()->pretend(fn () => Schema::create('posts', function (Blueprint $table): void {
        $table->pgArray('tags', PgArrayType::Text);
    }));
})->throws(RuntimeException::class, 'pgArray() columns are only supported by PostgreSQL');

it('rejects array columns declared without the helper', function (): void {
    pgsqlSchemaSql(fn ($schema) => $schema->create('posts', function (Blueprint $table): void {
        $table->addColumn('pgArray', 'tags');
    }));
})->throws(RuntimeException::class, 'pgArray() columns must be declared with the pgArray() helper.');
