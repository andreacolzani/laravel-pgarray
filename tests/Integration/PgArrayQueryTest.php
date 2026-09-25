<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\AsIntegerArray;
use AndreaColzani\PgArray\Enums\PgArrayType;
use AndreaColzani\PgArray\Support\PgArrayParser;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

beforeEach(function (): void {
    pgsql();

    Schema::connection('pgsql')->dropIfExists('pgarray_queries');

    Schema::connection('pgsql')->create('pgarray_queries', function (Blueprint $table): void {
        $table->string('name')->primary();
        $table->pgArray('tags', PgArrayType::Text)->nullable();
        $table->pgArray('numbers', PgArrayType::Integer)->nullable();
        $table->pgArray('ids', PgArrayType::Uuid)->nullable();
        $table->pgArray('matrix', PgArrayType::Integer)->dimensions(2)->nullable();
        $table->timestamps();
    });

    pgsqlQueryTable()->insert([
        ['name' => 'php', 'tags' => '{php,laravel}', 'numbers' => '{1,2,3}', 'ids' => '{0b8e4f3a-5d3c-4d7e-9f1a-2b3c4d5e6f70}', 'matrix' => '{{1,2},{3,4}}'],
        ['name' => 'js', 'tags' => '{js,vue}', 'numbers' => '{3,4}', 'ids' => '{5c1d7b4e-0f7a-4c2b-8e3d-9a8b7c6d5e4f}', 'matrix' => '{{5,6}}'],
        ['name' => 'empty', 'tags' => '{}', 'numbers' => '{}', 'ids' => '{}', 'matrix' => '{}'],
        ['name' => 'null', 'tags' => null, 'numbers' => null, 'ids' => null, 'matrix' => null],
        ['name' => 'special', 'tags' => '{"a,b","say \"hi\"","back\\\\slash","it\'s","?"}', 'numbers' => '{}', 'ids' => '{}', 'matrix' => '{}'],
    ]);
});

afterEach(function (): void {
    if (pgsqlFailure() === null) {
        Schema::connection('pgsql')->dropIfExists('pgarray_queries');
    }
});

function pgsqlQueryTable(): Builder
{
    return pgsql()->table('pgarray_queries');
}

/**
 * @return list<string>
 */
function pgsqlNames(Builder $query): array
{
    /** @var list<string> */
    return $query->orderBy('name')->pluck('name')->all();
}

/**
 * @return array<int, mixed>
 */
function pgsqlArray(string $name, string $column): array
{
    /** @var string $value */
    $value = pgsqlQueryTable()->where('name', $name)->value($column);

    return PgArrayParser::parse($value);
}

it('filters with array operators', function (Closure $where, array $names): void {
    expect(pgsqlNames($where(pgsqlQueryTable())))->toBe($names);
})->with([
    'contains' => [fn (Builder $q) => $q->wherePgArrayContains('tags', ['laravel', 'php']), ['php']],
    'contains scalar' => [fn (Builder $q) => $q->wherePgArrayContains('tags', 'vue'), ['js']],
    'contains empty' => [fn (Builder $q) => $q->wherePgArrayContains('tags', []), ['empty', 'js', 'php', 'special']],
    'doesnt contain' => [fn (Builder $q) => $q->wherePgArrayDoesntContain('tags', ['php']), ['empty', 'js', 'special']],
    'contained by' => [fn (Builder $q) => $q->wherePgArrayContainedBy('tags', ['php', 'laravel', 'go']), ['empty', 'php']],
    'contained by empty' => [fn (Builder $q) => $q->wherePgArrayContainedBy('tags', []), ['empty']],
    'not contained by' => [fn (Builder $q) => $q->wherePgArrayNotContainedBy('tags', ['php', 'laravel']), ['js', 'special']],
    'overlaps' => [fn (Builder $q) => $q->wherePgArrayOverlaps('tags', collect(['vue', 'php'])), ['js', 'php']],
    'overlaps empty' => [fn (Builder $q) => $q->wherePgArrayOverlaps('tags', []), []],
    'doesnt overlap' => [fn (Builder $q) => $q->wherePgArrayDoesntOverlap('tags', ['vue']), ['empty', 'php', 'special']],
    'or' => [fn (Builder $q) => $q->wherePgArrayContains('tags', ['php'])->orWherePgArrayContains('tags', ['vue']), ['js', 'php']],
    'or negated' => [fn (Builder $q) => $q->where('name', 'php')->orWherePgArrayDoesntOverlap('tags', ['vue', 'php']), ['empty', 'php', 'special']],
    'integers' => [fn (Builder $q) => $q->wherePgArrayOverlaps('numbers', [3]), ['js', 'php']],
    'integers cast' => [fn (Builder $q) => $q->wherePgArrayContains('numbers', [1, 2], PgArrayType::Integer), ['php']],
    'uuids' => [fn (Builder $q) => $q->wherePgArrayContains('ids', [Uuid::fromString('5c1d7b4e-0f7a-4c2b-8e3d-9a8b7c6d5e4f')]), ['js']],
    'multidimensional contains' => [fn (Builder $q) => $q->wherePgArrayContains('matrix', [[4, 1]]), ['php']],
    'multidimensional contained by' => [fn (Builder $q) => $q->wherePgArrayContainedBy('matrix', [[1, 2], [3, 4]]), ['empty', 'php', 'special']],
    'special characters' => [fn (Builder $q) => $q->wherePgArrayContains('tags', ['a,b', 'say "hi"', 'back\\slash', "it's", '?']), ['special']],
    'enums' => [fn (Builder $q) => $q->wherePgArrayOverlaps('tags', [Status::Active]), []],
    'expression column' => [fn (Builder $q) => $q->wherePgArrayContains(pgsql()->raw('array_append(tags, \'x\')'), ['x', 'vue']), ['js']],
]);

it('appends and prepends values', function (): void {
    expect(pgsqlQueryTable()->where('name', 'php')->pgArrayAppend('tags', ['go', "it's"]))->toBe(1)
        ->and(pgsqlQueryTable()->where('name', 'php')->pgArrayPrepend('tags', 'first'))->toBe(1)
        ->and(pgsqlQueryTable()->where('name', 'null')->pgArrayAppend('tags', ['a']))->toBe(1)
        ->and(pgsqlQueryTable()->where('name', 'empty')->pgArrayAppend('numbers', [], PgArrayType::Integer))->toBe(1)
        ->and(pgsqlQueryTable()->where('name', 'js')->pgArrayAppend('matrix', [[7, 8]]))->toBe(1)
        ->and(pgsqlQueryTable()->where('name', 'special')->pgArrayAppend('tags', ['back\\slash "quoted"', '?'], null, ['numbers' => '{9}']))->toBe(1)
        ->and(pgsqlArray('php', 'tags'))->toBe(['first', 'php', 'laravel', 'go', "it's"])
        ->and(pgsqlArray('null', 'tags'))->toBe(['a'])
        ->and(pgsqlArray('special', 'tags'))->toBe(['a,b', 'say "hi"', 'back\slash', "it's", '?', 'back\slash "quoted"', '?'])
        ->and(pgsqlQueryTable()->where('name', 'empty')->value('numbers'))->toBe('{}')
        ->and(pgsqlQueryTable()->where('name', 'special')->value('numbers'))->toBe('{9}')
        ->and(pgsqlQueryTable()->where('name', 'js')->value('matrix'))->toBe('{{5,6},{7,8}}');
});

it('touches updated_at when appending through Eloquent', function (): void {
    Carbon::setTestNow('2026-09-25 10:00:00');

    $model = new class extends Model
    {
        protected $connection = 'pgsql';

        protected $table = 'pgarray_queries';

        protected $primaryKey = 'name';

        protected $keyType = 'string';

        public $incrementing = false;

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'numbers' => AsIntegerArray::class,
            ];
        }
    };

    expect($model->newQuery()->whereKey('js')->pgArrayPrepend('numbers', [1, 2]))->toBe(1)
        ->and($model->newQuery()->wherePgArrayContains('numbers', [1, 4])->pluck('name')->all())->toBe(['js']);

    $js = $model->newQuery()->findOrFail('js');

    expect($js->numbers)->toBe([1, 2, 3, 4])
        ->and($js->updated_at?->toDateTimeString())->toBe('2026-09-25 10:00:00');
});
