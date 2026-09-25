<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Enums\PgArrayType;
use AndreaColzani\PgArray\Support\PgArrayHash;
use AndreaColzani\PgArray\Support\PgArrayParser;
use AndreaColzani\PgArray\Tests\Fixtures\Address;
use AndreaColzani\PgArray\Tests\Fixtures\Cents;
use AndreaColzani\PgArray\Tests\Fixtures\CountryCode;
use AndreaColzani\PgArray\Tests\Fixtures\Email;
use AndreaColzani\PgArray\Tests\Fixtures\Money;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;
use AndreaColzani\PgArray\Tests\Fixtures\Shape;
use AndreaColzani\PgArray\Tests\Fixtures\Sku;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use AndreaColzani\PgArray\Tests\Models\PgsqlModel;
use AndreaColzani\PgArray\Types\Point;
use AndreaColzani\PgArray\Types\Vector;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

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
function pgsqlStored(PgsqlModel $model, string $column): mixed
{
    return pgsql()->table(PgsqlModel::TABLE)->where('id', $model->getKey())->value($column);
}

function pgsqlSelect(PgsqlModel $model, string $expression): mixed
{
    return pgsql()->table(PgsqlModel::TABLE)->where('id', $model->getKey())->selectRaw($expression.' as value')->value('value');
}

it('round trips backed enums', function (): void {
    $model = PgsqlModel::query()->create([
        'statuses' => [Status::Active, 'inactive', null],
        'priorities' => collect([Priority::High, Priority::Low]),
    ]);

    $fresh = $model->fresh();

    expect(pgsqlStored($model, 'statuses'))->toBe('{active,inactive,NULL}')
        ->and(pgsqlStored($model, 'priorities'))->toBe('{3,1}')
        ->and($fresh?->statuses)->toBe([Status::Active, Status::Inactive, null])
        ->and($fresh?->priorities?->all())->toBe([Priority::High, Priority::Low])
        ->and(PgsqlModel::query()->wherePgArrayContains('statuses', Status::Active)->count())->toBe(1);
});

it('round trips custom objects', function (): void {
    $model = PgsqlModel::query()->create([
        'emails' => [new Email('Ada@Example.com'), null, new Email("O'Brien+Tag@example.com")],
        'cents' => [new Cents(PHP_INT_MAX), new Cents(-1)],
    ]);

    $fresh = $model->fresh();

    expect(pgsqlStored($model, 'emails'))->toBe("{ada@example.com,NULL,o'brien+tag@example.com}")
        ->and(pgsqlStored($model, 'cents'))->toBe('{'.PHP_INT_MAX.',-1}')
        ->and($fresh?->emails)->toEqual([new Email('ada@example.com'), null, new Email("O'Brien+Tag@example.com")])
        ->and($fresh?->cents)->toEqual([new Cents(PHP_INT_MAX), new Cents(-1)]);
});

it('round trips values through external serializers', function (): void {
    $model = PgsqlModel::query()->create([
        'prices' => [new Money(1999, 'EUR'), null],
        'skus' => [new Sku('AB-1'), Sku::of('cd "2"')],
        'country_codes' => [new CountryCode('IT'), CountryCode::of('fr')],
    ]);

    $fresh = $model->fresh();

    expect(pgsqlStored($model, 'prices'))->toBe('{"{\"amount\": 1999, \"currency\": \"EUR\"}",NULL}')
        ->and(pgsqlStored($model, 'skus'))->toBe('{AB-1,"CD \"2\""}')
        ->and(pgsqlStored($model, 'country_codes'))->toBe('{IT,FR}')
        ->and(pgsqlSelect($model, "prices[1]->>'currency'"))->toBe('EUR')
        ->and($fresh?->prices)->toEqual([new Money(1999, 'EUR'), null])
        ->and($fresh?->skus)->toEqual([new Sku('AB-1'), new Sku('CD "2"')])
        ->and($fresh?->country_codes)->toEqual([new CountryCode('IT'), new CountryCode('FR')]);
});

it('round trips encrypted values', function (): void {
    $model = PgsqlModel::query()->create([
        'secrets' => ['top "secret"', null, ''],
        'encrypted_numbers' => [1, 2, 3],
        'encrypted_addresses' => [new Address('Via Roma 1', 'Milano')],
    ]);

    $fresh = $model->fresh();

    /** @var list<?string> $secrets */
    $secrets = PgArrayParser::parse((string) pgsqlStored($model, 'secrets'));

    expect($secrets[1])->toBeNull()
        ->and(Crypt::decryptString((string) $secrets[0]))->toBe('top "secret"')
        ->and(pgsqlStored($model, 'secrets'))->not->toContain('secret"')
        ->and(pgsqlSelect($model, 'cardinality(encrypted_numbers)'))->toBe(3)
        ->and($fresh?->secrets)->toBe(['top "secret"', null, ''])
        ->and($fresh?->encrypted_numbers)->toBe([1, 2, 3])
        ->and($fresh?->encrypted_addresses)->toEqual([new Address('Via Roma 1', 'Milano')]);
});

it('round trips hashed values', function (): void {
    $model = PgsqlModel::query()->create(['recovery_codes' => ['code-1', 'code "2"', null]]);

    $fresh = $model->fresh();

    /** @var list<?string> $hashes */
    $hashes = $fresh?->recovery_codes;

    expect($hashes[2])->toBeNull()
        ->and($hashes[0])->toStartWith('$2y$')
        ->and(PgArrayHash::check('code "2"', $hashes))->toBeTrue()
        ->and(PgArrayHash::find('code-1', $hashes))->toBe(0)
        ->and(PgArrayHash::check('code-3', $hashes))->toBeFalse();

    // Hashes read from the database are stored again unchanged.
    $fresh?->update(['recovery_codes' => [...$hashes, 'code-3']]);

    /** @var list<?string> $updated */
    $updated = $fresh?->fresh()?->recovery_codes;

    expect(array_slice($updated, 0, 3))->toBe($hashes)
        ->and(PgArrayHash::check('code-3', $updated))->toBeTrue();
});

it('round trips bytea values', function (): void {
    $binary = random_bytes(64)."\0\\\"',{}";

    $model = PgsqlModel::query()->create(['files' => [$binary, '', null, 'text']]);

    expect(pgsqlStored($model, 'files'))->toStartWith('{"\\\\x')
        ->and(pgsqlSelect($model, 'octet_length(files[1])'))->toBe(strlen($binary))
        ->and(pgsqlSelect($model, "encode(files[4], 'hex')"))->toBe('74657874')
        ->and($model->fresh()?->files)->toBe([$binary, '', null, 'text']);
});

it('reads bytea values in the escape output format', function (): void {
    $model = PgsqlModel::query()->create(['files' => ["a\0b\\c\"", 'text']]);

    pgsql()->statement("set bytea_output = 'escape'");

    expect(pgsqlStored($model, 'files'))->not->toContain('\\\\x')
        ->and($model->fresh()?->files)->toBe(["a\0b\\c\"", 'text']);
});

it('round trips inet and macaddr values', function (): void {
    $model = PgsqlModel::query()->create([
        'ips' => ['192.168.0.1', '10.0.0.0/8', '2001:0DB8:0000:0000:0000:0000:0000:0001', '::1/128', null],
        'macs' => ['08-00-2B-01-02-03', '0800.2b01.0203', null],
    ]);

    $fresh = $model->fresh();

    expect(pgsqlStored($model, 'ips'))->toBe('{192.168.0.1,10.0.0.0/8,2001:db8::1,::1,NULL}')
        ->and(pgsqlStored($model, 'macs'))->toBe('{08:00:2b:01:02:03,08:00:2b:01:02:03,NULL}')
        ->and($fresh?->ips)->toBe(['192.168.0.1', '10.0.0.0/8', '2001:db8::1', '::1', null])
        ->and($fresh?->macs)->toBe(['08:00:2b:01:02:03', '08:00:2b:01:02:03', null])
        ->and(pgsqlSelect($model, "ips[2] >> '10.1.2.3'::inet"))->toBeTrue();
});

it('round trips pgvector values', function (): void {
    pgsqlExtension('vector');

    Schema::connection('pgsql')->table(PgsqlModel::TABLE, function (Blueprint $table): void {
        $table->pgArray('embeddings', PgArrayType::Vector)->size(3)->nullable();
    });

    $model = PgsqlModel::query()->create(['embeddings' => [new Vector([0.1, -2.5, 3]), null, new Vector([1.0e-8, 0, 1])]]);

    /** @var list<?Vector> $embeddings */
    $embeddings = $model->fresh()?->embeddings;

    expect(pgsqlStored($model, 'embeddings'))->toBe('{"[0.1,-2.5,3]",NULL,"[1e-08,0,1]"}')
        ->and($embeddings[0])->toBeInstanceOf(Vector::class)
        ->and($embeddings[0]?->toArray())->toBe([0.1, -2.5, 3.0])
        ->and($embeddings[1])->toBeNull()
        ->and($embeddings[2]?->toArray())->toBe([1.0e-8, 0.0, 1.0])
        ->and((float) pgsqlSelect($model, "embeddings[1] <-> '[0.1,-2.5,4]'"))->toBe(1.0);
});

it('rejects pgvector values with the wrong dimensions', function (): void {
    pgsqlExtension('vector');

    Schema::connection('pgsql')->table(PgsqlModel::TABLE, function (Blueprint $table): void {
        $table->pgArray('embeddings', PgArrayType::Vector)->size(3)->nullable();
    });

    pgsql()->table(PgsqlModel::TABLE)->insert(['embeddings' => '{"[1,2]"}']);
})->throws(QueryException::class);

it('round trips PostGIS points', function (): void {
    pgsqlExtension('postgis');

    Schema::connection('pgsql')->table(PgsqlModel::TABLE, function (Blueprint $table): void {
        $table->pgArray('shapes', PgArrayType::Geometry)->nullable();
        $table->pgArray('areas', PgArrayType::Geography)->subtype('Polygon')->nullable();
        $table->pgArray('locations', PgArrayType::Geography)->subtype('Point')->nullable();
    });

    $milan = new Point(latitude: 45.4642, longitude: 9.19);
    $sydney = new Point(latitude: -33.8688, longitude: 151.2093);

    $model = PgsqlModel::query()->create([
        'shapes' => ['POINT(1 2)', 'SRID=3857;LINESTRING(0 0,1 1)', null],
        'areas' => collect(['SRID=4326;POLYGON((0 0,0 1,1 1,1 0,0 0))']),
        'locations' => [$milan, null, $sydney, 'SRID=4326;POINT(-0.1276 51.5072)'],
    ]);

    $fresh = $model->fresh();

    /** @var list<?string> $shapes */
    $shapes = $fresh?->shapes;

    expect(pgsqlSelect($model, 'ST_AsEWKT(shapes[2])'))->toBe('SRID=3857;LINESTRING(0 0,1 1)')
        ->and(pgsqlSelect($model, 'ST_AsText(areas[1])'))->toBe('POLYGON((0 0,0 1,1 1,1 0,0 0))')
        ->and(pgsqlSelect($model, 'ST_AsEWKT(locations[1])'))->toBe('SRID=4326;POINT(9.19 45.4642)')
        // Hex EWKB, as returned by PostgreSQL.
        ->and($shapes[0])->toBe('0101000000000000000000F03F0000000000000040')
        ->and($shapes[2])->toBeNull()
        ->and($fresh?->areas?->first())->toBeString()
        ->and($fresh?->locations)->toEqual([$milan, null, $sydney, new Point(latitude: 51.5072, longitude: -0.1276)])
        // Distance Milan–Sydney on the WGS 84 spheroid, in km.
        ->and((float) pgsqlSelect($model, 'ST_Distance(locations[1], locations[3])') / 1000)->toEqualWithDelta(16_555, 1);

    // Hex EWKB read from the database is stored again unchanged.
    $fresh?->update(['shapes' => $shapes]);

    expect(pgsqlSelect($model, 'ST_AsEWKT(shapes[2])'))->toBe('SRID=3857;LINESTRING(0 0,1 1)');
});

it('round trips PostGIS values through a delimited serializer', function (): void {
    pgsqlExtension('postgis');

    Schema::connection('pgsql')->table(PgsqlModel::TABLE, function (Blueprint $table): void {
        $table->pgArray('geo_shapes', PgArrayType::Geometry)->nullable();
    });

    $model = PgsqlModel::query()->create(['geo_shapes' => [
        new Shape('POINT(1 2)'),
        null,
        new Shape('LINESTRING(0 0,1 1)', 3857),
    ]]);

    /** @var list<?Shape> $shapes */
    $shapes = $model->fresh()?->geo_shapes;

    expect(pgsqlSelect($model, 'cardinality(geo_shapes)'))->toBe(3)
        ->and(pgsqlSelect($model, 'ST_AsEWKT(geo_shapes[3])'))->toBe('SRID=3857;LINESTRING(0 0,1 1)')
        ->and($shapes[0])->toBeInstanceOf(Shape::class)
        ->and($shapes[0]?->wkt)->toBe('0101000020E6100000000000000000F03F0000000000000040')
        ->and($shapes[1])->toBeNull();
});

it('filters and concatenates PostGIS arrays', function (): void {
    pgsqlExtension('postgis');

    Schema::connection('pgsql')->table(PgsqlModel::TABLE, function (Blueprint $table): void {
        $table->pgArray('locations', PgArrayType::Geography)->subtype('Point')->nullable();
    });

    $milan = new Point(latitude: 45.4642, longitude: 9.19);
    $rome = new Point(latitude: 41.9028, longitude: 12.4964);

    $model = PgsqlModel::query()->create(['locations' => [$milan, $rome]]);

    expect(PgsqlModel::query()->whereKey($model->getKey())->pgArrayAppend('locations', [$rome->toPgArrayValue(), $milan->toPgArrayValue()], PgArrayType::Geography))->toBe(1)
        ->and(pgsqlSelect($model, 'cardinality(locations)'))->toBe(4)
        ->and($model->fresh()?->locations)->toEqual([$milan, $rome, $rome, $milan]);

    Schema::connection('pgsql')->table(PgsqlModel::TABLE, function (Blueprint $table): void {
        $table->pgArray('shapes', PgArrayType::Geometry)->nullable();
    });

    PgsqlModel::query()->whereKey($model->getKey())->update(['shapes' => '{"POINT(1 2)":"POINT(3 4)"}']);

    expect(PgsqlModel::query()->wherePgArrayContains('shapes', ['POINT(3 4)', 'POINT(1 2)'], PgArrayType::Geometry)->count())->toBe(1)
        ->and(PgsqlModel::query()->wherePgArrayOverlaps('shapes', ['POINT(5 6)', 'POINT(1 2)'], PgArrayType::Geometry)->count())->toBe(1)
        ->and(PgsqlModel::query()->wherePgArrayContainedBy('shapes', ['POINT(5 6)', 'POINT(1 2)'], PgArrayType::Geometry)->count())->toBe(0);
});
