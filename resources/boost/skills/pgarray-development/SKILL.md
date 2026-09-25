---
name: pgarray-development
description: Work with PostgreSQL array columns (text[], integer[], uuid[], jsonb[], vector[], geometry[] …) in Laravel using andreacolzani/laravel-pgarray. Activate when creating or altering array columns in migrations ($table->pgArray()), adding array casts to Eloquent models (AsStringArray, AsIntegerArray, AsPgArray::of() …), storing enums, value objects or JSON objects in arrays, encrypting or hashing array elements, querying arrays with @>, <@, && or appending with || (wherePgArrayContains(), wherePgArrayOverlaps(), pgArrayAppend() …), or debugging how array values are stored, parsed or compared.
---

# Laravel PostgreSQL Arrays (`andreacolzani/laravel-pgarray`)

This package gives Laravel first-class support for PostgreSQL array columns. It provides:

1. **Eloquent casts** that turn PostgreSQL array literals (`{php,"hello, world",NULL}`) into PHP arrays or Collections of typed elements, and back.
2. A **`pgArray()` migration column type** with type modifiers (`varchar(50)[]`, `numeric(10,2)[]`, `vector(1536)[]`, …).
3. **Query builder macros** for PostgreSQL's array operators (`@>`, `<@`, `&&`, `||`).

It only works with the **PostgreSQL** database driver. Supported versions: PHP 8.3+, Laravel 12 / 13, PostgreSQL 15+.

Root namespace: `AndreaColzani\PgArray`.

## When to use this package (and when not to)

Use it whenever a model attribute maps to a **native PostgreSQL array column** (`text[]`, `integer[]`, …).

- Do **not** use Laravel's native `array`, `json`, `collection`, `AsArrayObject` or `AsCollection` casts for PostgreSQL array columns: those casts JSON-encode the value (`["a","b"]`), which PostgreSQL rejects for `text[]` columns (it expects `{a,b}`).
- Do **not** hand-build array literals with `implode()` / `'{'.…'}'` or `DB::raw()`: quoting, escaping, `NULL` elements and multidimensional arrays are easy to get wrong. Always go through the casts, the migration helper or the query builder macros.
- Do **not** create array columns with `$table->json()` or with `DB::statement('ALTER TABLE … text[]')`: use `$table->pgArray()`.
- If the data is a structured document rather than a list of homogeneous values, a regular `jsonb` column with Laravel's `AsArrayObject` / `AsCollection` casts may be a better fit. Use array columns for lists of scalars (tags, ids, codes, scores, dates) or lists of homogeneous objects that need array operators or GIN indexes.

## Quick reference

```php
// Migration
use AndreaColzani\PgArray\Enums\PgArrayType;

$table->pgArray('tags', PgArrayType::Text)->default([]);

// Model
use AndreaColzani\PgArray\Casts\AsStringArray;

protected function casts(): array
{
    return ['tags' => AsStringArray::class];
}

// Usage
$post->tags = ['php', 'laravel'];
$post->save();

Post::wherePgArrayContains('tags', 'laravel')->get();
Post::whereKey($post->id)->pgArrayAppend('tags', 'featured');
```

---

## 1. Migrations: `$table->pgArray()`

`pgArray(string $column, PgArrayType|PgArrayTypeDefinition $type)` is a `Blueprint` macro. It returns an `AndreaColzani\PgArray\Database\PgArrayColumnDefinition`, which extends Laravel's `ColumnDefinition`, so **every native column modifier still works** (`nullable()`, `default()`, `comment()`, `after()`, `change()`, …).

### Element types: `PgArrayType`

`AndreaColzani\PgArray\Enums\PgArrayType` describes the **PostgreSQL** element type:

| Case | SQL type | Notes |
|---|---|---|
| `Char`, `Varchar`, `Text` | `char`, `varchar`, `text` | `length()` for `char` / `varchar` |
| `SmallInt`, `Integer`, `BigInt` | `smallint`, `integer`, `bigint` | |
| `Real`, `DoublePrecision` | `real`, `double precision` | |
| `Decimal`, `Numeric` | `decimal`, `numeric` | `precision($precision, $scale)` |
| `Boolean` | `boolean` | |
| `Date` | `date` | |
| `Time`, `TimeTz`, `Timestamp`, `TimestampTz` | `time`, `timetz`, `timestamp`, `timestamptz` | `precision($fractionalSeconds)` (0–6) |
| `Bytea` | `bytea` | |
| `Uuid` | `uuid` | |
| `Inet`, `MacAddr` | `inet`, `macaddr` | |
| `Json`, `Jsonb` | `json`, `jsonb` | |
| `Vector` | `vector` | needs the pgvector extension; `size($dimensions)` |
| `Geometry`, `Geography` | `geometry`, `geography` | needs PostGIS; `subtype()`, `srid()` |

### Examples

```php
use AndreaColzani\PgArray\Enums\PgArrayType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Simple types
            $table->pgArray('tags', PgArrayType::Text);                            // text[] not null
            $table->pgArray('related_ids', PgArrayType::BigInt)->nullable();       // bigint[] null
            $table->pgArray('supplier_ids', PgArrayType::Uuid)->default([]);       // uuid[] default '{}'::uuid[]

            // Type modifiers
            $table->pgArray('skus', PgArrayType::Varchar)->length(32);             // varchar(32)[]
            $table->pgArray('prices', PgArrayType::Decimal)->precision(10, 2);     // decimal(10,2)[]
            $table->pgArray('restocked_at', PgArrayType::TimestampTz)->precision(6); // timestamptz(6)[]

            // Multidimensional arrays
            $table->pgArray('size_matrix', PgArrayType::Integer)->dimensions(2);   // integer[][]

            // JSON elements
            $table->pgArray('variants', PgArrayType::Jsonb)->default([]);          // jsonb[]

            // Defaults from PHP arrays / Collections
            $table->pgArray('channels', PgArrayType::Text)->default(['web', 'store']); // default '{web,store}'::text[]

            // Forbid NULL elements (adds a CHECK constraint)
            $table->pgArray('labels', PgArrayType::Text)->withoutNullElements()->default([]);

            $table->timestamps();
        });
    }
};
```

### Type modifiers

| Modifier | Types | Result |
|---|---|---|
| `length(int $length)` | `Char`, `Varchar` | `varchar(50)[]` |
| `precision(int $precision, ?int $scale = null)` | `Decimal`, `Numeric` | `numeric(10,2)[]` |
| `precision(int $precision)` | `Time`, `TimeTz`, `Timestamp`, `TimestampTz` | `timestamp(6)[]` |
| `size(int $dimensions)` | `Vector` | `vector(1536)[]` |
| `subtype(string $subtype)` | `Geometry`, `Geography` | `geography(Point,4326)[]` |
| `srid(int $srid)` | `Geometry`, `Geography` | `geometry(Polygon,3857)[]` |
| `dimensions(int $dimensions)` | all | `integer[][]` |
| `withoutNullElements()` | all (one-dimensional only) | `check (array_position(col, NULL) is null)` |

Rules:

- Modifiers are validated **immediately** and throw `AndreaColzani\PgArray\Exceptions\InvalidDefinitionException` when the type does not support them (e.g. `length()` on `Integer`) or the values are invalid (scale greater than precision, time precision above 6, vector dimensions above 16000, unknown PostGIS subtype, …).
- `geography` with a subtype but no SRID defaults to SRID 4326. A SRID without a subtype uses the generic `Geometry` subtype.
- `dimensions()` only affects the declared type: **PostgreSQL does not enforce the number of dimensions**, and introspects `integer[][]` as `integer[]`.
- `nullable()` concerns the **column**, `withoutNullElements()` concerns the **elements**. `withoutNullElements()` only works on one-dimensional arrays and cannot be combined with `change()` (add the constraint in a separate statement).

### Defaults

`default()` accepts PHP arrays and `Arrayable` values (Collections). Elements may be scalars, `null`, nested arrays, `BackedEnum` cases and `Stringable` objects. The default is rendered as a typed literal:

```php
$table->pgArray('roles', PgArrayType::Text)->default([Role::Viewer]);            // '{viewer}'::text[]
$table->pgArray('grid', PgArrayType::Integer)->dimensions(2)->default([[0, 0], [0, 0]]); // '{{0,0},{0,0}}'::integer[][]
$table->pgArray('tags', PgArrayType::Text)->default([]);                          // '{}'::text[]
```

Strings and `DB::raw()` expressions keep Laravel's usual behavior.

### `PgArrayTypeDefinition`

For reusable or programmatic type definitions, use `AndreaColzani\PgArray\Database\PgArrayTypeDefinition`. Chained column modifiers override it.

```php
use AndreaColzani\PgArray\Database\PgArrayTypeDefinition;

$table->pgArray('skus', PgArrayTypeDefinition::varchar(20));                // varchar(20)[]
$table->pgArray('amounts', PgArrayTypeDefinition::numeric(12, 4));          // numeric(12,4)[]
$table->pgArray('embeddings', PgArrayTypeDefinition::vector(1536));         // vector(1536)[]
$table->pgArray('stops', PgArrayTypeDefinition::geography('Point', 4326));  // geography(Point,4326)[]

PgArrayTypeDefinition::decimal(10, 2)->toSql();                 // 'decimal(10,2)'
PgArrayTypeDefinition::timestampTz(6)->toArraySql();            // 'timestamptz(6)[]'
PgArrayTypeDefinition::of(PgArrayType::Integer)->toArraySql(2); // 'integer[][]'
```

Named constructors: `of()`, `char()`, `varchar()`, `decimal()`, `numeric()`, `time()`, `timeTz()`, `timestamp()`, `timestampTz()`, `vector()`, `geometry()`, `geography()`.

### Altering and dropping columns

Use Laravel's `change()` and restate the **full** column definition. When PostgreSQL cannot convert the existing data implicitly, add `using()` (string or expression); it is supported on every Laravel version the package supports.

```php
Schema::table('products', function (Blueprint $table) {
    $table->pgArray('prices', PgArrayType::Decimal)->precision(12, 2)->change();
    $table->pgArray('related_ids', PgArrayType::BigInt)->using('related_ids::bigint[]')->change();
    $table->pgArray('codes', PgArrayType::Integer)->using(DB::raw('codes::integer[]'))->change();

    $table->dropColumn('legacy_tags'); // native, no dedicated helper
});
```

`using()` cannot be combined with `collation()` in the same `change()`: change the collation separately.

### Indexes

Array operators (`@>`, `<@`, `&&`) can use **GIN** indexes. Laravel's `index()` accepts the algorithm as third argument:

```php
$table->index('tags', 'products_tags_gin', 'gin');
```

### PostgreSQL extensions

`vector` requires [pgvector](https://github.com/pgvector/pgvector) and `geometry` / `geography` require [PostGIS](https://postgis.net). Enable them in a migration that runs before the columns are created:

```php
DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
```

---

## 2. Eloquent casts

All casts live in `AndreaColzani\PgArray\Casts`. Declare them in the model's `casts()` method.

### Built-in castables

| Castable | PHP elements | Typical column |
|---|---|---|
| `AsStringArray` | `string` | `text[]`, `varchar[]`, `char[]`, `time[]`, `timetz[]` |
| `AsIntegerArray` | `int` | `smallint[]`, `integer[]`, `bigint[]` |
| `AsDecimalArray` | `string` (exact decimal) | `numeric[]`, `decimal[]` |
| `AsFloatArray`, `AsDoubleArray` | `float` | `double precision[]` |
| `AsRealArray` | `float` | `real[]` |
| `AsBooleanArray` | `bool` | `boolean[]` |
| `AsDateArray` / `AsImmutableDateArray` | `Carbon\Carbon` / `Carbon\CarbonImmutable` | `date[]` |
| `AsDateTimeArray` / `AsImmutableDateTimeArray` | `Carbon\Carbon` / `Carbon\CarbonImmutable` | `timestamp[]`, `timestamptz[]` |
| `AsUuidArray` | `Ramsey\Uuid\UuidInterface` | `uuid[]` |
| `AsUlidArray` | `Symfony\Component\Uid\Ulid` | `char(26)[]`, `text[]` (**not** `uuid[]`) |
| `AsStringableArray` | `Illuminate\Support\Stringable` | `text[]` |
| `AsUriArray` | `Illuminate\Support\Uri` | `text[]` |
| `AsByteaArray` | binary `string` | `bytea[]` |
| `AsInetArray` | normalized `string` | `inet[]` |
| `AsMacAddrArray` | normalized `string` | `macaddr[]` |
| `AsVectorArray` | `AndreaColzani\PgArray\Types\Vector` | `vector[]` |
| `AsGeometryArray` / `AsGeographyArray` | `string` (hex EWKB) | `geometry[]` / `geography[]` |
| `AsEncryptedArray` | `string` (decrypted) | `text[]` |
| `AsHashedArray` | `string` (hash) | `text[]` |

```php
use AndreaColzani\PgArray\Casts\AsBooleanArray;
use AndreaColzani\PgArray\Casts\AsDateArray;
use AndreaColzani\PgArray\Casts\AsDecimalArray;
use AndreaColzani\PgArray\Casts\AsIntegerArray;
use AndreaColzani\PgArray\Casts\AsStringArray;
use AndreaColzani\PgArray\Casts\AsUuidArray;

class Product extends Model
{
    protected function casts(): array
    {
        return [
            'tags' => AsStringArray::class,
            'related_ids' => AsIntegerArray::class,
            'prices' => AsDecimalArray::class,
            'flags' => AsBooleanArray::class,
            'holidays' => AsDateArray::class,
            'supplier_ids' => AsUuidArray::class,
        ];
    }
}
```

### Collections

Every castable has a static `collect()` method that returns the value as an `Illuminate\Support\Collection` instead of a PHP array:

```php
'tags' => AsStringArray::collect(),

$product->tags;                       // Collection(['new', 'sale'])
$product->tags->contains('sale');     // true
$product->tags = collect(['a', 'b']); // Collections can be assigned
$product->tags = ['a', 'b'];          // arrays too
```

Only the top level becomes a Collection: nested dimensions of a multidimensional array stay plain PHP arrays.

### The generic cast: `AsPgArray`

`AsPgArray::of(PgArrayCast|string $type, PgArrayContainer $container = PgArrayContainer::Array)` accepts any element type:

- a `AndreaColzani\PgArray\Enums\PgArrayCast` case (the built-in element casts, PHP side);
- the class-string of a `BackedEnum`;
- the class-string of a class implementing `PgArrayValue` or `PgArrayJsonValue`;
- the class-string of any class mapped to an external serializer.

```php
use AndreaColzani\PgArray\Casts\AsPgArray;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;

'related_ids' => AsPgArray::of(PgArrayCast::Integer),                             // = AsIntegerArray::class
'related_ids' => AsPgArray::of(PgArrayCast::Integer, PgArrayContainer::Collection), // = AsIntegerArray::collect()
'statuses' => AsPgArray::of(OrderStatus::class),
'emails' => AsPgArray::of(Email::class),
'addresses' => AsPgArray::of(Address::class, PgArrayContainer::Collection),
'prices' => AsPgArray::of(Money::class),
'tags' => AsPgArray::class,                                                        // no arguments: string elements
```

`PgArrayCast` cases: `Boolean`, `Date`, `DateTime`, `ImmutableDate`, `ImmutableDateTime`, `Decimal`, `Double`, `Float`, `Integer`, `Real`, `String`, `Stringable`, `Hashed`, `Bytea`, `Inet`, `MacAddr`, `Vector`, `Geometry`, `Geography`, `Uri`, `Uuid`, `Ulid`.

**`PgArrayCast` vs `PgArrayType`:** `PgArrayCast` describes how elements are converted on the PHP side (Eloquent casts). `PgArrayType` describes the PostgreSQL column type (migrations and query builder). Never pass a `PgArrayType` to `AsPgArray::of()`, or a `PgArrayCast` to `pgArray()`.

Unknown class-strings throw `UnsupportedElementException` when the cast definition is built.

### How values behave

```php
$product->related_ids = [10, '20', null];  // numeric strings are cast to int
$product->save();                          // stored as {10,20,NULL}
$product->fresh()->related_ids;            // [10, 20, null]

$product->tags = [];                       // stored as {}   → read back as []
$product->tags = null;                     // stored as NULL → read back as null
$product->tags = ['NULL', '', 'a,b', 'say "hi"', 'back\\slash', 'ünïcødé'];
// all preserved exactly: the string 'NULL' is not a NULL element, '' stays ''
```

- `NULL` column ↔ `null`; empty array `{}` ↔ `[]`; `NULL` elements ↔ `null` elements.
- Quoting and escaping are handled for any character.
- Dirty checking works on the serialized value: assigning the same values does not make the attribute dirty.

### Mutating values in place

Array attributes are computed on access, so **modifying the returned PHP array in place has no effect** (PHP emits "Indirect modification of overloaded property … has no effect"):

```php
// WRONG: silently ignored
$product->tags[] = 'featured';
unset($product->tags[0]);

// RIGHT: read, modify, assign back
$tags = $product->tags;
$tags[] = 'featured';
$product->tags = $tags;

// RIGHT: build a new array
$product->tags = [...$product->tags, 'featured'];
$product->tags = array_values(array_diff($product->tags, ['legacy']));
```

With a **Collection** container (`collect()` / `PgArrayContainer::Collection`), the Collection object is cached by Eloquent, so in-place Collection mutations (`push()`, `put()`, `pull()`, `forget()`, …) **are** persisted on save:

```php
$product->tags->push('featured'); // persisted on $product->save()
```

Immutable Collection methods (`map()`, `filter()`, `merge()`, …) return new instances: assign the result back.

To append/prepend without loading the model at all, use `pgArrayAppend()` / `pgArrayPrepend()` (section 6).

### Multidimensional arrays

Every cast supports nested arrays; element casts are applied recursively:

```php
'size_matrix' => AsIntegerArray::class, // integer[][] column

$product->size_matrix = [[36, 38], [40, 42]]; // stored as {{36,38},{40,42}}
$product->size_matrix;                        // [[36, 38], [40, 42]]
```

PostgreSQL requires **rectangular** arrays: all sub-arrays at the same level must have the same length (`[[1, 2], [3]]` is rejected by the database). Because PHP arrays are interpreted as dimensions, an element can never be a PHP array (use objects for structured elements: see JSON objects).

### Numbers

- `AsDecimalArray` returns **strings** (`'19.90'`) to preserve exact precision. Do not cast them to float for money: use `bcmath`, `brick/money` or similar.
- `AsFloatArray` / `AsDoubleArray` / `AsRealArray` return floats. Floats are written with the shortest representation that round trips (`0.1 + 0.2` → `0.30000000000000004`). `NAN`, `INF`, `-INF` are written as `NaN`, `Infinity`, `-Infinity` and read back as the PHP constants.
- `real` is single precision in PostgreSQL: values read back may differ slightly from what was assigned. Prefer `double precision` unless storage matters.
- `AsIntegerArray` casts with `(int)`.

### Booleans

`AsBooleanArray` writes `t` / `f` and reads `t`, `true`, `1`, `yes`, `on` / `f`, `false`, `0`, `no`, `off` (case-insensitive). Assigned values are cast with `(bool)`.

---

## 3. Dates and time zones

- `AsDateArray` / `AsImmutableDateArray` write `Y-m-d` and read Carbon instances at midnight.
- `AsDateTimeArray` / `AsImmutableDateTimeArray` write **`Y-m-d H:i:s.uP`**: microseconds **and UTC offset**, e.g. `2026-08-20 14:30:00.000000+02:00`. Assigned values may be `DateTimeInterface` instances or strings parsed by Carbon.
- Values read from `timestamptz[]` are converted to the **application time zone** (`config('app.timezone')` / `date_default_timezone_get()`).
- `timestamp[]` (without time zone) ignores the offset and stores the wall-clock time of the instance, like Laravel.
- `time[]` / `timetz[]` have no dedicated cast: use `AsStringArray`.

This differs from Laravel's `datetime` cast, which writes `Y-m-d H:i:s` without offset (or the model's `$dateFormat`, which **array casts ignore**). Results are identical when the PostgreSQL session time zone equals the application time zone; when they differ, the array casts still store the correct instant.

Recommendation: set the `timezone` option of the `pgsql` connection in `config/database.php` to the application time zone, for consistency with Laravel's non-array date columns.

```php
'meetings' => AsImmutableDateTimeArray::class, // timestamptz[]

$event->meetings = [
    CarbonImmutable::parse('2026-03-10 09:00', 'America/New_York'),
    '2026-03-11 15:30:00+00:00',
];
$event->save();

$event->fresh()->meetings[0]->toIso8601String(); // same instant, in app.timezone
```

---

## 4. Enums, value objects, JSON objects and serializers

When `AsPgArray::of()` receives a class-string, the element caster is resolved in this order of precedence:

1. an **external serializer** mapped to the class (configuration / registry, then `#[PgArraySerializer]` attribute, then `PgArraySerializable` contract);
2. **`PgArrayJsonValue`** implementations (JSON elements);
3. **`PgArrayValue`** implementations (scalar elements);
4. **`BackedEnum`** classes.

Pure (non-backed) enums and unsupported classes throw `UnsupportedElementException`.

### 4.1 Backed enums

```php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
}

enum Priority: int
{
    case Low = 1;
    case High = 2;
}

'statuses' => AsPgArray::of(OrderStatus::class),   // text[] column
'priorities' => AsPgArray::of(Priority::class),    // integer[] / smallint[] column
'statuses' => AsPgArray::of(OrderStatus::class, PgArrayContainer::Collection),
```

```php
$order->statuses = [OrderStatus::Pending, 'paid']; // cases or backing values
$order->statuses;                                  // [OrderStatus::Pending, OrderStatus::Paid]

$order->statuses = ['refunded'];                   // throws InvalidValueException
```

- Assign cases or valid backing values; read back always returns cases.
- Int-backed enums accept integers and integer strings (`'2'`).
- Invalid values throw `InvalidValueException` both on assignment and on read.
- Cases of a different enum are rejected.

### 4.2 Value objects: `PgArrayValue`

Implement `AndreaColzani\PgArray\Contracts\PgArrayValue` to store a class as a **scalar** element (string, int, float, bool or null):

```php
use AndreaColzani\PgArray\Contracts\PgArrayValue;
use InvalidArgumentException;

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
        return $this->address; // logical value, NOT an encoded PostgreSQL string
    }

    public static function fromPgArrayValue(mixed $value): static
    {
        return new static((string) $value);
    }
}
```

```php
'emails' => AsPgArray::of(Email::class), // text[]

$user->emails = [new Email('Ada@Example.com'), 'grace@example.com'];
$user->emails; // [Email('ada@example.com'), Email('grace@example.com')]
```

Contract rules:

- `toPgArrayValue()` returns a **logical PHP value** (never a pre-quoted PostgreSQL literal). For plain `PgArrayValue`, only `string|int|float|bool|null` are allowed; arrays/objects throw `InvalidValueException` (use `PgArrayJsonValue` for structured values). Returning `null` stores a `NULL` element.
- `fromPgArrayValue()` receives the element **as text** read from PostgreSQL (e.g. `'42'` for an `integer[]` column, so cast it). It is never called with `null`: `NULL` elements stay `null`.
- On assignment, raw values that are not instances of the class (e.g. `'grace@example.com'`) are normalized through `fromPgArrayValue()` then `toPgArrayValue()`, so the class validates them. Objects of other classes are rejected.
- A `BackedEnum` that implements `PgArrayValue` uses the contract instead of the automatic enum support.

Integer example:

```php
final class Cents implements PgArrayValue
{
    public function __construct(public readonly int $amount) {}

    public function toPgArrayValue(): int
    {
        return $this->amount;
    }

    public static function fromPgArrayValue(mixed $value): static
    {
        return new static((int) $value);
    }
}

'refunds' => AsPgArray::of(Cents::class), // bigint[] column
```

### 4.3 JSON objects: `PgArrayJsonValue`

For structured elements, use a `json[]` or `jsonb[]` column (an array whose elements are JSON documents) and implement the **marker contract** `AndreaColzani\PgArray\Contracts\PgArrayJsonValue` (it extends `PgArrayValue`). The optional trait `AndreaColzani\PgArray\Concerns\InteractsWithPgArrayJson` provides both methods:

```php
use AndreaColzani\PgArray\Concerns\InteractsWithPgArrayJson;
use AndreaColzani\PgArray\Contracts\PgArrayJsonValue;

final class Address implements PgArrayJsonValue
{
    use InteractsWithPgArrayJson;

    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
        public readonly ?string $postalCode = null,
    ) {}
}
```

```php
// Migration
$table->pgArray('addresses', PgArrayType::Jsonb)->default([]);

// Model
'addresses' => AsPgArray::of(Address::class),

// Usage
$customer->addresses = [
    new Address('350 Fifth Avenue', 'New York', 'US', '10118'),
    new Address('1 Chome-1-2 Oshiage', 'Tokyo', 'JP'),
];
$customer->save();

$customer->fresh()->addresses[1]->city; // 'Tokyo'
```

What the trait does:

- `toPgArrayValue()` returns the object's properties (`get_object_vars()`) keyed by name. Nested `PgArrayValue` objects are converted through their own `toPgArrayValue()`.
- `fromPgArrayValue()` passes the decoded keys to the **constructor as named arguments**, so constructor parameter names must match the stored keys (promoted properties are the easiest way). Nested `PgArrayValue` objects and `BackedEnum`s are restored from the parameter's type. Unknown keys are ignored (removing a property keeps old rows readable); missing required parameters fail.

Nested objects and enums:

```php
enum Country: string
{
    case UnitedStates = 'US';
    case Japan = 'JP';
}

final class Address implements PgArrayJsonValue
{
    use InteractsWithPgArrayJson;

    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly Country $country,  // restored from 'US'
        public readonly ?Email $contact = null, // restored through Email::fromPgArrayValue()
    ) {}
}
```

Custom structure (override one or both methods, or implement them without the trait):

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
        // $value is the decoded JSON: objects are associative arrays
        return new static($value['street'], $value['city']);
    }
}
```

JSON rules:

- Encoding uses `JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION`; decoding returns associative arrays. Invalid JSON throws `JsonException`.
- SQL `NULL` elements, JSON `null` elements and `null` logical values are all `null`.
- On assignment, instances of the class and **raw JSON strings** (normalized through `fromPgArrayValue()`) are accepted. Raw PHP arrays are **not** accepted as elements (they are array dimensions): wrap them in the object.
- There is no "raw JSON" cast returning associative arrays: always use a `PgArrayJsonValue` class (or a JSON serializer).

### 4.4 External serializers

Use a serializer when the element class cannot implement the contracts (third-party classes such as `Money`), needs complex serialization, or when several classes share the same rules.

```php
use AndreaColzani\PgArray\Contracts\PgArrayValueSerializer;
use Money\Currency;
use Money\Money;

final class MoneySerializer implements PgArrayValueSerializer
{
    /** @param Money $value */
    public function serialize(object $value): mixed
    {
        return $value->getAmount().' '.$value->getCurrency()->getCode(); // '1999 USD'
    }

    public function deserialize(mixed $value, string $class): object
    {
        [$amount, $currency] = explode(' ', (string) $value);

        return new Money($amount, new Currency($currency));
    }
}
```

Map classes to serializers in one of three ways (highest precedence first):

```php
// 1. config/pgarray.php (publish: php artisan vendor:publish --tag="laravel-pgarray-config")
return [
    'serializers' => [
        Money\Money::class => App\Serializers\MoneySerializer::class,
    ],
];

// 1b. …or programmatically, e.g. in AppServiceProvider::boot()
use AndreaColzani\PgArray\Support\PgArraySerializerRegistry;

$this->app->make(PgArraySerializerRegistry::class)
    ->register([Money::class, Price::class], MoneySerializer::class); // one serializer, several classes
// an instance can be registered too: ->register(Money::class, new MoneySerializer($currencies))

// 2. Attribute on a class you own
use AndreaColzani\PgArray\Attributes\PgArraySerializer;

#[PgArraySerializer(SkuSerializer::class)]
final class Sku { /* … */ }

// 3. Contract on a class you own
use AndreaColzani\PgArray\Contracts\PgArraySerializable;

final class CountryCode implements PgArraySerializable
{
    public static function pgArraySerializer(): string
    {
        return CountryCodeSerializer::class;
    }
}
```

The cast is unchanged: `'prices' => AsPgArray::of(Money::class)`.

Serializer rules:

- `serialize()` returns a scalar or `null`. To store structured values in `json[]` / `jsonb[]`, implement the marker `AndreaColzani\PgArray\Contracts\PgArrayJsonSerializer` instead (same methods; `deserialize()` then receives the decoded JSON).
- `deserialize()` receives the element text (or decoded JSON) and the **target class**, must return an instance of that class, and is never called with `null`.
- Lookup is by **exact class name**: subclasses and interfaces are not matched; the attribute and contract are not inherited.
- A serializer overrides `PgArrayValue`, `PgArrayJsonValue` and enum support, so it can change how existing value objects or third-party enums are stored.
- Serializer classes are resolved through the container (constructor injection works) and shared.
- Assignment normalizes raw values through `deserialize()` → `serialize()`; objects of other classes are rejected.
- A mapped class that does not implement `PgArrayValueSerializer` throws `UnsupportedElementException`.

---

## 5. Encrypted and hashed arrays

### Encryption (element by element)

```php
use AndreaColzani\PgArray\Casts\AsEncryptedArray;

'backup_codes' => AsEncryptedArray::class,       // strings, text[] column
'backup_codes' => AsEncryptedArray::collect(),

'pins' => AsPgArray::encrypted(PgArrayCast::Integer),
'birthdays' => AsPgArray::encrypted(PgArrayCast::Date, PgArrayContainer::Collection),
'statuses' => AsPgArray::encrypted(OrderStatus::class),
'addresses' => AsPgArray::encrypted(Address::class),
```

- **The column must be `text[]` (or `varchar[]`) whatever the element type**: ciphertexts are strings. `AsPgArray::encrypted(PgArrayCast::Integer)` on an `integer[]` column fails.
- Each element is converted by its element cast, then encrypted with its own IV; on read it is decrypted, then converted. Values are transparently decrypted when accessed.
- The array structure stays visible (length, dimensions, `NULL` elements are not encrypted). Laravel's `encrypted:array` / `AsEncryptedArrayObject` casts instead encrypt the whole array into one string (use them, with a `text` column, if the structure must be hidden).
- Uses `Model::currentEncrypter()`: `Model::encryptUsing()` and `APP_PREVIOUS_KEYS` rotation work. Invalid payloads throw Laravel's `DecryptException`.
- Encrypted columns cannot be meaningfully searched with array operators.

### Hashing (element by element, one-way)

```php
use AndreaColzani\PgArray\Casts\AsHashedArray;
use AndreaColzani\PgArray\Support\PgArrayHash;

'recovery_codes' => AsHashedArray::class, // text[] column

$user->recovery_codes = ['k3j4-9f8a', 'p0q1-7z2x']; // each hashed with Hash::make()
$user->save();
$user->recovery_codes; // ['$2y$12$…', '$2y$12$…'] — hashes, never plaintext
```

Verify with `PgArrayHash` (works with arrays, Collections and `null`; only checks top-level string elements):

```php
if (PgArrayHash::check($input, $user->recovery_codes)) {
    // valid
}

// Consume a one-time code
$key = PgArrayHash::find($input, $user->recovery_codes); // int|string|null

if ($key !== null) {
    $codes = $user->recovery_codes;
    unset($codes[$key]);
    $user->recovery_codes = array_values($codes); // remaining hashes are NOT re-hashed
    $user->save();
}
```

- Values that are already hashes (`Hash::isHashed()`) are stored unchanged, so hashes read from the database can be re-assigned (to remove or append codes). They must match the configured hashing algorithm, otherwise `InvalidValueException`.
- Accepts strings, integers, floats and `Stringable`; booleans and other objects throw. `NULL` elements stay `NULL`.
- Never compare hashes with `in_array()` / `===`: always use `PgArrayHash`.

---

## 6. Query builder

The macros are registered on `Illuminate\Database\Query\Builder`, so they work on query builders, Eloquent builders, models (static calls) and relations.

### Filters

| Operator | Meaning | Methods |
|---|---|---|
| `@>` | column contains **all** the values | `wherePgArrayContains`, `orWherePgArrayContains`, `wherePgArrayDoesntContain`, `orWherePgArrayDoesntContain` |
| `<@` | every column element is **among** the values | `wherePgArrayContainedBy`, `orWherePgArrayContainedBy`, `wherePgArrayNotContainedBy`, `orWherePgArrayNotContainedBy` |
| `&&` | column shares **at least one** value | `wherePgArrayOverlaps`, `orWherePgArrayOverlaps`, `wherePgArrayDoesntOverlap`, `orWherePgArrayDoesntOverlap` |

Signature: `(string|Expression $column, mixed $values, PgArrayType|PgArrayTypeDefinition|null $type = null)`.

```php
use AndreaColzani\PgArray\Enums\PgArrayType;

// Has the tag 'sale' (single values are wrapped in an array)
Product::wherePgArrayContains('tags', 'sale')->get();

// Has ALL of these tags
Product::wherePgArrayContains('tags', ['sale', 'new'])->get();

// Has ANY of these tags
Product::wherePgArrayOverlaps('tags', ['summer', 'winter'])->get();

// Only uses allowed tags
Product::wherePgArrayContainedBy('tags', $allowedTags)->get();

// Negations and OR
Product::query()
    ->wherePgArrayDoesntContain('tags', 'discontinued')
    ->where(fn ($query) => $query
        ->wherePgArrayOverlaps('tags', ['kids'])
        ->orWherePgArrayContains('channels', 'store'))
    ->get();

// Collections, enums, UUIDs and dates are accepted as values
Order::wherePgArrayOverlaps('statuses', [OrderStatus::Paid, OrderStatus::Shipped])->get();
Product::wherePgArrayContains('supplier_ids', $supplier->uuid)->get();
Product::wherePgArrayOverlaps('related_ids', collect([1, 2, 3]))->get();

// Relations and qualified columns
$category->products()->wherePgArrayContains('products.tags', 'sale')->get();

// Explicit cast of the bound literal (useful with expressions or ambiguous types)
Product::wherePgArrayOverlaps('related_ids', $ids, PgArrayType::BigInt)->get(); // "related_ids" && ?::bigint[]
```

Semantics:

- The values are serialized to **one** array-literal binding (e.g. `{sale,new}`); PostgreSQL infers its type from the column. Passing `$type` adds an explicit cast.
- Values can be an array, an `Arrayable` (Collection) or a single value. Elements: scalars, `null`, nested arrays, `BackedEnum` (backing value), `DateTimeInterface` (written like the date-time casts, with offset), `Stringable` (UUIDs, ULIDs, `Vector`, …). `PgArrayValue` objects such as `Point` are **not** accepted: pass their `toPgArrayValue()`. A `null` value throws `InvalidValueException`.
- Empty values follow PostgreSQL: `@> '{}'` is always true, `<@ '{}'` matches only empty arrays, `&& '{}'` is always false.
- Negated methods (`not (…)`) exclude rows where the column is `NULL` (like `whereJsonDoesntContain`). Add `->orWhereNull('tags')` if needed.
- Multidimensional values are compared element-wise regardless of dimensions.
- Comparisons are exact and case-sensitive (`'PHP'` ≠ `'php'`).
- Encrypted and hashed columns cannot be filtered meaningfully (ciphertexts/hashes differ for equal values).
- With a GIN index on the column, these operators can use the index.

Other patterns that don't need the macros:

```php
// Array length / emptiness
Product::whereRaw('cardinality(tags) = 0')->get();
Product::whereRaw('cardinality(tags) > ?', [3])->get();

// Unnest for aggregations
DB::table('products')->selectRaw('unnest(tags) as tag, count(*)')->groupBy('tag')->get();
```

### Concatenation: `pgArrayAppend()` / `pgArrayPrepend()`

Update rows in place with `||`, without loading them. Signature: `(string $column, mixed $values, PgArrayType|PgArrayTypeDefinition|null $type = null, array $extra = [])`. They return the number of affected rows, like `increment()`.

```php
Product::whereKey($id)->pgArrayAppend('tags', 'featured');           // tags = tags || '{featured}'
Product::whereKey($id)->pgArrayAppend('tags', ['summer', 'sale']);   // several values
Product::whereKey($id)->pgArrayPrepend('tags', 'pinned');            // tags = '{pinned}' || tags

// Extra columns in the same UPDATE (positional arguments)
Product::whereKey($id)->pgArrayAppend('tags', 'archived', null, ['archived_at' => now()]);

// Bulk
Product::wherePgArrayContains('tags', 'summer')->pgArrayAppend('tags', 'clearance');

// Plain query builder
DB::table('products')->where('id', $id)->pgArrayAppend('tags', 'featured');
```

- On Eloquent builders, `updated_at` is touched. Model events are **not** fired (it's a query-level update).
- Appending to a `NULL` column yields just the appended values.
- Duplicates are not removed (PostgreSQL arrays are not sets).
- An in-memory model is not updated: call `$product->refresh()` afterwards if you keep using it.
- The literal is inlined in the SQL, escaped by the connection's PDO quoting.

### PostGIS arrays in queries

PostGIS separates `geometry[]` / `geography[]` elements with `:` instead of `,`. The query builder does not know the column type, so with **more than one value** pass the type:

```php
Store::wherePgArrayOverlaps(
    'service_areas',
    ['SRID=4326;POINT(-0.1276 51.5072)', 'SRID=4326;POINT(2.3522 48.8566)'],
    PgArrayType::Geography,
)->get();

$points = array_map(fn (Point $point) => $point->toPgArrayValue(), $points);
Store::whereKey($id)->pgArrayAppend('locations', $points, PgArrayType::Geometry);
```

---

## 7. PostgreSQL special types

### `bytea[]`: `AsByteaArray`

```php
'thumbnails' => AsByteaArray::class,

$image->thumbnails = [file_get_contents($smallPath), file_get_contents($largePath)];
$image->thumbnails[0]; // raw binary string
```

Assigned strings are **always raw binary data** (never pre-encoded `\x…` literals). Stored in hex format; reads both hex and legacy `escape` output. Consider object storage for large files.

### `inet[]` / `macaddr[]`: `AsInetArray`, `AsMacAddrArray`

```php
'allowed_ips' => AsInetArray::class,
'devices' => AsMacAddrArray::class,

$account->allowed_ips = ['203.0.113.7', '2001:0DB8::1/64', '10.0.0.1/32'];
$account->allowed_ips; // ['203.0.113.7', '2001:db8::1/64', '10.0.0.1']

$account->devices = ['08-00-2B-01-02-03', '0800.2b01.0203', '08002b010203'];
$account->devices;     // ['08:00:2b:01:02:03', '08:00:2b:01:02:03', '08:00:2b:01:02:03']
```

Values are validated on assignment (`InvalidValueException` for invalid addresses) and normalized like PostgreSQL output: IPv6 compressed lowercase, full-length prefixes (`/32`, `/128`) omitted. `cidr` and `macaddr8` are not supported. PostgreSQL's network operators (`<<`, `>>=`) are not wrapped: use `whereRaw()` with `unnest()` if needed.

### `vector[]` (pgvector): `AsVectorArray` and `Types\Vector`

```php
use AndreaColzani\PgArray\Casts\AsVectorArray;
use AndreaColzani\PgArray\Types\Vector;

// Migration
$table->pgArray('chunk_embeddings', PgArrayType::Vector)->size(1536); // vector(1536)[]

// Model
'chunk_embeddings' => AsVectorArray::withDimensions(1536),            // validates every element
'chunk_embeddings' => AsVectorArray::class,                           // no validation
'chunk_embeddings' => AsVectorArray::withDimensions(1536, PgArrayContainer::Collection),

// Usage
$document->chunk_embeddings = [
    new Vector($embeddingOfChunk1), // list<float|int>
    new Vector($embeddingOfChunk2),
    '[0.1,0.2,0.3]',               // pgvector strings are accepted too
];

$vector = $document->chunk_embeddings[0];
$vector->toArray();    // list<float>
$vector->dimensions(); // 1536 (also count($vector))
(string) $vector;      // '[0.12,-0.5,…]'
json_encode($vector);  // plain JSON list
Vector::fromString('[1,2,3]');
```

- Elements must be `Vector` objects (or pgvector strings): a plain `list<float>` would be interpreted as an array dimension and fail.
- `withDimensions(n)` throws `InvalidValueException` when an element has a different dimension, on both assignment and read.
- pgvector stores single-precision floats: values read back may differ slightly.
- `Vector` is immutable; components must be finite numbers.
- Similarity search on a single `vector` column is a pgvector concern (e.g. `orderByRaw('embedding <=> ?', [(string) $query])`); this package handles **arrays** of vectors.

### `geometry[]` / `geography[]` (PostGIS): `Types\Point`, `AsGeometryArray`, `AsGeographyArray`

Recommended for points: the `Point` value object.

```php
use AndreaColzani\PgArray\Types\Point;

// Migration
$table->pgArray('stops', PgArrayType::Geography)->subtype('Point'); // geography(Point,4326)[]

// Model
'stops' => AsPgArray::of(Point::class),

// Usage
$route->stops = [
    new Point(latitude: 51.5072, longitude: -0.1276),  // London, SRID 4326 by default
    new Point(latitude: 48.8566, longitude: 2.3522),   // Paris
    new Point(latitude: 40.7128, longitude: -74.0060, srid: 4326),
];
$route->save();

$stop = $route->fresh()->stops[0];
$stop->latitude;          // 51.5072
$stop->longitude;         // -0.1276
$stop->srid;              // 4326
$stop->toPgArrayValue();  // 'SRID=4326;POINT(-0.1276 51.5072)'  (EWKT: longitude first!)
json_encode($stop);       // {"type":"Point","coordinates":[-0.1276,51.5072]} (GeoJSON)
```

- The constructor order is `(latitude, longitude, srid = 4326)`; WKT/EWKT/GeoJSON use **longitude first**. Use named arguments to avoid mistakes.
- `Point` writes EWKT and reads the hex EWKB returned by PostgreSQL, as well as WKT / EWKT strings. Only 2D points are supported.

For other geometries, `AsGeometryArray` / `AsGeographyArray` are passthrough casts: they read hex EWKB strings and write WKT / EWKT / hex EWKB strings (or `Point` objects) unchanged. No GIS parsing happens.

```php
'coverage' => AsGeographyArray::class,

$zone->coverage = ['SRID=4326;POLYGON((-0.2 51.4,-0.0 51.4,-0.0 51.6,-0.2 51.4))'];
$zone->coverage; // ['0103000020E6100000…'] hex EWKB
```

For richer geometry objects (e.g. from a GIS library), map their class to an **external serializer** returning EWKT. If a custom element type is stored in a PostGIS array, implement `AndreaColzani\PgArray\Contracts\PgArrayDelimited` (`public static function pgArrayDelimiter(): string { return ':'; }`) on the value object or on its serializer.

---

## 8. Validation, factories, API output and testing in applications

### Validation

The casts validate some types (enums, inet, macaddr, vectors, value objects), but validate user input with Laravel rules first:

```php
$request->validate([
    'tags' => ['required', 'array', 'max:10'],
    'tags.*' => ['string', 'distinct', 'max:30'],
    'statuses' => ['array'],
    'statuses.*' => [Rule::enum(OrderStatus::class)],
    'allowed_ips.*' => ['ip'],
]);

$product->tags = $request->validated('tags');
```

Only list arrays are meaningful: keys are discarded (`['a' => 'x']` is stored as `{x}`). Use `array_values()` if you filtered an array.

### Mass assignment

Array attributes are mass-assignable like any other attribute (add them to `$fillable`):

```php
Product::create(['name' => 'Sneaker', 'tags' => ['shoes', 'sale']]);
$product->update(['tags' => ['shoes']]);
```

### Factories

```php
public function definition(): array
{
    return [
        'tags' => fake()->words(3),
        'related_ids' => [],
        'statuses' => [OrderStatus::Pending],
        'stops' => [new Point(latitude: fake()->latitude(), longitude: fake()->longitude())],
    ];
}
```

### Serialization (`toArray()` / JSON / API resources)

`toArray()` returns the cast arrays **with their element objects untouched** (Carbon instances, enum cases, value objects). They are converted only when encoded to JSON (`toJson()`, JSON responses, API resources): Carbon becomes an ISO-8601 string, enums their backing value, `Vector` a list, `Point` GeoJSON, and other objects go through `JsonSerializable` or their public properties. Implement `JsonSerializable` on custom value objects to control API output, or map the elements explicitly in an API resource. Hashed and encrypted attributes should usually be listed in `$hidden`.

### Testing applications that use the package

- The migration helper and query macros require the **PostgreSQL** driver: tests that run migrations on SQLite will throw `UnsupportedDriverException`. Run the test suite against PostgreSQL (e.g. with `RefreshDatabase` and a `pgsql` test connection).
- The casts themselves are pure PHP and can be unit-tested without a database via `$model->setRawAttributes(['tags' => '{a,b}'])` and `$model->getAttributes()`.
- Database assertions compare the stored literal: `$this->assertDatabaseHas('products', ['tags' => '{shoes,sale}'])` (order matters). Prefer asserting on the model: `expect($product->fresh()->tags)->toBe(['shoes', 'sale'])`.

---

## 9. Exceptions

Every package exception implements `AndreaColzani\PgArray\Exceptions\PgArrayException` and extends an SPL exception:

| Exception | Extends | When |
|---|---|---|
| `UnsupportedElementException` | `InvalidArgumentException` | element type cannot be resolved (unknown class, pure enum, unsupported class, invalid serializer) |
| `InvalidDefinitionException` | `InvalidArgumentException` | invalid cast arguments, migration type/column modifiers, delimiters |
| `InvalidValueException` | `UnexpectedValueException` | a value cannot be cast, serialized or parsed (invalid enum value, IP, MAC, vector, point, malformed array literal, `null` query value, …) |
| `UnsupportedDriverException` | `RuntimeException` | migration helper or query macros used with a non-PostgreSQL connection |

```php
use AndreaColzani\PgArray\Exceptions\PgArrayException;

try {
    $order->statuses = $request->input('statuses');
} catch (PgArrayException $e) {
    throw ValidationException::withMessages(['statuses' => $e->getMessage()]);
}
```

Also possible: `JsonException` (invalid JSON element) and Laravel's `DecryptException` (invalid encrypted payload).

---

## 10. Public API vs internals

Use only the public API: `Casts\AsPgArray`, `Casts\As*Array`, `Casts\PgArrayCastable`, `Enums\PgArrayCast`, `Enums\PgArrayType`, `Enums\PgArrayContainer`, `Contracts\*`, `Attributes\PgArraySerializer`, `Concerns\InteractsWithPgArrayJson`, `Support\PgArraySerializerRegistry`, `Support\PgArrayHash`, `Types\Point`, `Types\Vector`, `Database\PgArrayTypeDefinition`, `Database\PgArrayColumnDefinition` modifiers, the `pgArray()` / `wherePgArray*()` / `pgArrayAppend()` / `pgArrayPrepend()` macros, and `Exceptions\*`.

Classes marked `@internal` (`Casts\PgArray`, everything in `Casts\Values`, `Support\PgArrayParser`, `Support\PgArrayLiteral`, `Database\PgArraySchema`, `Database\PgArrayQuery`, `Database\PgArrayConcatenation`, `Database\PgArrayDefault`) may change without notice: do not instantiate or extend them in application code. For custom element types, implement `PgArrayValue`, `PgArrayJsonValue` or an external serializer instead of writing a value caster.

---

## 11. Common mistakes checklist

- Using Laravel's `array` / `json` / `AsCollection` casts on a `text[]` column → use `AsStringArray` (or another array cast).
- Appending with `$model->tags[] = 'x'` on an array container → assign a new array, use a Collection container, or `pgArrayAppend()`.
- `AsPgArray::encrypted(...)` / `AsHashedArray` on a non-text column → the column must be `text[]`.
- `AsUlidArray` on a `uuid[]` column → ULIDs are 26-character strings: use `char(26)[]` / `text[]` (or store them as UUIDs with `AsUuidArray`).
- Treating `AsDecimalArray` values as floats → they are strings on purpose.
- Assigning a `list<float>` as a vector → wrap it in `new Vector([...])`.
- Swapping latitude/longitude in `Point` → use named arguments.
- Querying PostGIS arrays with several values without the `$type` argument → pass `PgArrayType::Geometry` / `Geography`.
- Passing `null` to a `wherePgArray*()` method → use `whereNull()` instead.
- Expecting `wherePgArrayDoesntContain()` to return rows with a `NULL` column → add `orWhereNull()`.
- Ragged multidimensional arrays (`[[1, 2], [3]]`) → PostgreSQL rejects them.
- Passing `PgArrayType` to `AsPgArray::of()` (or `PgArrayCast` to `pgArray()`) → casts use `PgArrayCast`, migrations/queries use `PgArrayType`.
- Running migrations with `pgArray()` on SQLite in tests → use a PostgreSQL test database.
- Forgetting `CREATE EXTENSION` before creating `vector[]` / `geometry[]` / `geography[]` columns.
