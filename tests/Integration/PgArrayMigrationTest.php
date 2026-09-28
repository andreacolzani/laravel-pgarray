<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\AsDecimalArray;
use AndreaColzani\PgArray\Casts\AsIntegerArray;
use AndreaColzani\PgArray\Casts\AsPgArray;
use AndreaColzani\PgArray\Database\PgArrayTypeDefinition;
use AndreaColzani\PgArray\Enums\PgArrayType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    pgsql();

    Schema::connection('pgsql')->dropIfExists('pgarray_migrations');
});

afterEach(function (): void {
    if (pgsqlFailure() === null) {
        Schema::connection('pgsql')->dropIfExists('pgarray_migrations');
    }
});

/**
 * @return object{type: string, not_null: bool, default: ?string}
 */
function pgsqlColumn(string $column): object
{
    /** @var object{type: string, not_null: bool, default: ?string} */
    return pgsql()->selectOne(
        'select format_type(a.atttypid, a.atttypmod) as type, a.attnotnull as not_null, pg_get_expr(d.adbin, d.adrelid) as default
        from pg_attribute a
        left join pg_attrdef d on d.adrelid = a.attrelid and d.adnum = a.attnum
        where a.attrelid = ?::regclass and a.attname = ? and not a.attisdropped',
        ['pgarray_migrations', $column],
    );
}

function pgsqlCreate(Closure $columns): void
{
    Schema::connection('pgsql')->create('pgarray_migrations', $columns);
}

it('creates array columns', function (Closure $column, string $type): void {
    pgsqlCreate($column);

    expect(pgsqlColumn('value')->type)->toBe($type);
})->with([
    'varchar' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Varchar), 'character varying[]'],
    'text' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Text), 'text[]'],
    'integer' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Integer), 'integer[]'],
    'bigint' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::BigInt), 'bigint[]'],
    'double precision' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::DoublePrecision), 'double precision[]'],
    'boolean' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Boolean), 'boolean[]'],
    'uuid' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Uuid), 'uuid[]'],
    'json' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Json), 'json[]'],
    'jsonb' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Jsonb), 'jsonb[]'],
    'inet' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Inet), 'inet[]'],
    'bytea' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Bytea), 'bytea[]'],
    'ulid' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Ulid), 'character(26)[]'],
    'char(2)' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Char)->length(2), 'character(2)[]'],
    'varchar(50)' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Varchar)->length(50), 'character varying(50)[]'],
    'decimal(10,2)' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Decimal)->precision(10, 2), 'numeric(10,2)[]'],
    'timestamp(6)' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Timestamp)->precision(6), 'timestamp(6) without time zone[]'],
    'timestamptz(3)' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::TimestampTz)->precision(3), 'timestamp(3) with time zone[]'],
    'type definition' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayTypeDefinition::numeric(12, 4)), 'numeric(12,4)[]'],
    // PostgreSQL does not store the number of dimensions.
    'multidimensional' => [fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Integer)->dimensions(2), 'integer[]'],
]);

it('creates vector array columns', function (): void {
    pgsqlExtension('vector');

    pgsqlCreate(fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Vector)->size(3));

    expect(pgsqlColumn('value')->type)->toBe('vector(3)[]');

    pgsql()->table('pgarray_migrations')->insert(['value' => '{"[1,2,3]","[4,5,6]"}']);

    expect(pgsql()->table('pgarray_migrations')->value('value'))->toBe('{"[1,2,3]","[4,5,6]"}');
});

it('creates spatial array columns', function (): void {
    pgsqlExtension('postgis');

    pgsqlCreate(function (Blueprint $table): void {
        $table->pgArray('value', PgArrayType::Geography)->subtype('Point');
        $table->pgArray('shapes', PgArrayType::Geometry)->srid(3857);
    });

    expect(pgsqlColumn('value')->type)->toBe('geography(Point,4326)[]')
        ->and(pgsqlColumn('shapes')->type)->toBe('geometry(Geometry,3857)[]');
});

it('creates nullable array columns', function (): void {
    pgsqlCreate(function (Blueprint $table): void {
        $table->pgArray('value', PgArrayType::Text);
        $table->pgArray('nullable', PgArrayType::Text)->nullable();
    });

    expect(pgsqlColumn('value')->not_null)->toBeTrue()
        ->and(pgsqlColumn('nullable')->not_null)->toBeFalse();

    pgsql()->table('pgarray_migrations')->insert(['value' => '{a}', 'nullable' => null]);

    pgsql()->table('pgarray_migrations')->insert(['value' => null]);
})->throws(QueryException::class, 'SQLSTATE[23502]'); // not_null_violation

it('creates array columns with defaults', function (): void {
    pgsqlCreate(function (Blueprint $table): void {
        $table->id();
        $table->pgArray('value', PgArrayType::Text)->default(['a', "it's", 'b c', null]);
        $table->pgArray('matrix', PgArrayType::Integer)->dimensions(2)->default([[1, 2], [3, 4]]);
        $table->pgArray('empty', PgArrayType::Varchar)->length(5)->default([]);
    });

    pgsql()->table('pgarray_migrations')->insert(['id' => 1]);

    expect((array) pgsql()->table('pgarray_migrations')->first())->toBe([
        'id' => 1,
        'value' => '{a,it\'s,"b c",NULL}',
        'matrix' => '{{1,2},{3,4}}',
        'empty' => '{}',
    ]);
});

it('forbids null elements', function (): void {
    pgsqlCreate(fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Text)->withoutNullElements()->nullable());

    pgsql()->table('pgarray_migrations')->insert([['value' => '{a,b}'], ['value' => '{}'], ['value' => null]]);

    expect(pgsql()->table('pgarray_migrations')->count())->toBe(3);

    pgsql()->table('pgarray_migrations')->insert(['value' => '{a,NULL}']);
})->throws(QueryException::class, 'SQLSTATE[23514]'); // check_violation

it('adds array columns to an existing table', function (): void {
    pgsqlCreate(fn (Blueprint $table) => $table->id());

    pgsql()->table('pgarray_migrations')->insert(['id' => 1]);

    Schema::connection('pgsql')->table('pgarray_migrations', function (Blueprint $table): void {
        $table->pgArray('value', PgArrayType::Text)->withoutNullElements()->default(['a']);
    });

    expect(pgsqlColumn('value')->type)->toBe('text[]')
        ->and(pgsql()->table('pgarray_migrations')->value('value'))->toBe('{a}');
});

it('changes array columns', function (): void {
    pgsqlCreate(fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Varchar)->length(5));

    pgsql()->table('pgarray_migrations')->insert(['value' => '{abc}']);

    Schema::connection('pgsql')->table('pgarray_migrations', function (Blueprint $table): void {
        $table->pgArray('value', PgArrayType::Varchar)->length(50)->nullable()->default(['x'])->change();
    });

    expect(pgsqlColumn('value'))->toEqual((object) [
        'type' => 'character varying(50)[]',
        'not_null' => false,
        'default' => '\'{x}\'::character varying(50)[]',
    ])->and(pgsql()->table('pgarray_migrations')->value('value'))->toBe('{abc}');
});

it('changes the element type of array columns with a casting expression', function (): void {
    pgsqlCreate(fn (Blueprint $table) => $table->pgArray('value', PgArrayType::Text));

    pgsql()->table('pgarray_migrations')->insert(['value' => '{1,2,3}']);

    Schema::connection('pgsql')->table('pgarray_migrations', function (Blueprint $table): void {
        $table->pgArray('value', PgArrayType::Integer)->using('value::integer[]')->change();
    });

    expect(pgsqlColumn('value')->type)->toBe('integer[]')
        ->and(pgsql()->table('pgarray_migrations')->value('value'))->toBe('{1,2,3}');
});

it('drops array columns', function (): void {
    pgsqlCreate(function (Blueprint $table): void {
        $table->id();
        $table->pgArray('value', PgArrayType::Text);
    });

    Schema::connection('pgsql')->table('pgarray_migrations', fn (Blueprint $table) => $table->dropColumn('value'));

    expect(Schema::connection('pgsql')->hasColumn('pgarray_migrations', 'value'))->toBeFalse();
});

it('round-trips values through eloquent casts', function (): void {
    pgsqlCreate(function (Blueprint $table): void {
        $table->id();
        $table->pgArray('tags', PgArrayType::Varchar)->length(20)->default([]);
        $table->pgArray('numbers', PgArrayType::Integer)->nullable();
        $table->pgArray('prices', PgArrayType::Decimal)->precision(10, 2)->nullable();
    });

    $model = new class extends Model
    {
        protected $connection = 'pgsql';

        protected $table = 'pgarray_migrations';

        public $timestamps = false;

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'tags' => AsPgArray::class,
                'numbers' => AsIntegerArray::class,
                'prices' => AsDecimalArray::class,
            ];
        }
    };

    $created = $model->newQuery()->create([
        'tags' => ['a', 'b c', 'd"e', 'it\'s'],
        'numbers' => [1, 2, 3],
        'prices' => ['10.50', '0.99'],
    ]);

    $fresh = $model->newQuery()->findOrFail($created->getKey());

    expect($fresh->tags)->toBe(['a', 'b c', 'd"e', 'it\'s'])
        ->and($fresh->numbers)->toBe([1, 2, 3])
        ->and($fresh->prices)->toBe(['10.50', '0.99']);

    $empty = $model->newQuery()->create([]);

    expect($model->newQuery()->findOrFail($empty->getKey())->tags)->toBe([]);
});
