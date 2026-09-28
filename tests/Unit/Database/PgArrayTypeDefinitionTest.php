<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Database\PgArrayTypeDefinition;
use AndreaColzani\PgArray\Enums\PgArrayType;

it('renders types without parameters', function (PgArrayType $type): void {
    $definition = PgArrayTypeDefinition::of($type);

    expect($definition->toSql())->toBe($type->value)
        ->and($definition->toArraySql())->toBe($type->value.'[]')
        ->and($definition->parameters)->toBe([]);
})->with(array_values(array_filter(PgArrayType::cases(), fn (PgArrayType $type): bool => $type !== PgArrayType::Ulid)));

it('renders ULIDs as char(26)', function (): void {
    expect(PgArrayTypeDefinition::of(PgArrayType::Ulid)->toArraySql())->toBe('char(26)[]');
});

it('rejects parameters for ULIDs', function (): void {
    new PgArrayTypeDefinition(PgArrayType::Ulid, [30]);
})->throws(InvalidArgumentException::class, 'Invalid [ulid] type definition: does not accept parameters.');

it('renders parameterized types', function (PgArrayTypeDefinition $definition, string $sql): void {
    expect($definition->toSql())->toBe($sql)
        ->and((string) $definition)->toBe($sql)
        ->and($definition->toArraySql())->toBe($sql.'[]');
})->with([
    'char' => [PgArrayTypeDefinition::char(2), 'char(2)'],
    'varchar' => [PgArrayTypeDefinition::varchar(50), 'varchar(50)'],
    'unbounded varchar' => [PgArrayTypeDefinition::varchar(), 'varchar'],
    'decimal' => [PgArrayTypeDefinition::decimal(10, 2), 'decimal(10,2)'],
    'decimal without scale' => [PgArrayTypeDefinition::decimal(10), 'decimal(10)'],
    'numeric' => [PgArrayTypeDefinition::numeric(12, 4), 'numeric(12,4)'],
    'unconstrained numeric' => [PgArrayTypeDefinition::numeric(), 'numeric'],
    'time' => [PgArrayTypeDefinition::time(0), 'time(0)'],
    'timetz' => [PgArrayTypeDefinition::timeTz(3), 'timetz(3)'],
    'timestamp' => [PgArrayTypeDefinition::timestamp(6), 'timestamp(6)'],
    'timestamptz' => [PgArrayTypeDefinition::timestampTz(3), 'timestamptz(3)'],
    'vector' => [PgArrayTypeDefinition::vector(1536), 'vector(1536)'],
    'geometry' => [PgArrayTypeDefinition::geometry('Point', 4326), 'geometry(Point,4326)'],
    'geometry without srid' => [PgArrayTypeDefinition::geometry('Polygon'), 'geometry(Polygon)'],
    'geometry with srid only' => [PgArrayTypeDefinition::geometry(srid: 3857), 'geometry(Geometry,3857)'],
    'geography' => [PgArrayTypeDefinition::geography('point', 4326), 'geography(Point,4326)'],
    'geography z' => [PgArrayTypeDefinition::geography('pointz', 4326), 'geography(PointZ,4326)'],
    'geography zm' => [PgArrayTypeDefinition::geography('MULTIPOLYGONZM'), 'geography(MultiPolygonZM)'],
    'generic constructor' => [new PgArrayTypeDefinition(PgArrayType::Timestamp, [6]), 'timestamp(6)'],
]);

it('keeps the type and parameters', function (): void {
    $definition = new PgArrayTypeDefinition(PgArrayType::Decimal, ['p' => 10, 's' => 2]);

    expect($definition->type)->toBe(PgArrayType::Decimal)
        ->and($definition->parameters)->toBe([10, 2]);
});

it('renders multidimensional arrays', function (): void {
    expect(PgArrayTypeDefinition::of(PgArrayType::Integer)->toArraySql(2))->toBe('integer[][]')
        ->and(PgArrayTypeDefinition::of(PgArrayType::DoublePrecision)->toArraySql())->toBe('double precision[]');
});

it('rejects invalid array dimensions', function (): void {
    PgArrayTypeDefinition::of(PgArrayType::Integer)->toArraySql(0);
})->throws(InvalidArgumentException::class, 'Array dimensions must be greater than zero, [0] given.');

it('accepts the parameter limits', function (PgArrayTypeDefinition $definition, string $sql): void {
    expect($definition->toSql())->toBe($sql);
})->with([
    'min length' => [PgArrayTypeDefinition::varchar(1), 'varchar(1)'],
    'max length' => [PgArrayTypeDefinition::char(10485760), 'char(10485760)'],
    'max precision' => [PgArrayTypeDefinition::numeric(1000, 1000), 'numeric(1000,1000)'],
    'zero scale' => [PgArrayTypeDefinition::decimal(5, 0), 'decimal(5,0)'],
    'max temporal precision' => [PgArrayTypeDefinition::timestampTz(6), 'timestamptz(6)'],
    'max vector dimensions' => [PgArrayTypeDefinition::vector(16000), 'vector(16000)'],
    'srid zero' => [PgArrayTypeDefinition::geometry('Point', 0), 'geometry(Point,0)'],
]);

it('rejects invalid parameters', function (Closure $definition, string $message): void {
    expect($definition)->toThrow(InvalidArgumentException::class, $message);
})->with([
    'zero length' => [fn () => PgArrayTypeDefinition::varchar(0), 'Invalid [varchar] type definition: length must be an integer between 1 and 10485760, [0] given.'],
    'length too long' => [fn () => PgArrayTypeDefinition::char(10485761), 'length must be an integer between 1 and 10485760'],
    'zero precision' => [fn () => PgArrayTypeDefinition::decimal(0), 'precision and scale must be an integer between 1 and 1000, [0] given'],
    'precision too high' => [fn () => PgArrayTypeDefinition::numeric(1001), 'between 1 and 1000'],
    'negative scale' => [fn () => PgArrayTypeDefinition::numeric(10, -1), 'between 0 and 1000, [-1] given'],
    'scale above precision' => [fn () => PgArrayTypeDefinition::decimal(2, 3), 'Invalid [decimal] type definition: scale [3] cannot be greater than precision [2].'],
    'scale without precision' => [fn () => PgArrayTypeDefinition::decimal(scale: 2), 'A numeric scale requires a precision.'],
    'temporal precision too high' => [fn () => PgArrayTypeDefinition::timestamp(7), 'Invalid [timestamp] type definition: precision must be an integer between 0 and 6, [7] given.'],
    'negative temporal precision' => [fn () => PgArrayTypeDefinition::time(-1), 'between 0 and 6'],
    'zero vector dimensions' => [fn () => PgArrayTypeDefinition::vector(0), 'Invalid [vector] type definition: dimensions must be an integer between 1 and 16000, [0] given.'],
    'too many vector dimensions' => [fn () => PgArrayTypeDefinition::vector(16001), 'between 1 and 16000'],
    'unknown spatial subtype' => [fn () => PgArrayTypeDefinition::geometry('Circle'), 'Invalid [geometry] type definition: unknown spatial subtype [Circle].'],
    'negative srid' => [fn () => PgArrayTypeDefinition::geography('Point', -1), 'SRID must be a non-negative integer, [-1] given.'],
    'parameters on a plain type' => [fn () => new PgArrayTypeDefinition(PgArrayType::Uuid, [1]), 'Invalid [uuid] type definition: does not accept parameters.'],
    'too many parameters' => [fn () => new PgArrayTypeDefinition(PgArrayType::Varchar, [1, 2]), 'accepts at most 1 parameter(s).'],
    'too many numeric parameters' => [fn () => new PgArrayTypeDefinition(PgArrayType::Numeric, [10, 2, 1]), 'accepts at most 2 parameter(s).'],
    'too many spatial parameters' => [fn () => new PgArrayTypeDefinition(PgArrayType::Geometry, ['Point', 4326, 1]), 'accepts at most 2 parameter(s).'],
    'string length' => [fn () => new PgArrayTypeDefinition(PgArrayType::Varchar, ['50']), 'length must be an integer between 1 and 10485760, [50] given.'],
    'integer spatial subtype' => [fn () => new PgArrayTypeDefinition(PgArrayType::Geometry, [1]), 'unknown spatial subtype [1].'],
    'string srid' => [fn () => new PgArrayTypeDefinition(PgArrayType::Geography, ['Point', '4326']), 'SRID must be a non-negative integer'],
]);
