# Laravel PostgreSQL Arrays

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andreacolzani/laravel-pgarray.svg?style=flat-square)](https://packagist.org/packages/andreacolzani/laravel-pgarray)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/andrecolza/laravel-pgarray/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/andrecolza/laravel-pgarray/actions?query=workflow%3Arun-tests+branch%3Amain)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/andrecolza/laravel-pgarray/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/andrecolza/laravel-pgarray/actions?query=workflow%3APHPStan+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/andreacolzani/laravel-pgarray.svg?style=flat-square)](https://packagist.org/packages/andreacolzani/laravel-pgarray)

First-class support for PostgreSQL array columns (`text[]`, `integer[]`, `uuid[]`, `jsonb[]`, `vector[]`, …) in Laravel:

- **Eloquent casts** that parse and serialize PostgreSQL arrays correctly: quoting, escaping, `NULL` elements, empty strings and multidimensional arrays.
- **Typed elements**: integers, decimals, floats, booleans, dates, UUIDs, ULIDs, backed enums, your own value objects and JSON objects.
- **Element-level encryption and hashing**.
- **PostgreSQL special types**: `bytea`, `inet`, `macaddr`, pgvector `vector`, PostGIS `geometry` / `geography`.
- A **`pgArray()` migration helper** with type modifiers (`varchar(50)[]`, `numeric(10,2)[]`, `vector(1536)[]`, …).
- **Query builder macros** for the array operators `@>`, `<@`, `&&` and `||`.

```php
use AndreaColzani\PgArray\Casts\AsIntegerArray;
use AndreaColzani\PgArray\Casts\AsPgArray;
use AndreaColzani\PgArray\Casts\AsStringArray;

class Post extends Model
{
    protected function casts(): array
    {
        return [
            'tags' => AsStringArray::class,           // text[]    → ['php', 'laravel']
            'scores' => AsIntegerArray::collect(),    // integer[] → Collection([1, 2, 3])
            'statuses' => AsPgArray::of(Status::class), // text[]  → [Status::Draft, ...]
        ];
    }
}

Post::query()->wherePgArrayContains('tags', ['php', 'laravel'])->get();
```

## Contents

- [Installation](#installation)
- [Basic usage](#basic-usage)
- [Built-in casts](#built-in-casts)
- [Collections](#collections)
- [Multidimensional arrays](#multidimensional-arrays)
- [Enums](#enums)
- [Custom value objects](#custom-value-objects)
- [JSON / JSONB](#json--jsonb)
- [External serializers](#external-serializers)
- [Dates and time zones](#dates-and-time-zones)
- [Encrypted arrays](#encrypted-arrays)
- [Hashed arrays](#hashed-arrays)
- [PostgreSQL special types](#postgresql-special-types)
- [Migrations](#migrations)
- [Query builder](#query-builder)
- [Exceptions](#exceptions)
- [Supported PostgreSQL types](#supported-postgresql-types)
- [PHP / Laravel compatibility](#php--laravel-compatibility)
- [Testing](#testing)
- [License](#license)

## Installation

Install the package via Composer:

```bash
composer require andreacolzani/laravel-pgarray
```

The service provider is discovered automatically. It registers the `pgArray()` migration helper and the query builder macros.

The configuration file is only needed to map [external serializers](#external-serializers). Publish it with:

```bash
php artisan vendor:publish --tag="laravel-pgarray-config"
```

## Basic usage

Add a cast to the model for each array column:

```php
use AndreaColzani\PgArray\Casts\AsIntegerArray;

protected function casts(): array
{
    return [
        'scores' => AsIntegerArray::class,
    ];
}
```

```php
$post->scores = [10, 20, null, 30];
$post->save();                    // stored as {10,20,NULL,30}

$post->fresh()->scores;           // [10, 20, null, 30]
```

The same cast can be written with the generic `AsPgArray` cast, which accepts any element type:

```php
use AndreaColzani\PgArray\Casts\AsPgArray;
use AndreaColzani\PgArray\Enums\PgArrayCast;

'scores' => AsPgArray::of(PgArrayCast::Integer),
```

The package keeps PostgreSQL semantics:

- a `NULL` column is `null`, an empty array (`{}`) is `[]`;
- `NULL` elements are `null`, while the strings `'NULL'` and `''` are kept as strings;
- quotes, backslashes, braces, commas, whitespace and Unicode are escaped and parsed correctly.

`AsPgArray` without arguments (`'tags' => AsPgArray::class`) is a string array.

## Built-in casts

Every built-in cast is available as a dedicated castable and as a `PgArrayCast` case:

| Castable | `PgArrayCast` | PHP element | Typical column |
|---|---|---|---|
| `AsStringArray` | `String` | `string` | `text[]`, `varchar[]`, `char[]`, `time[]`, `timetz[]` |
| `AsStringableArray` | `Stringable` | `Illuminate\Support\Stringable` | `text[]` |
| `AsIntegerArray` | `Integer` | `int` | `smallint[]`, `integer[]`, `bigint[]` |
| `AsDecimalArray` | `Decimal` | `string` (exact) | `numeric[]`, `decimal[]` |
| `AsFloatArray` | `Float` | `float` | `double precision[]` |
| `AsDoubleArray` | `Double` | `float` | `double precision[]` |
| `AsRealArray` | `Real` | `float` | `real[]` |
| `AsBooleanArray` | `Boolean` | `bool` | `boolean[]` |
| `AsDateArray` | `Date` | `Carbon\Carbon` | `date[]` |
| `AsImmutableDateArray` | `ImmutableDate` | `Carbon\CarbonImmutable` | `date[]` |
| `AsDateTimeArray` | `DateTime` | `Carbon\Carbon` | `timestamp[]`, `timestamptz[]` |
| `AsImmutableDateTimeArray` | `ImmutableDateTime` | `Carbon\CarbonImmutable` | `timestamp[]`, `timestamptz[]` |
| `AsUuidArray` | `Uuid` | `Ramsey\Uuid\UuidInterface` | `uuid[]` |
| `AsUlidArray` | `Ulid` | `Symfony\Component\Uid\Ulid` | `char(26)[]`, `text[]` |
| `AsUriArray` | `Uri` | `Illuminate\Support\Uri` | `text[]` |
| `AsByteaArray` | `Bytea` | `string` (binary) | `bytea[]` |
| `AsInetArray` | `Inet` | `string` | `inet[]` |
| `AsMacAddrArray` | `MacAddr` | `string` | `macaddr[]` |
| `AsVectorArray` | `Vector` | `AndreaColzani\PgArray\Types\Vector` | `vector[]` |
| `AsGeometryArray` | `Geometry` | `string` (hex EWKB) | `geometry[]` |
| `AsGeographyArray` | `Geography` | `string` (hex EWKB) | `geography[]` |
| `AsEncryptedArray` | — | `string` | `text[]` |
| `AsHashedArray` | `Hashed` | `string` (hash) | `text[]` |

All castables live in the `AndreaColzani\PgArray\Casts` namespace.

A few notes on numbers:

- Decimals are strings, so no precision is lost (`'12.50'` stays `'12.50'`).
- Floats are written with the shortest representation that round trips (`0.1 + 0.2` is stored as `0.30000000000000004`). `NAN`, `INF` and `-INF` are stored as `NaN`, `Infinity` and `-Infinity` and read back as such.
- `real` is single precision in PostgreSQL: values read back may differ slightly from the assigned ones.

Values assigned to an array are converted by the element cast: numeric strings become integers, Carbon instances and date strings become dates, `UuidInterface` / `Ulid` / `Stringable` objects become strings, and so on.

## Collections

Every castable has a `collect()` method, which retrieves the array as an `Illuminate\Support\Collection`:

```php
use AndreaColzani\PgArray\Casts\AsIntegerArray;

'scores' => AsIntegerArray::collect(),
```

```php
$post->scores;                 // Collection([10, 20, 30])
$post->scores->sum();          // 60

$post->scores = collect([1, 2, 3]);   // Collections can be assigned
$post->scores = [1, 2, 3];            // … and so can arrays
```

With `AsPgArray`, pass the container as second argument:

```php
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;

'scores' => AsPgArray::of(PgArrayCast::Integer, PgArrayContainer::Collection),
```

Only the top level is a Collection: the inner dimensions of a multidimensional array stay plain arrays.

## Multidimensional arrays

Multidimensional arrays work with every cast, in both directions:

```php
'matrix' => AsIntegerArray::class, // integer[][] column

$model->matrix = [[1, 2], [3, 4]];  // stored as {{1,2},{3,4}}
$model->matrix;                     // [[1, 2], [3, 4]]
```

Element casts are applied recursively to every element. PostgreSQL requires multidimensional arrays to be rectangular: sub-arrays with different lengths are rejected by the database.

## Enums

Backed enums are supported automatically:

```php
enum Status: string
{
    case Draft = 'draft';
    case Published = 'published';
}
```

```php
'statuses' => AsPgArray::of(Status::class),                               // text[]
'statuses' => AsPgArray::of(Status::class, PgArrayContainer::Collection), // Collection
```

```php
$post->statuses = [Status::Draft, 'published']; // cases or backing values
$post->save();                                  // stored as {draft,published}

$post->fresh()->statuses;                       // [Status::Draft, Status::Published]
```

- String-backed enums use `text[]` columns, int-backed enums `integer[]` (or `smallint[]` / `bigint[]`).
- Values that do not match a case throw an `InvalidValueException`, both when assigning and when reading.
- Pure enums (without backing values) are not supported.

## Custom value objects

Implement `PgArrayValue` to use your own classes as elements. The class converts itself to a **logical PHP value** and back: quoting and escaping are handled by the package.

```php
use AndreaColzani\PgArray\Contracts\PgArrayValue;

final class Email implements PgArrayValue
{
    public readonly string $address;

    public function __construct(string $address)
    {
        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid email address [{$address}].");
        }

        $this->address = strtolower($address);
    }

    public function toPgArrayValue(): string
    {
        return $this->address;
    }

    public static function fromPgArrayValue(mixed $value): static
    {
        return new static((string) $value);
    }
}
```

```php
'emails' => AsPgArray::of(Email::class), // text[]

$user->emails = [new Email('Taylor@example.com'), 'dayle@example.com'];
$user->emails; // [Email('taylor@example.com'), Email('dayle@example.com')]
```

- `toPgArrayValue()` returns a scalar (`string`, `int`, `float`, `bool`) or `null`. To store structured values, use [JSON elements](#json--jsonb).
- `fromPgArrayValue()` receives the element as text, as read from PostgreSQL. It is never called with `null`: `NULL` elements stay `null`.
- Raw values assigned to the array (such as `'dayle@example.com'` above) go through `fromPgArrayValue()`, so the class validates them. Objects of other classes are rejected.
- A backed enum implementing `PgArrayValue` uses the contract instead of the automatic enum support.

## JSON / JSONB

PostgreSQL `json[]` / `jsonb[]` columns are arrays whose elements are JSON values. Implement the `PgArrayJsonValue` marker contract to store objects as JSON elements:

```php
use AndreaColzani\PgArray\Concerns\InteractsWithPgArrayJson;
use AndreaColzani\PgArray\Contracts\PgArrayJsonValue;

final class Address implements PgArrayJsonValue
{
    use InteractsWithPgArrayJson;

    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly ?Country $country = null, // a backed enum
    ) {}
}
```

```php
'addresses' => AsPgArray::of(Address::class), // json[] or jsonb[]

$user->addresses = [
    new Address('Via Roma 1', 'Milano', Country::Italy),
    new Address('Main Street 10', 'Springfield'),
];
// each element is stored as a JSON object: {"street":"Via Roma 1","city":"Milano","country":"it"}

$user->addresses[0]->city; // 'Milano'
```

The optional `InteractsWithPgArrayJson` trait provides a default implementation:

- `toPgArrayValue()` returns the object's properties by name. Nested `PgArrayValue` objects are converted through their own contract.
- `fromPgArrayValue()` passes the matching keys to the constructor as named arguments, restoring nested `PgArrayValue` objects and backed enums from the parameter types. Unknown keys are ignored, so stored elements keep working after a property is removed.

You can override either method, or implement both yourself without the trait:

```php
final class Address implements PgArrayJsonValue
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}

    public function toPgArrayValue(): array
    {
        return ['street' => $this->street, 'city' => $this->city];
    }

    public static function fromPgArrayValue(mixed $value): static
    {
        return new static($value['street'], $value['city']);
    }
}
```

`fromPgArrayValue()` receives the decoded JSON value, with objects decoded as associative arrays. SQL `NULL` elements and JSON `null` elements are both read as `null`. Invalid JSON throws a `JsonException`.

## External serializers

When a class cannot implement the contracts (for example, a class of another package), or several classes share the same serialization rules, map the class to an external serializer:

```php
use AndreaColzani\PgArray\Contracts\PgArrayValueSerializer;

final class MoneySerializer implements PgArrayValueSerializer
{
    public function serialize(object $value): mixed
    {
        return $value->getAmount().' '.$value->getCurrency();  // '1250 EUR'
    }

    public function deserialize(mixed $value, string $class): object
    {
        [$amount, $currency] = explode(' ', $value);

        return new $class($amount, $currency);
    }
}
```

There are three ways to map a serializer, in order of precedence:

```php
// 1. config/pgarray.php: classes you cannot modify
'serializers' => [
    Money::class => MoneySerializer::class,
],

// 2. The attribute, for classes you own
use AndreaColzani\PgArray\Attributes\PgArraySerializer;

#[PgArraySerializer(SkuSerializer::class)]
final class Sku { /* … */ }

// 3. The PgArraySerializable contract
use AndreaColzani\PgArray\Contracts\PgArraySerializable;

final class CountryCode implements PgArraySerializable
{
    public static function pgArraySerializer(): string
    {
        return StringValueSerializer::class;
    }
}
```

Serializers can also be registered from a service provider:

```php
use AndreaColzani\PgArray\Support\PgArraySerializerRegistry;

$this->app->make(PgArraySerializerRegistry::class)
    ->register([Money::class, Price::class], MoneySerializer::class);
```

The cast definition does not change: `'prices' => AsPgArray::of(Money::class)`.

- `serialize()` returns a scalar or `null`. Implement the `PgArrayJsonSerializer` marker contract instead to store structured values in `json[]` / `jsonb[]` columns.
- `deserialize()` receives the target class, so one serializer can serve several classes. It must return an instance of that class and is never called with `null`.
- Classes are matched by exact name: subclasses and interfaces are not matched.
- A serializer takes precedence over `PgArrayValue`, `PgArrayJsonValue` and the automatic enum support, so you can change how existing value objects or third-party enums are stored.
- Serializers are resolved through the container, so they can use dependency injection.

## Dates and time zones

`AsDateArray` and `AsImmutableDateArray` write dates as `Y-m-d`.

`AsDateTimeArray` and `AsImmutableDateTimeArray` write date-times as `Y-m-d H:i:s.uP`, **with microseconds and the UTC offset**:

```php
$event->starts_at = [Carbon::parse('2026-08-20 14:30', 'Europe/Rome')];
// stored as {"2026-08-20 14:30:00.000000+02:00"}
```

This differs from Laravel's `datetime` cast, which writes `Grammar::getDateFormat()` (`Y-m-d H:i:s`, or the model's `$dateFormat`) without an offset, and without microseconds. Array casts ignore `$dateFormat`.

- **When the PostgreSQL session time zone matches the application time zone** (the configuration Laravel expects) and your Carbon instances are in the application time zone, the results are the same as with Laravel's `datetime` cast.
- **When they differ, values stay correct.** Without an offset, PostgreSQL reads a `timestamptz` value in the session time zone, silently shifting it. With the offset, `timestamptz[]` columns store the exact instant whatever the session time zone. `timestamp[]` columns (without time zone) ignore the offset and keep the wall-clock time of the instance, as Laravel does.
- **Values read from `timestamptz[]` columns are converted to the application time zone** (`app.timezone`), while Laravel's `Date::parse()` keeps a fixed offset such as `+02:00`.
- The raw attribute (`$model->getAttributes()`) contains the offset.
- Query builder operators format `DateTimeInterface` elements in the same way.

It is still a good idea to set the `timezone` of the `pgsql` connection to the application time zone, for Laravel's native (non-array) date columns:

```php
// config/database.php
'pgsql' => [
    // …
    'timezone' => env('DB_TIMEZONE', 'UTC'), // same as app.timezone
],
```

## Encrypted arrays

Encrypted arrays encrypt **each element separately**, with the application encrypter:

```php
use AndreaColzani\PgArray\Casts\AsEncryptedArray;

'secrets' => AsEncryptedArray::class,           // text[] of encrypted strings
'secrets' => AsEncryptedArray::collect(),
```

Any element type can be encrypted with `AsPgArray::encrypted()`:

```php
'pins' => AsPgArray::encrypted(PgArrayCast::Integer),
'birthdays' => AsPgArray::encrypted(PgArrayCast::Date, PgArrayContainer::Collection),
'statuses' => AsPgArray::encrypted(Status::class),
'addresses' => AsPgArray::encrypted(Address::class),
```

- The element is converted by its cast first, then encrypted; on read, it is decrypted and then converted.
- Ciphertexts are strings, so encrypted arrays always need `text[]` (or `varchar[]`) columns, whatever the element type.
- The array structure stays visible: dimensions, length and `NULL` elements are not encrypted. Every other element gets its own IV, so equal values produce different ciphertexts.
- This is different from Laravel's `encrypted:array` cast, which encrypts the whole array as a single JSON payload. Use the native cast when the structure must be hidden too.
- Encryption uses `Model::currentEncrypter()`, so `Model::encryptUsing()` and previous keys (`APP_PREVIOUS_KEYS`) work as with Laravel's encrypted casts. Invalid payloads throw Laravel's `DecryptException`.

## Hashed arrays

Hashed arrays hash each element with the configured hasher, like Laravel's `hashed` cast does for a single value. Hashing is one-way: elements are read back as hashes.

```php
use AndreaColzani\PgArray\Casts\AsHashedArray;

'recovery_codes' => AsHashedArray::class, // text[]
```

```php
$user->recovery_codes = ['alpha-123', 'bravo-456']; // stored as hashes
```

Verify values with `PgArrayHash`:

```php
use AndreaColzani\PgArray\Support\PgArrayHash;

PgArrayHash::check($code, $user->recovery_codes);     // bool

// Remove a consumed recovery code
$key = PgArrayHash::find($code, $user->recovery_codes); // int|string|null

if ($key !== null) {
    $codes = $user->recovery_codes;
    unset($codes[$key]);
    $user->recovery_codes = array_values($codes);
    $user->save();
}
```

- Values that are already hashed are stored unchanged, so the hashes read from the database can be assigned again (as above) without being hashed twice. They must match the configured hashing algorithm.
- Strings, integers, floats and `Stringable` objects are accepted. `NULL` elements stay `NULL`.
- `PgArrayHash` accepts arrays, Collections and `null`, and only checks top-level string elements.

## PostgreSQL special types

### `bytea`

```php
'files' => AsByteaArray::class, // bytea[]

$model->files = [file_get_contents('logo.png')];
```

Assigned strings are raw binary data, stored in the hex format (`\x89504e47…`). Both the hex and the legacy `escape` output formats are decoded when reading.

### `inet` and `macaddr`

```php
'ips' => AsInetArray::class,     // inet[]
'macs' => AsMacAddrArray::class, // macaddr[]

$model->ips = ['192.168.0.1', '2001:0DB8::1/64', '10.0.0.1/32'];
$model->ips;  // ['192.168.0.1', '2001:db8::1/64', '10.0.0.1']

$model->macs = ['08-00-2B-01-02-03', '0800.2b01.0203'];
$model->macs; // ['08:00:2b:01:02:03', '08:00:2b:01:02:03']
```

Values are validated when assigned and normalized as PostgreSQL outputs them: IPv4 or IPv6 addresses with an optional prefix (IPv6 compressed and lowercase, full-length prefixes omitted), and every MAC address format accepted by PostgreSQL.

### pgvector `vector`

Requires the [pgvector](https://github.com/pgvector/pgvector) extension.

```php
use AndreaColzani\PgArray\Casts\AsVectorArray;
use AndreaColzani\PgArray\Types\Vector;

'chunks' => AsVectorArray::class,              // vector[]
'chunks' => AsVectorArray::withDimensions(1536), // validates the dimensions of every element

$model->chunks = [new Vector([0.12, -0.5, 0.33])];

$vector = $model->chunks[0];
$vector->toArray();    // [0.12, -0.5, 0.33]
$vector->dimensions(); // 3
(string) $vector;      // '[0.12,-0.5,0.33]'
```

Elements are `Vector` objects rather than plain lists, because PHP arrays are dimensions of the PostgreSQL array. `Vector` is immutable, `Countable`, `Stringable` (pgvector format) and `JsonSerializable` (plain list). pgvector stores single-precision floats, so values read back may differ slightly from the assigned ones.

`AsVectorArray::withDimensions()` accepts a container as second argument: `AsVectorArray::withDimensions(1536, PgArrayContainer::Collection)`.

### PostGIS `geometry` and `geography`

Requires the [PostGIS](https://postgis.net) extension. The package does not parse geometries: `AsGeometryArray` and `AsGeographyArray` read them as the hex EWKB strings returned by PostgreSQL, and write WKT, EWKT or hex EWKB strings unchanged.

```php
'areas' => AsGeographyArray::class, // geography[]

$model->areas = ['SRID=4326;POLYGON((9.1 45.4, 9.2 45.4, 9.2 45.5, 9.1 45.4))'];
$model->areas; // ['0103000020E6100000…']
```

Points are best handled with the `Point` value object:

```php
use AndreaColzani\PgArray\Types\Point;

'locations' => AsPgArray::of(Point::class), // geometry[] or geography[]

$model->locations = [new Point(latitude: 45.4642, longitude: 9.19)]; // SRID 4326 by default

$model->locations[0]->latitude;  // 45.4642
$model->locations[0]->srid;      // 4326
json_encode($model->locations[0]); // GeoJSON: {"type":"Point","coordinates":[9.19,45.4642]}
```

`Point` writes EWKT (`SRID=4326;POINT(9.19 45.4642)`) and reads 2D EWKB, WKT and EWKT. Richer geometries, or objects of GIS libraries, can be mapped with an [external serializer](#external-serializers).

> [!NOTE]
> PostGIS separates array elements with `:` instead of `,` (`{0101…:0101…}`). The casts handle it automatically. For custom elements stored in PostGIS arrays, implement `Contracts\PgArrayDelimited` on the value object or the serializer. For the query builder, see [below](#postgis-arrays).

## Migrations

The `pgArray()` column type creates array columns:

```php
use AndreaColzani\PgArray\Enums\PgArrayType;

Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->pgArray('tags', PgArrayType::Text);                              // text[]
    $table->pgArray('scores', PgArrayType::Integer)->nullable();             // integer[] null
    $table->pgArray('ids', PgArrayType::Uuid)->default([]);                  // uuid[] default '{}'::uuid[]
    $table->pgArray('addresses', PgArrayType::Jsonb);                        // jsonb[]
});
```

Every native column modifier works (`nullable()`, `default()`, `comment()`, `change()`, …), plus the following type modifiers:

```php
$table->pgArray('codes', PgArrayType::Varchar)->length(50);                     // varchar(50)[]
$table->pgArray('prices', PgArrayType::Decimal)->precision(10, 2);              // decimal(10,2)[]
$table->pgArray('seen_at', PgArrayType::TimestampTz)->precision(6);             // timestamptz(6)[]
$table->pgArray('embeddings', PgArrayType::Vector)->size(1536);                 // vector(1536)[]
$table->pgArray('places', PgArrayType::Geography)->subtype('Point');            // geography(Point,4326)[]
$table->pgArray('areas', PgArrayType::Geometry)->subtype('Polygon')->srid(3857); // geometry(Polygon,3857)[]
$table->pgArray('matrix', PgArrayType::Integer)->dimensions(2);                 // integer[][]
```

Modifiers are validated immediately: a modifier that the type does not support, or an invalid value (e.g. a scale greater than the precision), throws an `InvalidDefinitionException`.

### Defaults

`default()` accepts PHP arrays and Collections, rendered as an array literal cast to the column type:

```php
$table->pgArray('labels', PgArrayType::Text)->default(['draft', 'new']);
// default '{draft,new}'::text[]

$table->pgArray('matrix', PgArrayType::Integer)->dimensions(2)->default([[1, 2], [3, 4]]);
// default '{{1,2},{3,4}}'::integer[][]
```

Elements can be scalars, `null`, nested arrays, backed enums and `Stringable` objects. Strings and `DB::raw()` expressions work as in Laravel.

### `NULL` elements

`nullable()` allows the **column** to be `NULL`. PostgreSQL cannot forbid `NULL` **elements** in the type, so `withoutNullElements()` adds a check constraint:

```php
$table->pgArray('labels', PgArrayType::Text)->withoutNullElements()->default([]);
// text[] check (array_position("labels", NULL) is null) not null default '{}'::text[]
```

It is only supported by one-dimensional arrays, when creating or adding a column.

### Type definitions

Types can also be described with `PgArrayTypeDefinition`, which validates the modifiers and renders the SQL type:

```php
use AndreaColzani\PgArray\Database\PgArrayTypeDefinition;

$table->pgArray('skus', PgArrayTypeDefinition::varchar(20)); // varchar(20)[]

PgArrayTypeDefinition::decimal(10, 2)->toSql();                // decimal(10,2)
PgArrayTypeDefinition::timestampTz(6)->toArraySql();           // timestamptz(6)[]
PgArrayTypeDefinition::geography('Point', 4326)->toArraySql(); // geography(Point,4326)[]
PgArrayTypeDefinition::of(PgArrayType::Integer)->toArraySql(2); // integer[][]
```

Named constructors: `of()`, `char()`, `varchar()`, `decimal()`, `numeric()`, `time()`, `timeTz()`, `timestamp()`, `timestampTz()`, `vector()`, `geometry()`, `geography()`. Chained column modifiers override the definition passed to `pgArray()`.

### Altering and dropping columns

Use Laravel's `change()`, restating the full column definition. When PostgreSQL cannot convert the existing values implicitly, add a `USING` expression:

```php
Schema::table('posts', function (Blueprint $table) {
    $table->pgArray('prices', PgArrayType::Decimal)->precision(12, 2)->change();
    $table->pgArray('codes', PgArrayType::Integer)->using('codes::integer[]')->change();
    $table->dropColumn('tags');
});
```

`using()` works on every supported Laravel version. It cannot be combined with `collation()` in the same `change()`: change the collation separately.

> [!NOTE]
> PostgreSQL does not enforce the declared number of dimensions: an `integer[][]` column accepts one-dimensional arrays, and is introspected as `integer[]`.

## Query builder

The array operators are available on the query builder, on Eloquent builders and on relations:

```php
Post::query()
    ->wherePgArrayContains('tags', ['php', 'laravel'])        // "tags" @> ?
    ->wherePgArrayContainedBy('tags', $allowedTags)           // "tags" <@ ?
    ->orWherePgArrayOverlaps('tags', collect(['vue', 'php'])) // or "tags" && ?
    ->wherePgArrayDoesntContain('tags', 'legacy')             // not ("tags" @> ?)
    ->get();
```

| Operator | Methods |
|---|---|
| `@>` contains | `wherePgArrayContains`, `orWherePgArrayContains`, `wherePgArrayDoesntContain`, `orWherePgArrayDoesntContain` |
| `<@` is contained by | `wherePgArrayContainedBy`, `orWherePgArrayContainedBy`, `wherePgArrayNotContainedBy`, `orWherePgArrayNotContainedBy` |
| `&&` overlaps | `wherePgArrayOverlaps`, `orWherePgArrayOverlaps`, `wherePgArrayDoesntOverlap`, `orWherePgArrayDoesntOverlap` |

- Values can be arrays, Collections or a single value (wrapped into a one-element array). Elements can be scalars, `null`, nested arrays, backed enums, dates (written like the [date-time casts](#dates-and-time-zones)) and `Stringable` objects such as UUIDs. Passing `null` as the value throws an `InvalidValueException`.
- The values are sent as a single binding (e.g. `{php,laravel}`), which PostgreSQL types after the column. Pass a type as third argument to add an explicit cast, for example with expressions:

  ```php
  ->wherePgArrayOverlaps('ids', $ids, PgArrayType::BigInt) // "ids" && ?::bigint[]
  ```

- Columns can be qualified (`posts.tags`) or expressions.
- Empty values follow PostgreSQL: `@> '{}'` is always true, `<@ '{}'` matches only empty arrays, `&& '{}'` is always false.
- The negated methods (`not (...)`) exclude rows where the column is `NULL`, like `whereJsonDoesntContain`.
- Multidimensional values follow PostgreSQL too: the operators compare elements regardless of dimensions.

### Concatenation

`pgArrayAppend()` and `pgArrayPrepend()` update the matching rows with the `||` operator, and return the number of affected rows, like `increment()`:

```php
Post::whereKey($id)->pgArrayAppend('tags', ['new', 'featured']); // set "tags" = "tags" || '{new,featured}'
Post::whereKey($id)->pgArrayPrepend('tags', 'first');            // set "tags" = '{first}' || "tags"

// Extra columns to update, as with increment()
Post::whereKey($id)->pgArrayAppend('tags', 'archived', null, ['archived_at' => now()]);
```

On Eloquent builders, `updated_at` is touched. Appending to a `NULL` column yields the appended values.

### PostGIS arrays

The query builder does not know the column type, so filters and concatenations on `geometry[]` / `geography[]` columns with more than one value need the type, to use the `:` delimiter:

```php
->wherePgArrayOverlaps('areas', ['SRID=4326;POINT(9.19 45.46)', 'SRID=4326;POINT(12.5 41.9)'], PgArrayType::Geometry)
```

The query builder macros, like the migration helper, only work with the PostgreSQL driver: other drivers throw an `UnsupportedDriverException`.

## Exceptions

Every exception thrown by the package implements `AndreaColzani\PgArray\Exceptions\PgArrayException`, and extends the SPL exception of its category:

| Exception | Extends | Thrown for |
|---|---|---|
| `UnsupportedElementException` | `InvalidArgumentException` | element types that cannot be resolved (unknown classes, pure enums, invalid serializers) |
| `InvalidDefinitionException` | `InvalidArgumentException` | invalid cast arguments, type modifiers or column modifiers |
| `InvalidValueException` | `UnexpectedValueException` | values that cannot be cast, serialized or parsed |
| `UnsupportedDriverException` | `RuntimeException` | PostgreSQL-only features used with another database driver |

```php
use AndreaColzani\PgArray\Exceptions\PgArrayException;

try {
    $post->statuses = ['unknown'];
} catch (PgArrayException $e) {
    // …
}
```

## Supported PostgreSQL types

| PostgreSQL type | `PgArrayType` | Eloquent cast |
|---|---|---|
| `char`, `varchar`, `text` | `Char`, `Varchar`, `Text` | `AsStringArray`, `AsStringableArray`, enums, value objects |
| `smallint`, `integer`, `bigint` | `SmallInt`, `Integer`, `BigInt` | `AsIntegerArray`, int-backed enums |
| `real` | `Real` | `AsRealArray` |
| `double precision` | `DoublePrecision` | `AsDoubleArray`, `AsFloatArray` |
| `decimal`, `numeric` | `Decimal`, `Numeric` | `AsDecimalArray` |
| `boolean` | `Boolean` | `AsBooleanArray` |
| `date` | `Date` | `AsDateArray`, `AsImmutableDateArray` |
| `time`, `timetz` | `Time`, `TimeTz` | `AsStringArray` |
| `timestamp`, `timestamptz` | `Timestamp`, `TimestampTz` | `AsDateTimeArray`, `AsImmutableDateTimeArray` |
| `uuid` | `Uuid` | `AsUuidArray`, `AsStringArray` |
| `bytea` | `Bytea` | `AsByteaArray` |
| `inet` | `Inet` | `AsInetArray` |
| `macaddr` | `MacAddr` | `AsMacAddrArray` |
| `json`, `jsonb` | `Json`, `Jsonb` | `AsPgArray::of()` with a `PgArrayJsonValue` class or a JSON serializer |
| `vector` (pgvector) | `Vector` | `AsVectorArray` |
| `geometry`, `geography` (PostGIS) | `Geometry`, `Geography` | `AsGeometryArray`, `AsGeographyArray`, `AsPgArray::of(Point::class)` |

`PgArrayCast` describes how elements are converted on the PHP side (Eloquent casts), while `PgArrayType` describes the PostgreSQL type (migrations and query builder).

## PHP / Laravel compatibility

| | Versions |
|---|---|
| PHP | 8.3, 8.4, 8.5 |
| Laravel | 12, 13 |
| PostgreSQL | 15, 16, 17, 18 |
| PostGIS (optional) | 3.5, 3.6 |
| pgvector (optional) | any version packaged for the PostgreSQL release |

The CI matrix runs the test suite on Ubuntu and Windows for every PHP and Laravel version, and the integration suite against real PostgreSQL servers with PostGIS and pgvector.

## Testing

```bash
composer test      # Pest
composer analyse   # PHPStan
composer format    # Pint
```

The integration tests (group `pgsql`) run against a real PostgreSQL database, configured with environment variables:

| Variable | Default |
|---|---|
| `PGARRAY_DB_HOST` | `127.0.0.1` |
| `PGARRAY_DB_PORT` | `5432` |
| `PGARRAY_DB_DATABASE` | `pgarray_testing` |
| `PGARRAY_DB_USERNAME` | `postgres` |
| `PGARRAY_DB_PASSWORD` | (empty) |

The database must use the `UTF8` encoding. The tests are skipped when PostgreSQL is not reachable, and the pgvector / PostGIS tests when the extension is not installed. Set `PGARRAY_REQUIRE_DB=true` to make them fail instead, as in CI.

```bash
vendor/bin/pest --exclude-group=pgsql   # unit and feature tests only
vendor/bin/pest --group=pgsql           # integration tests only
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please report security vulnerabilities privately through [GitHub security advisories](../../security/advisories/new).

## Credits

- [Andrea Colzani](https://github.com/andrecolza)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
