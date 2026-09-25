<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Database\PgArrayColumnDefinition;
use AndreaColzani\PgArray\Database\PgArrayDefault;
use AndreaColzani\PgArray\Database\PgArrayTypeDefinition;
use AndreaColzani\PgArray\Enums\PgArrayType;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Stringable;

function pgArrayColumn(PgArrayType|PgArrayTypeDefinition $type): PgArrayColumnDefinition
{
    return new PgArrayColumnDefinition($type, ['type' => 'pgArray', 'name' => 'values']);
}

function pgArrayDefaultSql(PgArrayColumnDefinition $column): string
{
    $default = $column->get('default');

    expect($default)->toBeInstanceOf(PgArrayDefault::class);

    return $default->getValue(DB::connection()->getQueryGrammar());
}

it('renders the array type of a PgArrayType', function (PgArrayType $type): void {
    expect(pgArrayColumn($type)->toArraySql())->toBe($type->value.'[]');
})->with(PgArrayType::cases());

it('renders chained type modifiers', function (Closure $column, string $sql): void {
    expect($column()->toArraySql())->toBe($sql);
})->with([
    'char' => [fn () => pgArrayColumn(PgArrayType::Char)->length(2), 'char(2)[]'],
    'varchar' => [fn () => pgArrayColumn(PgArrayType::Varchar)->length(50), 'varchar(50)[]'],
    'decimal' => [fn () => pgArrayColumn(PgArrayType::Decimal)->precision(10, 2), 'decimal(10,2)[]'],
    'decimal without scale' => [fn () => pgArrayColumn(PgArrayType::Decimal)->precision(10), 'decimal(10)[]'],
    'numeric' => [fn () => pgArrayColumn(PgArrayType::Numeric)->precision(12, 4), 'numeric(12,4)[]'],
    'time' => [fn () => pgArrayColumn(PgArrayType::Time)->precision(0), 'time(0)[]'],
    'timetz' => [fn () => pgArrayColumn(PgArrayType::TimeTz)->precision(3), 'timetz(3)[]'],
    'timestamp' => [fn () => pgArrayColumn(PgArrayType::Timestamp)->precision(6), 'timestamp(6)[]'],
    'timestamptz' => [fn () => pgArrayColumn(PgArrayType::TimestampTz)->precision(6), 'timestamptz(6)[]'],
    'vector' => [fn () => pgArrayColumn(PgArrayType::Vector)->size(1536), 'vector(1536)[]'],
    'geometry subtype' => [fn () => pgArrayColumn(PgArrayType::Geometry)->subtype('Point'), 'geometry(Point)[]'],
    'geometry subtype and srid' => [fn () => pgArrayColumn(PgArrayType::Geometry)->subtype('point')->srid(4326), 'geometry(Point,4326)[]'],
    'geometry srid only' => [fn () => pgArrayColumn(PgArrayType::Geometry)->srid(3857), 'geometry(Geometry,3857)[]'],
    'geometry srid then subtype' => [fn () => pgArrayColumn(PgArrayType::Geometry)->srid(3857)->subtype('Polygon'), 'geometry(Polygon,3857)[]'],
    'geography default srid' => [fn () => pgArrayColumn(PgArrayType::Geography)->subtype('Point'), 'geography(Point,4326)[]'],
    'geography custom srid' => [fn () => pgArrayColumn(PgArrayType::Geography)->subtype('Point')->srid(4269), 'geography(Point,4269)[]'],
    'geography srid only' => [fn () => pgArrayColumn(PgArrayType::Geography)->srid(4326), 'geography(Geometry,4326)[]'],
    'two dimensions' => [fn () => pgArrayColumn(PgArrayType::Integer)->dimensions(2), 'integer[][]'],
    'modifiers and dimensions' => [fn () => pgArrayColumn(PgArrayType::Varchar)->dimensions(3)->length(10), 'varchar(10)[][][]'],
]);

it('accepts a type definition', function (): void {
    $column = pgArrayColumn(PgArrayTypeDefinition::varchar(50));

    expect($column->toArraySql())->toBe('varchar(50)[]')
        ->and($column->definition()->parameters)->toBe([50]);
});

it('lets chained modifiers override a type definition', function (): void {
    expect(pgArrayColumn(PgArrayTypeDefinition::varchar(50))->length(100)->toArraySql())->toBe('varchar(100)[]')
        ->and(pgArrayColumn(PgArrayTypeDefinition::geography('Point'))->srid(4269)->toArraySql())->toBe('geography(Point,4269)[]');
});

it('has one dimension and allows null elements by default', function (): void {
    $column = pgArrayColumn(PgArrayType::Text);

    expect($column->arrayDimensions())->toBe(1)
        ->and($column->forbidsNullElements())->toBeFalse()
        ->and($column->get('type'))->toBe('pgArray')
        ->and($column->get('name'))->toBe('values');
});

it('rejects modifiers not supported by the type', function (Closure $modifier, string $message): void {
    expect($modifier)->toThrow(InvalidArgumentException::class, $message);
})->with([
    'length' => [fn () => pgArrayColumn(PgArrayType::Text)->length(5), 'length() is only supported by char, varchar, [text] given.'],
    'precision' => [fn () => pgArrayColumn(PgArrayType::Integer)->precision(5), 'precision() is only supported by decimal, numeric, time, timetz, timestamp, timestamptz, [integer] given.'],
    'temporal scale' => [fn () => pgArrayColumn(PgArrayType::Timestamp)->precision(3, 2), 'A scale is only supported by decimal and numeric, [timestamp] given.'],
    'size' => [fn () => pgArrayColumn(PgArrayType::Real)->size(3), 'size() is only supported by vector, [real] given.'],
    'subtype' => [fn () => pgArrayColumn(PgArrayType::Text)->subtype('Point'), 'subtype() is only supported by geometry, geography, [text] given.'],
    'srid' => [fn () => pgArrayColumn(PgArrayType::Text)->srid(4326), 'srid() is only supported by geometry, geography, [text] given.'],
]);

it('validates modifier values', function (Closure $modifier, string $message): void {
    expect($modifier)->toThrow(InvalidArgumentException::class, $message);
})->with([
    'length' => [fn () => pgArrayColumn(PgArrayType::Varchar)->length(0), 'Invalid [varchar] type definition'],
    'scale' => [fn () => pgArrayColumn(PgArrayType::Decimal)->precision(2, 5), 'scale [5] cannot be greater than precision [2]'],
    'temporal precision' => [fn () => pgArrayColumn(PgArrayType::TimestampTz)->precision(7), 'Invalid [timestamptz] type definition'],
    'vector size' => [fn () => pgArrayColumn(PgArrayType::Vector)->size(16001), 'Invalid [vector] type definition'],
    'subtype' => [fn () => pgArrayColumn(PgArrayType::Geometry)->subtype('Circle'), 'unknown spatial subtype [Circle]'],
    'srid' => [fn () => pgArrayColumn(PgArrayType::Geometry)->srid(-1), 'SRID must be a non-negative integer'],
    'dimensions' => [fn () => pgArrayColumn(PgArrayType::Integer)->dimensions(0), 'Array dimensions must be greater than zero, [0] given.'],
]);

it('forbids null elements', function (): void {
    expect(pgArrayColumn(PgArrayType::Text)->withoutNullElements()->forbidsNullElements())->toBeTrue();
});

it('forbids null elements only in one-dimensional arrays', function (Closure $modifier): void {
    expect($modifier)->toThrow(
        InvalidArgumentException::class,
        'withoutNullElements() is only supported by one-dimensional arrays.',
    );
})->with([
    'dimensions first' => [fn () => pgArrayColumn(PgArrayType::Text)->dimensions(2)->withoutNullElements()],
    'dimensions last' => [fn () => pgArrayColumn(PgArrayType::Text)->withoutNullElements()->dimensions(2)],
]);

it('renders array defaults as array literals cast to the column type', function (mixed $value, string $sql): void {
    expect(pgArrayDefaultSql(pgArrayColumn(PgArrayType::Text)->default($value)))->toBe($sql);
})->with([
    'empty' => [[], "'{}'::text[]"],
    'strings' => [['a', 'b c', 'd"e'], "'{a,\"b c\",\"d\\\"e\"}'::text[]"],
    'quotes' => [["it's"], "'{it''s}'::text[]"],
    'nulls' => [['a', null, 'NULL'], "'{a,NULL,\"NULL\"}'::text[]"],
    'scalars' => [[1, 1.5, true, false], "'{1,1.5,t,f}'::text[]"],
    'enums' => [fn () => [[Status::Active, Priority::High], "'{active,".Priority::High->value."}'::text[]"]],
    'stringables' => [fn () => [[new Stringable('x y')], "'{\"x y\"}'::text[]"]],
    'keyed' => [['first' => 'a', 'second' => 'b'], "'{a,b}'::text[]"],
    'collection' => [fn () => [collect(['a', collect(['b'])]), "'{a,{b}}'::text[]"]],
]);

it('renders defaults with the final column type', function (): void {
    $column = pgArrayColumn(PgArrayType::Integer)
        ->default([[1, 2], [3, 4]])
        ->dimensions(2);

    expect(pgArrayDefaultSql($column))->toBe("'{{1,2},{3,4}}'::integer[][]");
});

it('keeps non-array defaults unchanged', function (mixed $value): void {
    expect(pgArrayColumn(PgArrayType::Text)->default($value)->get('default'))->toBe($value);
})->with([
    'literal' => ['{a,b}'],
    'null' => [null],
    'expression' => [fn () => new Expression("'{}'")],
]);

it('rejects unsupported default elements', function (): void {
    pgArrayColumn(PgArrayType::Text)->default([new stdClass]);
})->throws(InvalidArgumentException::class, 'Unsupported pgArray() default element of type [stdClass].');
