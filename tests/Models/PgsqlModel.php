<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Models;

use AndreaColzani\PgArray\Casts\AsBooleanArray;
use AndreaColzani\PgArray\Casts\AsByteaArray;
use AndreaColzani\PgArray\Casts\AsDateArray;
use AndreaColzani\PgArray\Casts\AsDateTimeArray;
use AndreaColzani\PgArray\Casts\AsDecimalArray;
use AndreaColzani\PgArray\Casts\AsDoubleArray;
use AndreaColzani\PgArray\Casts\AsEncryptedArray;
use AndreaColzani\PgArray\Casts\AsFloatArray;
use AndreaColzani\PgArray\Casts\AsGeographyArray;
use AndreaColzani\PgArray\Casts\AsGeometryArray;
use AndreaColzani\PgArray\Casts\AsHashedArray;
use AndreaColzani\PgArray\Casts\AsImmutableDateTimeArray;
use AndreaColzani\PgArray\Casts\AsInetArray;
use AndreaColzani\PgArray\Casts\AsIntegerArray;
use AndreaColzani\PgArray\Casts\AsMacAddrArray;
use AndreaColzani\PgArray\Casts\AsPgArray;
use AndreaColzani\PgArray\Casts\AsRealArray;
use AndreaColzani\PgArray\Casts\AsStringArray;
use AndreaColzani\PgArray\Casts\AsUlidArray;
use AndreaColzani\PgArray\Casts\AsUuidArray;
use AndreaColzani\PgArray\Casts\AsVectorArray;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use AndreaColzani\PgArray\Enums\PgArrayType;
use AndreaColzani\PgArray\Tests\Fixtures\Address;
use AndreaColzani\PgArray\Tests\Fixtures\Cents;
use AndreaColzani\PgArray\Tests\Fixtures\Contact;
use AndreaColzani\PgArray\Tests\Fixtures\CountryCode;
use AndreaColzani\PgArray\Tests\Fixtures\Email;
use AndreaColzani\PgArray\Tests\Fixtures\Money;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;
use AndreaColzani\PgArray\Tests\Fixtures\Shape;
use AndreaColzani\PgArray\Tests\Fixtures\Sku;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use AndreaColzani\PgArray\Types\Point;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An Eloquent model on the real PostgreSQL connection of the "pgsql" test
 * group, with one array column per cast. The vector and PostGIS columns need
 * extensions, so they are added by the tests that use them.
 */
final class PgsqlModel extends Model
{
    public const TABLE = 'pgarray_models';

    protected $connection = 'pgsql';

    protected $table = self::TABLE;

    protected $guarded = [];

    public $timestamps = false;

    public static function createTable(): void
    {
        Schema::connection('pgsql')->dropIfExists(self::TABLE);

        Schema::connection('pgsql')->create(self::TABLE, function (Blueprint $table): void {
            $table->id();

            // Types
            $table->pgArray('integers', PgArrayType::Integer)->nullable();
            $table->pgArray('bigints', PgArrayType::BigInt)->nullable();
            $table->pgArray('smallints', PgArrayType::SmallInt)->nullable();
            $table->pgArray('decimals', PgArrayType::Decimal)->precision(10, 2)->nullable();
            $table->pgArray('numerics', PgArrayType::Numeric)->nullable();
            $table->pgArray('floats', PgArrayType::DoublePrecision)->nullable();
            $table->pgArray('doubles', PgArrayType::DoublePrecision)->nullable();
            $table->pgArray('reals', PgArrayType::Real)->nullable();
            $table->pgArray('booleans', PgArrayType::Boolean)->nullable();
            $table->pgArray('chars', PgArrayType::Char)->length(3)->nullable();
            $table->pgArray('varchars', PgArrayType::Varchar)->length(20)->nullable();
            $table->pgArray('texts', PgArrayType::Text)->nullable();
            $table->pgArray('uuids', PgArrayType::Uuid)->nullable();
            $table->pgArray('ulids', PgArrayType::Ulid)->nullable();
            $table->pgArray('dates', PgArrayType::Date)->nullable();
            $table->pgArray('times', PgArrayType::Time)->precision(6)->nullable();
            $table->pgArray('timetzs', PgArrayType::TimeTz)->nullable();
            $table->pgArray('datetimes', PgArrayType::Timestamp)->precision(6)->nullable();
            $table->pgArray('timestamptzs', PgArrayType::TimestampTz)->precision(6)->nullable();
            $table->pgArray('addresses', PgArrayType::Json)->nullable();
            $table->pgArray('contacts', PgArrayType::Jsonb)->nullable();

            // Structure
            $table->pgArray('matrix', PgArrayType::Integer)->dimensions(2)->nullable();
            $table->pgArray('text_matrix', PgArrayType::Text)->dimensions(2)->nullable();

            // Advanced
            $table->pgArray('statuses', PgArrayType::Text)->nullable();
            $table->pgArray('priorities', PgArrayType::SmallInt)->nullable();
            $table->pgArray('emails', PgArrayType::Varchar)->nullable();
            $table->pgArray('cents', PgArrayType::BigInt)->nullable();
            $table->pgArray('prices', PgArrayType::Jsonb)->nullable();
            $table->pgArray('skus', PgArrayType::Text)->nullable();
            $table->pgArray('country_codes', PgArrayType::Char)->length(2)->nullable();
            $table->pgArray('secrets', PgArrayType::Text)->nullable();
            $table->pgArray('encrypted_numbers', PgArrayType::Text)->nullable();
            $table->pgArray('encrypted_addresses', PgArrayType::Text)->nullable();
            $table->pgArray('recovery_codes', PgArrayType::Text)->nullable();
            $table->pgArray('files', PgArrayType::Bytea)->nullable();
            $table->pgArray('ips', PgArrayType::Inet)->nullable();
            $table->pgArray('macs', PgArrayType::MacAddr)->nullable();
        });
    }

    public static function dropTable(): void
    {
        Schema::connection('pgsql')->dropIfExists(self::TABLE);
    }

    protected function casts(): array
    {
        return [
            'integers' => AsIntegerArray::class,
            'bigints' => AsIntegerArray::class,
            'smallints' => AsIntegerArray::collect(),
            'decimals' => AsDecimalArray::class,
            'numerics' => AsDecimalArray::class,
            'floats' => AsFloatArray::class,
            'doubles' => AsDoubleArray::class,
            'reals' => AsRealArray::class,
            'booleans' => AsBooleanArray::class,
            'chars' => AsStringArray::class,
            'varchars' => AsStringArray::class,
            'texts' => AsStringArray::class,
            'uuids' => AsUuidArray::class,
            'ulids' => AsUlidArray::class,
            'dates' => AsDateArray::class,
            'times' => AsStringArray::class,
            'timetzs' => AsStringArray::class,
            'datetimes' => AsDateTimeArray::class,
            'timestamptzs' => AsImmutableDateTimeArray::class,
            'addresses' => AsPgArray::of(Address::class),
            'contacts' => AsPgArray::of(Contact::class, PgArrayContainer::Collection),
            'matrix' => AsIntegerArray::class,
            'text_matrix' => AsStringArray::class,
            'statuses' => AsPgArray::of(Status::class),
            'priorities' => AsPgArray::of(Priority::class, PgArrayContainer::Collection),
            'emails' => AsPgArray::of(Email::class),
            'cents' => AsPgArray::of(Cents::class),
            'prices' => AsPgArray::of(Money::class),
            'skus' => AsPgArray::of(Sku::class),
            'country_codes' => AsPgArray::of(CountryCode::class),
            'secrets' => AsEncryptedArray::class,
            'encrypted_numbers' => AsPgArray::encrypted(PgArrayCast::Integer),
            'encrypted_addresses' => AsPgArray::encrypted(Address::class),
            'recovery_codes' => AsHashedArray::class,
            'files' => AsByteaArray::class,
            'ips' => AsInetArray::class,
            'macs' => AsMacAddrArray::class,
            'embeddings' => AsVectorArray::withDimensions(3),
            'shapes' => AsGeometryArray::class,
            'areas' => AsGeographyArray::collect(),
            'locations' => AsPgArray::of(Point::class),
            'geo_shapes' => AsPgArray::of(Shape::class),
        ];
    }
}
