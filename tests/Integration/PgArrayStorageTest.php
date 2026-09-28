<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Tests\Fixtures\Address;
use AndreaColzani\PgArray\Tests\Fixtures\Contact;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;
use AndreaColzani\PgArray\Tests\Models\PgsqlModel;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\Ulid;

beforeEach(function (): void {
    pgsql();

    PgsqlModel::createTable();
});

afterEach(function (): void {
    if (pgsqlFailure() === null) {
        PgsqlModel::dropTable();
    }
});

/**
 * The column as stored by PostgreSQL (array output format).
 */
function pgsqlRaw(PgsqlModel $model, string $column): mixed
{
    return pgsql()->table(PgsqlModel::TABLE)->where('id', $model->getKey())->value($column);
}

describe('storage', function (): void {
    it('inserts and selects arrays', function (): void {
        $model = PgsqlModel::query()->create(['integers' => [1, 2, 3], 'texts' => ['php', 'laravel']]);

        $fresh = PgsqlModel::query()->findOrFail($model->getKey());

        expect(pgsqlRaw($model, 'integers'))->toBe('{1,2,3}')
            ->and(pgsqlRaw($model, 'texts'))->toBe('{php,laravel}')
            ->and($fresh->integers)->toBe([1, 2, 3])
            ->and($fresh->texts)->toBe(['php', 'laravel']);
    });

    it('updates arrays', function (): void {
        $model = PgsqlModel::query()->create(['integers' => [1, 2, 3]]);

        $model->integers = [...$model->integers, 4];
        $model->save();

        PgsqlModel::query()->whereKey($model->getKey())->update(['texts' => '{a,b}']);

        $fresh = $model->fresh();

        expect($fresh?->integers)->toBe([1, 2, 3, 4])
            ->and($fresh?->texts)->toBe(['a', 'b']);
    });

    it('only updates arrays when they change', function (): void {
        $model = PgsqlModel::query()->create(['integers' => [1, 2, 3]]);

        $model->integers = [1, 2, 3];

        expect($model->isDirty('integers'))->toBeFalse();

        $model->integers = [3, 2, 1];

        expect($model->isDirty('integers'))->toBeTrue();
    });

    it('stores null arrays', function (): void {
        $model = PgsqlModel::query()->create(['integers' => null, 'texts' => ['a']]);

        $model->update(['texts' => null]);

        $fresh = $model->fresh();

        expect(pgsqlRaw($model, 'integers'))->toBeNull()
            ->and(pgsqlRaw($model, 'texts'))->toBeNull()
            ->and($fresh?->integers)->toBeNull()
            ->and($fresh?->texts)->toBeNull()
            ->and($fresh?->smallints)->toBeNull();
    });

    it('stores empty arrays', function (): void {
        $model = PgsqlModel::query()->create(['integers' => [], 'texts' => [], 'smallints' => collect(), 'matrix' => []]);

        $fresh = $model->fresh();

        expect(pgsqlRaw($model, 'integers'))->toBe('{}')
            ->and(pgsqlRaw($model, 'matrix'))->toBe('{}')
            ->and($fresh?->integers)->toBe([])
            ->and($fresh?->texts)->toBe([])
            ->and($fresh?->smallints)->toBeInstanceOf(Collection::class)->toBeEmpty()
            ->and($fresh?->matrix)->toBe([]);
    });
});

describe('types', function (): void {
    it('round trips values', function (string $column, mixed $value, string $raw, mixed $expected): void {
        $model = PgsqlModel::query()->create([$column => $value]);

        expect(pgsqlRaw($model, $column))->toBe($raw)
            ->and(PgsqlModel::query()->findOrFail($model->getKey())->getAttribute($column))->toBe($expected);
    })->with([
        'integer' => ['integers', [-2147483648, 0, 2147483647], '{-2147483648,0,2147483647}', [-2147483648, 0, 2147483647]],
        'bigint' => ['bigints', [PHP_INT_MIN, PHP_INT_MAX], '{'.PHP_INT_MIN.','.PHP_INT_MAX.'}', [PHP_INT_MIN, PHP_INT_MAX]],
        'decimal' => ['decimals', ['12.3', '-0.05', 7], '{12.30,-0.05,7.00}', ['12.30', '-0.05', '7.00']],
        'numeric' => ['numerics', ['123456789.123456789', '1e-20'], '{123456789.123456789,0.00000000000000000001}', ['123456789.123456789', '0.00000000000000000001']],
        'float' => ['floats', [0.1, -1.5, 1.0e-300], '{0.1,-1.5,1e-300}', [0.1, -1.5, 1.0e-300]],
        'double' => ['doubles', [0.1 + 0.2, M_PI], '{0.30000000000000004,3.141592653589793}', [0.1 + 0.2, M_PI]],
        // real is single-precision: values are rounded to ~6 significant digits.
        'real' => ['reals', [0.1, 1.5, 3.14159265], '{0.1,1.5,3.1415927}', [0.1, 1.5, 3.1415927]],
        'boolean' => ['booleans', [true, false, null], '{t,f,NULL}', [true, false, null]],
        // char(n) is blank-padded by PostgreSQL.
        'char' => ['chars', ['ab', 'abc'], '{"ab ",abc}', ['ab ', 'abc']],
        'varchar' => ['varchars', ['php', 'laravel'], '{php,laravel}', ['php', 'laravel']],
        'text' => ['texts', ['php', str_repeat('x', 10000)], '{php,'.str_repeat('x', 10000).'}', ['php', str_repeat('x', 10000)]],
        'time' => ['times', ['14:30:00.123456', '23:59:59'], '{14:30:00.123456,23:59:59}', ['14:30:00.123456', '23:59:59']],
        'timetz' => ['timetzs', ['14:30:00+05:30', '08:00:00-02'], '{14:30:00+05:30,08:00:00-02}', ['14:30:00+05:30', '08:00:00-02']],
    ]);

    it('round trips integers into smallint collections', function (): void {
        $model = PgsqlModel::query()->create(['smallints' => collect([-32768, 32767])]);

        expect(pgsqlRaw($model, 'smallints'))->toBe('{-32768,32767}')
            ->and($model->fresh()?->smallints)->toEqual(collect([-32768, 32767]));
    });

    it('round trips uuids', function (): void {
        $model = PgsqlModel::query()->create(['uuids' => [
            Uuid::fromString('0b8e4f3a-5d3c-4d7e-9f1a-2b3c4d5e6f70'),
            '5C1D7B4E-0F7A-4C2B-8E3D-9A8B7C6D5E4F',
        ]]);

        /** @var list<UuidInterface> $uuids */
        $uuids = $model->fresh()?->uuids;

        expect(pgsqlRaw($model, 'uuids'))->toBe('{0b8e4f3a-5d3c-4d7e-9f1a-2b3c4d5e6f70,5c1d7b4e-0f7a-4c2b-8e3d-9a8b7c6d5e4f}')
            ->and($uuids[0])->toBeInstanceOf(UuidInterface::class)
            ->and(array_map(strval(...), $uuids))->toBe(['0b8e4f3a-5d3c-4d7e-9f1a-2b3c4d5e6f70', '5c1d7b4e-0f7a-4c2b-8e3d-9a8b7c6d5e4f']);
    });

    it('round trips ulids', function (): void {
        $model = PgsqlModel::query()->create(['ulids' => [
            new Ulid('01H455P6D1K3YFZJ9A8E0S7N5X'),
            '01h455p6d1k3yfzj9a8e0s7n5y',
        ]]);

        /** @var list<Ulid> $ulids */
        $ulids = $model->fresh()?->ulids;

        expect(pgsqlRaw($model, 'ulids'))->toBe('{01H455P6D1K3YFZJ9A8E0S7N5X,01H455P6D1K3YFZJ9A8E0S7N5Y}')
            ->and($ulids[0])->toBeInstanceOf(Ulid::class)
            ->and(array_map(strval(...), $ulids))->toBe(['01H455P6D1K3YFZJ9A8E0S7N5X', '01H455P6D1K3YFZJ9A8E0S7N5Y']);
    });

    it('round trips dates', function (): void {
        $model = PgsqlModel::query()->create(['dates' => [Carbon::parse('2026-08-20 23:30:00'), '1999-12-31']]);

        /** @var list<Carbon> $dates */
        $dates = $model->fresh()?->dates;

        expect(pgsqlRaw($model, 'dates'))->toBe('{2026-08-20,1999-12-31}')
            ->and($dates[0])->toBeInstanceOf(Carbon::class)
            ->and(array_map(fn (Carbon $date) => $date->toDateString(), $dates))->toBe(['2026-08-20', '1999-12-31']);
    });

    it('round trips datetimes', function (): void {
        $model = PgsqlModel::query()->create(['datetimes' => [
            Carbon::parse('2026-08-20 14:30:00.123456'),
            // timestamp without time zone ignores the offset.
            Carbon::parse('2026-08-20 14:30:00', 'Asia/Tokyo'),
        ]]);

        /** @var list<Carbon> $datetimes */
        $datetimes = $model->fresh()?->datetimes;

        expect(pgsqlRaw($model, 'datetimes'))->toBe('{"2026-08-20 14:30:00.123456","2026-08-20 14:30:00"}')
            ->and($datetimes[0])->toBeInstanceOf(Carbon::class)
            ->and($datetimes[0]->format('Y-m-d H:i:s.u'))->toBe('2026-08-20 14:30:00.123456');
    });

    it('round trips datetimes with time zone whatever the session time zone', function (string $timezone): void {
        pgsql()->statement("set time zone '{$timezone}'");

        $instants = [
            CarbonImmutable::parse('2026-08-20 14:30:00.123456', 'UTC'),
            CarbonImmutable::parse('2026-01-15 08:00:00', 'Europe/Rome'),
            CarbonImmutable::parse('2026-08-20 14:30:00', 'Asia/Kolkata'),
        ];

        $model = PgsqlModel::query()->create(['timestamptzs' => $instants]);

        /** @var list<CarbonImmutable> $datetimes */
        $datetimes = $model->fresh()?->timestamptzs;

        expect(pgsql()->table(PgsqlModel::TABLE)->selectRaw("timestamptzs[1] = '2026-08-20 14:30:00.123456+00' as same")->value('same'))->toBeTrue()
            ->and($datetimes[0])->toBeInstanceOf(CarbonImmutable::class)
            ->and($datetimes[0]->getTimezone()->getName())->toBe(date_default_timezone_get())
            ->and(array_map(fn (CarbonImmutable $date) => $date->getTimestampMs(), $datetimes))
            ->toBe(array_map(fn (CarbonImmutable $date) => $date->getTimestampMs(), $instants))
            ->and($datetimes[0]->format('u'))->toBe('123456');
    })->with(['UTC', 'Europe/Berlin', 'America/New_York', 'Asia/Kolkata']);

    it('round trips json objects', function (): void {
        $model = PgsqlModel::query()->create(['addresses' => [
            new Address('Via Roma 1', 'Milano'),
            null,
            new Address('Rue "Quoted", 5 \\ bis', 'Città'),
        ]]);

        expect(pgsqlRaw($model, 'addresses'))->toBe('{"{\"street\":\"Via Roma 1\",\"city\":\"Milano\"}",NULL,"{\"street\":\"Rue \\\\\"Quoted\\\\\", 5 \\\\\\\\ bis\",\"city\":\"Città\"}"}')
            ->and($model->fresh()?->addresses)->toEqual([
                new Address('Via Roma 1', 'Milano'),
                null,
                new Address('Rue "Quoted", 5 \\ bis', 'Città'),
            ]);
    });

    it('round trips jsonb objects', function (): void {
        $contacts = collect([
            new Contact('Ada', ['+39 02 1234'], Priority::High, new Address('Via Roma 1', 'Milano')),
            new Contact('Linus'),
        ]);

        $model = PgsqlModel::query()->create(['contacts' => $contacts]);

        // jsonb normalizes the JSON text (spaces, key order).
        expect(pgsqlRaw($model, 'contacts'))->toBe('{"{\"name\": \"Ada\", \"phones\": [\"+39 02 1234\"], \"address\": {\"city\": \"Milano\", \"street\": \"Via Roma 1\"}, \"priority\": 3}","{\"name\": \"Linus\", \"phones\": [], \"address\": null, \"priority\": null}"}')
            ->and($model->fresh()?->contacts)->toEqual($contacts)
            ->and(pgsql()->table(PgsqlModel::TABLE)->selectRaw("contacts[1]->'address'->>'city' as city")->value('city'))->toBe('Milano');
    });
});

describe('structure', function (): void {
    it('round trips multidimensional arrays', function (): void {
        $model = PgsqlModel::query()->create([
            'matrix' => [[1, 2, 3], [4, null, 6]],
            'text_matrix' => [['a', 'b "c"'], ['{d}', null]],
        ]);

        $fresh = $model->fresh();

        expect(pgsqlRaw($model, 'matrix'))->toBe('{{1,2,3},{4,NULL,6}}')
            ->and(pgsqlRaw($model, 'text_matrix'))->toBe('{{a,"b \"c\""},{"{d}",NULL}}')
            ->and(pgsql()->table(PgsqlModel::TABLE)->selectRaw('array_ndims(matrix) as ndims')->value('ndims'))->toBe(2)
            ->and($fresh?->matrix)->toBe([[1, 2, 3], [4, null, 6]])
            ->and($fresh?->text_matrix)->toBe([['a', 'b "c"'], ['{d}', null]]);
    });

    it('reads arrays whose lower bound is not 1', function (): void {
        $model = PgsqlModel::query()->create(['integers' => [1, 2]]);

        pgsql()->statement('update '.PgsqlModel::TABLE.' set integers[0] = 9 where id = ?', [$model->getKey()]);

        expect(pgsqlRaw($model, 'integers'))->toBe('[0:2]={9,1,2}')
            ->and($model->fresh()?->integers)->toBe([9, 1, 2]);
    });

    it('rejects ragged multidimensional arrays', function (): void {
        PgsqlModel::query()->create(['matrix' => [[1, 2], [3]]]);
    })->throws(QueryException::class);

    it('round trips null elements', function (): void {
        $model = PgsqlModel::query()->create(['texts' => [null, 'a', null], 'integers' => [null]]);

        $fresh = $model->fresh();

        expect(pgsqlRaw($model, 'texts'))->toBe('{NULL,a,NULL}')
            ->and(pgsql()->table(PgsqlModel::TABLE)->selectRaw('array_position(texts, NULL) as position')->value('position'))->toBe(1)
            ->and($fresh?->texts)->toBe([null, 'a', null])
            ->and($fresh?->integers)->toBe([null]);
    });

    it('round trips quoted, empty and special strings', function (): void {
        $values = [
            '',
            ' ',
            ' padded ',
            'a,b',
            'say "hi"',
            "it's",
            'back\\slash',
            '\\"',
            '{braces}',
            '}',
            'NULL',
            'null',
            'Null',
            "line\nbreak",
            "tab\there",
            'unicode: città 🐘',
            '?',
            '$1',
            ':name',
            "'); drop table ".PgsqlModel::TABLE.'; --',
        ];

        $model = PgsqlModel::query()->create(['texts' => $values, 'varchars' => ['', 'NULL', 'a,b']]);

        $fresh = $model->fresh();

        expect($fresh?->texts)->toBe($values)
            ->and($fresh?->varchars)->toBe(['', 'NULL', 'a,b'])
            ->and(pgsql()->table(PgsqlModel::TABLE)->selectRaw('cardinality(texts) as count')->value('count'))->toBe(count($values))
            ->and(pgsql()->table(PgsqlModel::TABLE)->selectRaw('texts[1] = \'\' as empty, texts[11] is null as missing')->first())
            ->toEqual((object) ['empty' => true, 'missing' => false]);
    });
});
