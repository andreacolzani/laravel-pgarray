<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Models;

use AndreaColzani\PgArray\Casts\AsDateArray;
use AndreaColzani\PgArray\Casts\AsDateTimeArray;
use AndreaColzani\PgArray\Casts\AsDecimalArray;
use AndreaColzani\PgArray\Casts\AsDoubleArray;
use AndreaColzani\PgArray\Casts\AsFloatArray;
use AndreaColzani\PgArray\Casts\AsImmutableDateArray;
use AndreaColzani\PgArray\Casts\AsImmutableDateTimeArray;
use AndreaColzani\PgArray\Casts\AsIntegerArray;
use AndreaColzani\PgArray\Casts\AsPgArray;
use AndreaColzani\PgArray\Casts\AsRealArray;
use AndreaColzani\PgArray\Casts\AsStringableArray;
use AndreaColzani\PgArray\Casts\AsUlidArray;
use AndreaColzani\PgArray\Casts\AsUuidArray;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use AndreaColzani\PgArray\Tests\Fixtures\Address;
use AndreaColzani\PgArray\Tests\Fixtures\Contact;
use AndreaColzani\PgArray\Tests\Fixtures\CountryCode;
use AndreaColzani\PgArray\Tests\Fixtures\Email;
use AndreaColzani\PgArray\Tests\Fixtures\Money;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;
use AndreaColzani\PgArray\Tests\Fixtures\Sku;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use Illuminate\Database\Eloquent\Model;

final class TestModel extends Model
{
    protected $fillable = ['numbers'];

    protected function casts(): array
    {
        return [
            'tags' => AsPgArray::class,
            'numbers' => AsPgArray::of(PgArrayCast::Integer),
            'number_collection' => AsPgArray::of(
                PgArrayCast::Integer,
                PgArrayContainer::Collection,
            ),
            'integer_array' => AsIntegerArray::class,
            'integer_collection' => AsIntegerArray::collect(),
            'decimal_array' => AsDecimalArray::class,
            'decimal_collection' => AsDecimalArray::collect(),
            'double_array' => AsDoubleArray::class,
            'float_array' => AsFloatArray::class,
            'real_array' => AsRealArray::class,
            'stringable_array' => AsStringableArray::class,
            'stringable_collection' => AsStringableArray::collect(),
            'date_array' => AsDateArray::class,
            'datetime_array' => AsDateTimeArray::class,
            'datetime_collection' => AsDateTimeArray::collect(),
            'immutable_date_array' => AsImmutableDateArray::class,
            'immutable_datetime_array' => AsImmutableDateTimeArray::class,
            'uuid_array' => AsUuidArray::class,
            'uuid_collection' => AsUuidArray::collect(),
            'ulid_array' => AsUlidArray::class,
            'ulid_collection' => AsUlidArray::collect(),
            'statuses' => AsPgArray::of(Status::class),
            'status_collection' => AsPgArray::of(
                Status::class,
                PgArrayContainer::Collection,
            ),
            'priorities' => AsPgArray::of(Priority::class),
            'emails' => AsPgArray::of(Email::class),
            'email_collection' => AsPgArray::of(
                Email::class,
                PgArrayContainer::Collection,
            ),
            'addresses' => AsPgArray::of(Address::class),
            'address_collection' => AsPgArray::of(
                Address::class,
                PgArrayContainer::Collection,
            ),
            'contacts' => AsPgArray::of(Contact::class),
            'prices' => AsPgArray::of(Money::class),
            'price_collection' => AsPgArray::of(
                Money::class,
                PgArrayContainer::Collection,
            ),
            'skus' => AsPgArray::of(Sku::class),
            'country_codes' => AsPgArray::of(CountryCode::class),
        ];
    }
}
