# Project Roadmap

## Overview

Roadmap for `andreacolzani/laravel-pgarray`, covering PostgreSQL type support, generic/object casting, advanced Laravel semantics, migration helpers, query builder support, integration testing, documentation, and the first stable release.

The implementation strategy is intentionally incremental:

> **Implementation → tests → PHPStan → Pint → commit → next milestone**

Avoid designing future APIs in excessive detail before reaching the corresponding implementation milestone. Keep clear extension points while keeping each change focused.

---

## Milestone 1 — Complete fundamental PostgreSQL types [DONE]

### 1.1 `PgArrayType`

All PostgreSQL primitive types are represented in the `PgArrayType` enum:

- [x] `Char`
- [x] `Varchar`
- [x] `Text`
- [x] `SmallInt`
- [x] `Integer`
- [x] `BigInt`
- [x] `Real`
- [x] `DoublePrecision`
- [x] `Decimal`
- [x] `Numeric`
- [x] `Float`
- [x] `Boolean`
- [x] `Date`
- [x] `Time`
- [x] `TimeTz`
- [x] `Timestamp`
- [x] `TimestampTz`
- [x] `Bytea`
- [x] `Uuid`
- [x] `Inet`
- [x] `MacAddr`
- [x] `Json`
- [x] `Jsonb`
- [x] `Geometry`
- [x] `Geography`
- [x] `Vector`

> **Design decision (per project guidance):** `Char`, `Varchar`, `Text`, `Time`, `TimeTz`, `Uuid` all map directly to the `StringCaster` value caster — no separate caster classes are created for these. `Json`/`Jsonb` do **not** get built-in value casters; users provide custom casters for JSON array elements. The distinction between `PgArrayType` (database-level) and `PgArrayCast` (Laravel-side cast semantics) is intentional and kept separate.

Do not introduce parameterized types yet (`varchar(50)`, `decimal(10,2)`, etc.). Those will use a separate type-definition abstraction (see Milestone 11).

### 1.2 Value casters

Implemented and tested value casters:

- [x] `BooleanCaster`
- [x] `IntegerCaster`
- [x] `StringCaster` (handles `Char`, `Varchar`, `Text`, `Time`, `TimeTz` mapping)
- [x] `DecimalCaster`
- [x] `FloatCaster`
- [x] `DoubleCaster`
- [x] `RealCaster`
- [x] `DateCaster`
- [x] `DateTimeCaster`
- [x] `ImmutableDateCaster`
- [x] `ImmutableDateTimeCaster`
- [x] `StringableCaster`
- [x] `UriCaster`
- [x] `UlidCaster`
- [x] `UuidCaster`

> **No `JsonCaster` / `JsonbCaster`:** Per the stated design decision, JSON and JSONB types do not receive built-in value casters. Users supply custom casters for JSON array elements (see Milestone 6).

For each implemented caster:

- [x] `get()`
- [x] `set()`
- [x] `null` handling
- [x] edge/realistic values
- [x] precision-sensitive behavior where applicable

### 1.3 Factory

- [x] `PgArrayValueCasterFactory` maps all `PgArrayCast` cases to concrete casters.
- [x] Factory `match` expression is exhaustive over all `PgArrayCast` enum cases (no `default` fallback needed).
- [x] `UlidCaster` and `UuidCaster` added to the factory.
- [x] Added `ramsey/uuid` (^4.7) and `symfony/uid` (^7.0) to `composer.json` as runtime dependencies.

### 1.4 Integration tests

- [x] Eloquent integration tests extended for all new casts.
- [x] `TestModel` updated with `uuid_array`, `uuid_collection`, `ulid_array`, `ulid_collection` cast definitions.

### Checkpoint

```bash
composer test
composer analyse
composer format
```

All quality gates green: Pest ✅ (207 tests), PHPStan ✅, Pint ✅.

Commit the milestone before proceeding.

---

## Milestone 2 — Generalize `AsPgArray::of()` [DONE]

Generalize the first argument to:

```php
PgArrayCast|string
```

Target API:

```php
AsPgArray::of(PgArrayCast::Integer);

AsPgArray::of(
    PgArrayCast::Integer,
    PgArrayContainer::Collection,
);

AsPgArray::of(MyEnum::class);

AsPgArray::of(Address::class);
```

### Tasks

- [x] Update `AsPgArray::of()`.
- [x] Preserve existing `PgArrayCast` behavior.
- [x] Preserve the optional container.
- [x] Preserve backward compatibility.
- [x] Update cast-definition parsing as necessary.
- [x] Add tests for built-in casts.
- [x] Add tests for class-string definitions.
- [x] Add invalid-definition tests.

### Checkpoint

Run the standard test/static-analysis/formatting suite and commit.

---

## Milestone 3 — Element caster resolver [DONE]

Introduce a resolution layer between `PgArray` and individual value casters.

Target architecture:

```text
PgArray
   ↓
PgArrayValueCasterResolver
   ↓
specific PgArrayValueCaster
```

### 3.1 `PgArrayElementDefinition`

Introduce a small internal value object representing the element definition.

Initial conceptual shape:

```php
final class PgArrayElementDefinition
{
    public function __construct(
        public readonly PgArrayCast|string $type,
    ) {}
}
```

Do not add serializer configuration until it is actually needed.

- [x] Built by `PgArray` for every element type and passed to the resolver.

### 3.2 `PgArrayValueCasterResolver`

- [x] Resolve built-in `PgArrayCast` values.
- [x] Resolve class-string element types (unknown classes, pure enums and other unsupported classes fail with `UnsupportedElementException`; `BackedEnum` resolves to `EnumCaster` since Milestone 4).
- [x] Produce explicit exceptions for unsupported types.

### 3.3 Refactor factory

- [x] Keep `PgArrayValueCasterFactory` focused on built-in casters.
- [x] `PgArray` accepts `PgArrayCast|string` and resolves its caster through `PgArrayValueCasterResolver`.

Target structure:

```text
Resolver
 ├── PgArrayCast
 │      ↓
 │  ValueCasterFactory
 │
 └── class-string
        ↓
    Enum/Object/etc.
```

### 3.4 Tests

- [x] Existing built-in casts still work.
- [x] `PgArrayCast` resolution works.
- [x] Class-string resolution works.
- [x] Unsupported classes fail explicitly.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 4 — Automatic `BackedEnum` support [DONE]

Support:

```php
enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
```

with:

```php
AsPgArray::of(Status::class)
```

Expected round-trip:

```text
{active,inactive}
        ↓
[Status::Active, Status::Inactive]
        ↓
{active,inactive}
```

### Tasks

- [x] Implement `EnumCaster` (resolved by `PgArrayValueCasterResolver` for `BackedEnum` class-strings).
- [x] Support string-backed enums.
- [x] Support int-backed enums (PostgreSQL integer text such as `"1"` is validated with `FILTER_VALIDATE_INT`).
- [x] Handle invalid backing values explicitly (`UnexpectedValueException` on both `get()` and `set()`; `set()` accepts enum cases or valid raw backing values, cases of a different enum are rejected).
- [x] Keep `UnitEnum` unsupported (`UnsupportedElementException::pureEnum()`).
- [x] Test hydration.
- [x] Test serialization.
- [x] Test multidimensional arrays.
- [x] Test Collection containers.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 5 — `PgArrayValue` contract [DONE]

Introduce the class-level contract for custom value objects.

Proposed contract:

```php
interface PgArrayValue
{
    public function toPgArrayValue(): mixed;

    public static function fromPgArrayValue(mixed $value): static;
}
```

`toPgArrayValue()` returns a **logical PHP value**, not an already-encoded PostgreSQL string.

Example:

```php
final class Address implements PgArrayValue
{
    public function toPgArrayValue(): mixed
    {
        return [
            'street' => $this->street,
            'number' => $this->number,
            'city' => $this->city,
            'zip_code' => $this->zipCode,
        ];
    }

    public static function fromPgArrayValue(mixed $value): static
    {
        return new static(
            street: $value['street'],
            number: $value['number'],
            city: $value['city'],
            zipCode: $value['zip_code'],
        );
    }
}
```

### Tasks

- [x] Create `Contracts/PgArrayValue.php`.
- [x] Define/document the contract.
- [x] Implement the generic object caster (`ObjectCaster`).
- [x] Resolve `PgArrayValue` implementations automatically.
- [x] Test object → logical value.
- [x] Test logical value → object.
- [x] Test full array round-trip.
- [x] Test Collection.
- [x] Test multidimensional arrays.

> **Design decisions:**
> - For plain `PgArrayValue` implementations, `toPgArrayValue()` may only return scalar values (`string|int|float|bool`) or `null`. Arrays/objects fail with an explicit `UnexpectedValueException` pointing to `PgArrayJsonValue`: without JSON encoding, `PgArrayParser::serialize()` would read them as an extra array dimension. Milestone 6 lifts this restriction through the `PgArrayJsonValue` marker contract, without changing `PgArrayValue`.
> - On `set()`, raw values that are not instances of the class are normalized through `fromPgArrayValue()` → `toPgArrayValue()` (as `EnumCaster` does with raw backing values), so the class can validate them. Objects of other classes are rejected.
> - `fromPgArrayValue()` receives the PostgreSQL text representation of the element and is never called with `null`.
> - A `BackedEnum` that implements `PgArrayValue` is resolved through the contract (`ObjectCaster`), which takes precedence over automatic enum support.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 6 — JSON / JSONB object serialization [DONE]

Connect custom objects to PostgreSQL `json[]` / `jsonb[]`.

Conceptual flow:

```text
Address
   ↓
toPgArrayValue()
   ↓
PHP logical value
   ↓
JSON encoding
   ↓
json[] / jsonb[]
```

Remember that PostgreSQL `json[]` / `jsonb[]` is an array whose individual elements are JSON values.

### Tasks

- [x] Implement the JSON element caster (`JsonObjectCaster`, shared by `json[]` and `jsonb[]`).
- [x] Define object → JSON behavior (`PgArrayJsonValue` marker contract).
- [x] Define JSON → object behavior.
- [x] Provide a default, overridable serialization (`Concerns\InteractsWithPgArrayJson`).
- [x] Preserve nested structures.
- [x] Test `Address[]`.
- [x] Test `Address[][]`.
- [x] Test `null` values.
- [x] Test JSON edge cases.
- [x] Test full Eloquent round-trip.

Usage:

```php
final class Address implements PgArrayJsonValue
{
    use InteractsWithPgArrayJson; // optional default implementation

    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

'addresses' => AsPgArray::of(Address::class), // json[] / jsonb[] column
```

> **Design decisions:**
> - JSON storage is declared at class level through the marker contract `PgArrayJsonValue extends PgArrayValue`, not detected from the value: when reading, an element such as `"123"` or `"true"` is ambiguous (plain text for `Email`/`Cents`, JSON for `Address`). The cast definition stays `AsPgArray::of(Address::class)`.
> - The resolver checks `PgArrayJsonValue` before `PgArrayValue`: JSON implementations resolve to `JsonObjectCaster`, the others keep using `ObjectCaster` (scalar logical values only).
> - `json[]` and `jsonb[]` are identical on the PHP side, so a single `JsonObjectCaster` serves both; no separate `JsonCaster` / `JsonbCaster` classes.
> - No raw JSON cast (e.g. `PgArrayCast::Json` returning associative arrays): it would add little over Laravel's native `json`/`array` casts, and PHP list arrays would be ambiguous with PostgreSQL array dimensions. Scope is limited to `PgArrayJsonValue` objects.
> - Encoding uses `JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION`; decoding uses associative arrays and `JSON_THROW_ON_ERROR`. Invalid JSON fails with `JsonException`.
> - SQL `NULL` elements, JSON `null` elements and `null` logical values are all treated as `null`; `fromPgArrayValue()` is never called with `null`.
> - On `set()`, raw JSON strings are normalized through `fromPgArrayValue()` → `toPgArrayValue()` (consistent with `ObjectCaster`); other raw scalars and objects of other classes are rejected. Raw PHP arrays are not accepted as elements, because `PgArray` treats them as array dimensions.
> - `InteractsWithPgArrayJson` is optional. By default `toPgArrayValue()` returns the object's properties by name (nested `PgArrayValue` objects through their own contract) and `fromPgArrayValue()` passes matching keys to the constructor as named arguments, restoring nested `PgArrayValue` objects and backed enums from the parameter type. Unknown keys are ignored; missing required arguments fail. Classes override either method to change or limit the stored structure.
> - `PgArrayParser::serialize()` now preserves the case of string elements equal to `NULL` (e.g. `null` was stored as `"NULL"`).
> - `tests/Fixtures` is included in the PHPStan paths, so the trait is analysed through the classes that use it.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 7 — External serializers [DONE]

Add optional support for classes that cannot be modified, require complex serialization, or share serialization rules.

Contract:

```php
interface PgArrayValueSerializer
{
    public function serialize(object $value): mixed;

    /** @param class-string $class */
    public function deserialize(mixed $value, string $class): object;
}
```

### Tasks

- [x] Define serializer contract (`Contracts\PgArrayValueSerializer`, JSON marker `Contracts\PgArrayJsonSerializer`).
- [x] Decide configuration/registry mechanism (`pgarray.serializers` config + `Support\PgArraySerializerRegistry`, `#[PgArraySerializer]` attribute, `Contracts\PgArraySerializable`).
- [x] Support class → serializer mapping.
- [x] Support serializers shared by multiple classes.
- [x] Integrate external serializers into the resolver (`SerializerCaster`, `JsonSerializerCaster`).
- [x] Define precedence relative to `PgArrayValue`.
- [x] Test unmodifiable classes.
- [x] Test shared serializers.
- [x] Test missing serializer errors.
- [x] Test Collection and multidimensional arrays.

Usage:

```php
// config/pgarray.php — classes that cannot be modified
serializers => [
    Money::class => MoneySerializer::class,
],

// documented default for your own classes
#[PgArraySerializer(SkuSerializer::class)]
final class Sku { /* ... */ }

// alternative through a method
final class CountryCode implements PgArraySerializable
{
    public static function pgArraySerializer(): string
    {
        return StringValueSerializer::class;
    }
}

prices => AsPgArray::of(Money::class), // cast definition is unchanged
```

> **Design decisions:**
> - Serializers are mapped to element classes in three ways, in order of precedence: the `pgarray.serializers` configuration (loaded into the `PgArraySerializerRegistry` singleton, which also exposes `register()` for service providers), the `#[PgArraySerializer]` attribute (documented default for classes you own) and the `PgArraySerializable` contract (method-based alternative). The attribute wins over the method when a class uses both. The element class does not need to implement any other contract.
> - Lookup is by exact class name: the attribute and the contract are not inherited by subclasses, and the configuration does not match parent classes or interfaces.
> - A serializer takes precedence over `PgArrayJsonValue` / `PgArrayValue` and `BackedEnum` support: explicit configuration wins over class-level defaults, so existing value objects and third-party enums can be overridden without modifying them.
> - The storage format is declared by the serializer, consistently with Milestone 6: `PgArrayValueSerializer` returns scalar logical values or `null` (`SerializerCaster`), the `PgArrayJsonSerializer` marker stores logical values as JSON in `json[]` / `jsonb[]` (`JsonSerializerCaster`, same encoding flags and `null` semantics as `JsonObjectCaster`).
> - `deserialize()` receives the target class, so one serializer can be shared by multiple classes; it must return an instance of that class, otherwise an `UnexpectedValueException` is thrown. It is never called with `null`.
> - `set()` normalizes raw values through `deserialize()` → `serialize()` and rejects objects of other classes, as `ObjectCaster` / `JsonObjectCaster` do.
> - Serializer classes are instantiated through the container (dependency injection) once per registry and shared by every class they are mapped to; instances can also be registered directly.
> - A mapped class that does not implement `PgArrayValueSerializer` fails with `UnsupportedElementException::invalidSerializer()`; classes with no support at all fail with `UnsupportedElementException::unsupportedClass()`, whose message now points to the available options.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 8 — Element-level `Encrypted` [DONE]

Implement encryption at the **element level**, not at the array level.

```text
plaintext[]
    ↓
encrypt() each element
    ↓
ciphertext[]
    ↓
PostgreSQL array
```

### Tasks

- [x] Implement `EncryptedCaster` (decorator around the resolved element caster).
- [x] Integrate it into the resolver (`PgArrayElementDefinition::$encrypted`).
- [x] Encrypt each element on `set()`.
- [x] Decrypt each element on `get()`.
- [x] Handle `null`.
- [x] Handle Collection.
- [x] Test full round-trip.
- [x] Test that encryption remains element-level.
- [x] Do not duplicate Laravel's existing `encrypted:array` semantics.

Usage:

```php
// text[] columns — every element is a separate ciphertext
'secrets' => AsEncryptedArray::class,
'secret_collection' => AsEncryptedArray::collect(),

'pins' => AsPgArray::encrypted(PgArrayCast::Integer),
'birthdays' => AsPgArray::encrypted(PgArrayCast::Date, PgArrayContainer::Collection),
'statuses' => AsPgArray::encrypted(Status::class),
```

> **Design decisions:**
> - Encryption is an element modifier, not a `PgArrayCast` case: `EncryptedCaster` decorates the caster resolved for any element type (built-in casts, backed enums, `PgArrayValue` / `PgArrayJsonValue` objects, external serializers). `set()` runs the element caster first and encrypts its logical value; `get()` decrypts and passes the plaintext to the element caster.
> - Declared through `AsPgArray::encrypted($type, $container)`, which produces `AsPgArray:<type>,<container>,encrypted`; `AsEncryptedArray` (+ `collect()`) covers the common string case. Unknown modifiers in the cast definition fail with `InvalidArgumentException`.
> - Each element is encrypted separately, so array structure (dimensions, length, `NULL` elements) stays visible and only element values are hidden. This is intentionally different from Laravel's `encrypted:array`, which encrypts the whole array as one JSON payload into a single column value; users who want that keep using the native cast.
> - The logical value is encrypted in its PostgreSQL text representation (`true` / `false` as `t` / `f`, like `PgArrayParser::serialize()`), without PHP serialization, so the same element caster parses it after decryption. Non-scalar logical values fail with `UnexpectedValueException`.
> - Ciphertexts are strings: encrypted arrays require `text[]` (or `varchar[]`) columns, including for JSON objects, integers, dates and enums.
> - Encryption uses `Model::currentEncrypter()`, so `Model::encryptUsing()` and previous keys (`APP_PREVIOUS_KEYS`) behave as with Laravel's `encrypted` casts. The encrypter is resolved on every call, not captured when the cast is built.
> - `NULL` elements (and `null` logical values returned by the element caster) are stored as SQL `NULL`, not encrypted. Every other element gets a fresh IV, so equal plaintexts produce different ciphertexts. Invalid payloads fail with Laravel's `DecryptException`.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 9 — Element-level `Hashed` [DONE]

Hash elements individually:

```text
plaintext[]
    ↓
Hash::make() each element
    ↓
hash[]
```

Hashing is one-way, so retrieval returns hashes rather than plaintext.

### Tasks

- [x] Implement `HashedCaster`.
- [x] Integrate it into the resolver (`PgArrayCast::Hashed` → `PgArrayValueCasterFactory`).
- [x] Hash each element on `set()`.
- [x] Preserve hashes on `get()`.
- [x] Design the verification API (`Support\PgArrayHash::check()` / `find()`).
- [x] Consider whether a specialized `HashedArray` wrapper is justified (not justified, see below).
- [x] Test positive verification.
- [x] Test negative verification.
- [x] Test multiple hashes.
- [x] Test null.
- [x] Test Collection.

Usage:

```php
'recovery_codes' => AsHashedArray::class, // text[] column
'recovery_codes' => AsHashedArray::collect(),

$user->recovery_codes = ['alpha', 'beta'];              // stored as hashes

PgArrayHash::check($code, $user->recovery_codes);       // bool

$key = PgArrayHash::find($code, $user->recovery_codes); // int|string|null
if ($key !== null) {
    $codes = $user->recovery_codes;
    unset($codes[$key]);
    $user->recovery_codes = array_values($codes);
}
```

> **Design decisions:**
> - Hashing is declared through the dedicated `AsHashedArray` castable (+ `collect()`), backed by a new `PgArrayCast::Hashed` case resolved by `PgArrayValueCasterFactory` (so `AsPgArray::of(PgArrayCast::Hashed)` also works). There is no `AsPgArray::hashed()` element modifier: retrieved elements are always hash strings, so the element type is irrelevant when reading.
> - `HashedCaster` mirrors Laravel's `hashed` cast element by element: values already hashed (`Hash::isHashed()`) are stored unchanged, so hashes read from the database can be assigned again (e.g. appending a new code) without being hashed twice; they must pass `Hash::verifyConfiguration()`, otherwise a `RuntimeException` is thrown. Other values are hashed with `Hash::make()` using the configured driver.
> - Accepted input: strings, integers, floats and `Stringable`, hashed in their string form. Booleans and other objects fail with `UnexpectedValueException`. `NULL` elements stay SQL `NULL`.
> - Verification uses a static helper, `Support\PgArrayHash`: `check($value, $hashes)` and `find($value, $hashes)` returning the key of the first matching hash (to remove consumed values such as recovery codes). It accepts arrays, Collections and `null`, and checks only top-level string elements (`NULL` elements and nested arrays are skipped).
> - No `HashedArray` wrapper: it would introduce a third container next to `array` / `Collection` for two methods; the helper works with both existing containers.
> - Test suite: `hashing.bcrypt.rounds` is lowered to 4 in `TestCase` to keep hashing tests fast.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 10 — Special PostgreSQL types [DONE]

New `PgArrayCast` cases: `Bytea`, `Inet`, `MacAddr`, `Vector`, `Geometry`, `Geography`, each with a dedicated castable (`AsByteaArray`, `AsInetArray`, `AsMacAddrArray`, `AsVectorArray`, `AsGeometryArray`, `AsGeographyArray`). Value objects live in the new `Types` namespace (`Types\Vector`, `Types\Point`).

### `bytea`

Representation: raw binary `string`.

- [x] `ByteaCaster`
- [x] Binary-safe get/set behavior
- [x] Tests with binary data

### `inet`

Representation: normalized `string`.

- [x] `InetCaster`
- [x] Validation/normalization as appropriate
- [x] Tests for IPv4 and IPv6

### `macaddr`

Representation: normalized `string` (`08:00:2b:01:02:03`).

- [x] `MacAddrCaster`
- [x] Validation/normalization as appropriate
- [x] Tests

### `vector`

Representation: `Types\Vector` (immutable, wraps `list<float>`).

- [x] Define representation.
- [x] Implement `VectorCaster`.
- [x] Handle pgvector format.
- [x] Consider dimension validation (`AsVectorArray::withDimensions()`).
- [x] Test realistic vectors.

### `geometry` / `geography`

Do not turn the package into a GIS library.

- [x] Decide on default representation: string passthrough, plus an optional `Types\Point` value object.
- [x] Define serialization/deserialization boundaries.
- [x] Consider external serializers for richer GIS objects.
- [x] Implement the minimum useful representation.
- [ ] Add PostgreSQL/PostGIS integration tests where applicable (moved to Milestone 14: the suite has no real PostgreSQL yet).

Usage:

```php
'files'     => AsByteaArray::class,                  // bytea[]
'ips'       => AsInetArray::class,                   // inet[]
'macs'      => AsMacAddrArray::class,                // macaddr[]
'chunks'    => AsVectorArray::withDimensions(1536),  // vector(1536)[]
'areas'     => AsGeographyArray::class,              // geography[] (strings)
'locations' => AsPgArray::of(Point::class),          // geometry[] / geography[] points

$model->chunks = [new Vector([0.12, -0.5, ...])];
$model->locations = [new Point(latitude: 45.4642, longitude: 9.19)]; // SRID 4326
```

> **Design decisions:**
> - `bytea`: assigned strings are always raw binary data and are stored in the hex format (`\x0a0b…`). On read, the hex format is decoded, and so is the legacy `escape` format (`bytea_output = 'escape'`).
> - `inet`: values are validated (`address[/prefix]`, IPv4 or IPv6) and normalized to the PostgreSQL output form: IPv6 compressed and lowercase, full-length prefixes (`/32`, `/128`) omitted. `cidr` and `macaddr8` are out of scope.
> - `macaddr`: every PostgreSQL input format is accepted and normalized to `08:00:2b:01:02:03`.
> - `vector`: not a duplicate of `float8[][]`, because a `vector[]` column only accepts pgvector literals (`{"[1,2,3]"}`). Elements are `Types\Vector` objects rather than `list<float>`, because PHP arrays are nested dimensions of the PostgreSQL array: a list would be split into separate elements when assigned. `Vector` is `Countable`, `Stringable` (pgvector format) and `JsonSerializable` (plain list). Dimension validation is optional (`AsVectorArray::withDimensions(n)`) and is checked on both set and get. pgvector stores single-precision floats.
> - Configured casters: `PgArrayElementDefinition` (and `PgArray`) also accept a ready-made `PgArrayValueCaster`, which the resolver uses as is (encryption still applies). `AsVectorArray::withDimensions()` uses this to pass a `VectorCaster` with its dimensions. It is also the extension point for parameterized types (Milestone 11).
> - `geometry` / `geography`: `GeometryCaster` is a passthrough. It reads the hex EWKB returned by PostgreSQL as a string, and writes WKT / EWKT / EWKB strings unchanged, or a `Point` as EWKT. There is no GIS parsing and no GIS dependency.
> - `Types\Point(latitude, longitude, srid = 4326)` is the only spatial object and the **recommended way to work with points**. It implements `PgArrayValue`, so `AsPgArray::of(Point::class)` works through `ObjectCaster`. It writes EWKT (`SRID=4326;POINT(lng lat)`) and reads 2D EWKB (either byte order, SRID optional) or WKT/EWKT. `JsonSerializable` produces GeoJSON. The argument order matches `tarfin-labs/laravel-spatial`.
> - Richer geometries, or objects of GIS libraries, are mapped through external serializers (Milestone 7). For example, a `PgArrayValueSerializer` that turns a third-party `Point` into EWKT and back, registered in `pgarray.serializers`.

### Checkpoint

Run the standard suite and commit each logical group.

---

## Milestone 11 — Parameterized PostgreSQL types [DONE]

Enums alone are insufficient for:

```sql
varchar(50)[]
decimal(10,2)[]
timestamp(6)[]
vector(1536)[]
```

Introduce a separate type-definition/value-object abstraction rather than encoding every combination into `PgArrayType`.

Possible conceptual shape:

```php
new PgArrayTypeDefinition(
    type: PgArrayType::Varchar,
    parameters: [50],
);
```

### Tasks

- [x] Design `PgArrayTypeDefinition`.
- [x] Validate parameters.
- [x] Support type rendering.
- [x] Support `varchar(n)`.
- [x] Support `char(n)`.
- [x] Support `decimal(p,s)` / `numeric(p,s)`.
- [x] Support temporal precision.
- [x] Support `vector(n)`.
- [x] Support PostGIS type modifiers (`geometry(Point,4326)` / `geography(...)`).
- [x] Add tests.
- [x] Integrate with migration helpers (Milestone 12).
- [x] Integrate with any other API that needs PostgreSQL type definitions (none yet: Eloquent casts are intentionally unaffected).

Usage:

```php
use AndreaColzani\PgArray\Database\PgArrayTypeDefinition;

PgArrayTypeDefinition::varchar(50)->toArraySql();              // varchar(50)[]
PgArrayTypeDefinition::decimal(10, 2)->toSql();                // decimal(10,2)
PgArrayTypeDefinition::timestampTz(6)->toArraySql();           // timestamptz(6)[]
PgArrayTypeDefinition::vector(1536)->toArraySql();             // vector(1536)[]
PgArrayTypeDefinition::geography('Point', 4326)->toArraySql(); // geography(Point,4326)[]
PgArrayTypeDefinition::of(PgArrayType::Integer)->toArraySql(2); // integer[][]
new PgArrayTypeDefinition(PgArrayType::Timestamp, [6]);        // timestamp(6)
```

> **Design decisions:**
> - `Database\PgArrayTypeDefinition` is a database-level value object: a `PgArrayType` plus a list of type modifiers. It has a generic constructor `(PgArrayType $type, array $parameters = [])` and named constructors (`of()`, `char()`, `varchar()`, `decimal()`, `numeric()`, `time()`, `timeTz()`, `timestamp()`, `timestampTz()`, `vector()`, `geometry()`, `geography()`). All parameters are optional, as in PostgreSQL (`varchar`, `numeric` and `vector` without modifiers are valid).
> - Rendering: `toSql()` (also `__toString()`) returns the element type. `toArraySql(int $dimensions = 1)` appends `[]` once per dimension. PostgreSQL does not enforce the declared number of dimensions.
> - Validation (`InvalidArgumentException` naming the type), with limits as class constants:
>   - `char` / `varchar`: length between 1 and 10485760.
>   - `decimal` / `numeric`: precision between 1 and 1000, scale between 0 and precision. The portable rule is used: the negative scales and scales greater than precision that PostgreSQL 15+ allows are rejected. A scale without a precision is rejected.
>   - `time` / `timetz` / `timestamp` / `timestamptz`: precision between 0 and 6.
>   - `vector`: dimensions between 1 and 16000 (pgvector limit).
>   - `geometry` / `geography`: `(subtype[, srid])`. The subtype is one of the PostGIS geometry types with an optional `Z` / `M` / `ZM` suffix, and its casing is normalized (`pointz` → `PointZ`). The SRID must be ≥ 0. With the named constructors, a SRID without a subtype uses `Geometry`, because PostGIS requires a subtype.
>   - Every other type rejects parameters.
> - Integer parameters must be real integers (no numeric strings), to catch configuration mistakes early.
> - Scope: Eloquent casts are unchanged. Lengths, scales and precisions are enforced by PostgreSQL, and vector dimensions can already be validated with `AsVectorArray::withDimensions()`. There is no parsing from strings (`'varchar(50)[]'` → definition): it will be added only when a feature needs it, e.g. schema introspection.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 12 — Migration helper [DONE]

Provide a minimal migration API based around `pgArray()`, built on `Database\PgArrayTypeDefinition` (Milestone 11), with Laravel-style chained modifiers.

Support:

```text
varchar[]
text[]
integer[]
uuid[]
json[]
jsonb[]
varchar(50)[]
decimal(10,2)[]
timestamp(6)[]
vector(1536)[]
```

### Tasks

- [x] Design final `pgArray()` API (built on `Database\PgArrayTypeDefinition`, see Milestone 11).
- [x] Implement migration helper.
- [x] Support simple types.
- [x] Support parameterized types.
- [x] Support nullable.
- [x] Support defaults where appropriate.
- [x] Support alterations if feasible.
- [x] Support dropping arrays.
- [x] Test generated SQL.
- [x] Add PostgreSQL integration tests.

Usage:

```php
use AndreaColzani\PgArray\Database\PgArrayTypeDefinition;
use AndreaColzani\PgArray\Enums\PgArrayType;

Schema::create('posts', function (Blueprint $table) {
    $table->pgArray('tags', PgArrayType::Text);                                      // text[] not null
    $table->pgArray('codes', PgArrayType::Varchar)->length(50)->nullable();          // varchar(50)[] null
    $table->pgArray('skus', PgArrayTypeDefinition::varchar(20));                     // varchar(20)[]
    $table->pgArray('prices', PgArrayType::Decimal)->precision(10, 2);               // decimal(10,2)[]
    $table->pgArray('seen_at', PgArrayType::TimestampTz)->precision(6);              // timestamptz(6)[]
    $table->pgArray('embeddings', PgArrayType::Vector)->size(1536);                  // vector(1536)[]
    $table->pgArray('places', PgArrayType::Geography)->subtype('Point');             // geography(Point,4326)[]
    $table->pgArray('areas', PgArrayType::Geometry)->subtype('Polygon')->srid(3857); // geometry(Polygon,3857)[]
    $table->pgArray('matrix', PgArrayType::Integer)->dimensions(2)->default([[1, 2], [3, 4]]);
    // integer[][] default '{{1,2},{3,4}}'::integer[][]
    $table->pgArray('labels', PgArrayType::Text)->withoutNullElements()->default([]);
    // text[] check (array_position("labels", NULL) is null) not null default '{}'::text[]
});

Schema::table('posts', function (Blueprint $table) {
    $table->pgArray('prices', PgArrayType::Decimal)->precision(12, 2)->change();
    $table->pgArray('codes', PgArrayType::Integer)->using('codes::integer[]')->change();
    $table->dropColumn('tags');
});
```

> **Design decisions:**
> - `$table->pgArray(string $column, PgArrayType|PgArrayTypeDefinition $type)` is a `Blueprint` macro returning a `Database\PgArrayColumnDefinition` (a `ColumnDefinition`), so every native modifier (`nullable()`, `default()`, `comment()`, `after()`, `change()`, ...) keeps working. It is registered by `Database\PgArraySchema::register()` in the service provider, together with a `typePgArray` grammar macro (Laravel compiles column types through `type{Type}()`); other drivers throw a `RuntimeException`.
> - Type modifiers are chained, following Laravel naming: `length()` (char / varchar), `precision($precision, $scale = null)` (decimal / numeric, and fractional seconds of time / timetz / timestamp / timestamptz), `size()` (pgvector dimensions), `subtype()` / `srid()` (geometry / geography), `dimensions()` (array dimensions, `integer[][]`). Each modifier rebuilds the `PgArrayTypeDefinition`, so values are validated eagerly; a modifier not supported by the type throws an `InvalidArgumentException`. A `PgArrayTypeDefinition` can be passed instead, and chained modifiers override it.
> - Spatial defaults follow Laravel's `geometry()` / `geography()` columns: no modifiers → `geometry[]` / `geography[]`; a geography subtype without SRID uses 4326; a SRID without subtype uses the generic `Geometry` subtype, as PostGIS requires.
> - `nullable()` is the native column-level modifier (the column may be NULL). PostgreSQL cannot forbid NULL **elements** in the type itself, so `withoutNullElements()` adds a `check (array_position(col, NULL) is null)` column constraint. It is only supported by one-dimensional arrays (`array_position` does not support multidimensional arrays) and when creating or adding the column (a `RuntimeException` is thrown with `change()`: add the constraint separately).
> - `default()` accepts PHP arrays and `Arrayable` values (Collections), rendered by `Database\PgArrayDefault` as an array literal cast to the column type (`'{a,b}'::text[]`) with `Support\PgArrayParser::serialize()`. The cast is rendered when the migration is compiled, so modifiers can be chained before or after `default()`. Elements can be scalars, `null`, nested arrays, `BackedEnum` and `Stringable`. Strings and `Expression` defaults keep Laravel's behaviour.
> - Alterations use Laravel's `change()`: as for every column, the full definition must be restated. Casts PostgreSQL cannot apply implicitly need `->using(...)` (string or `Expression`); no `USING` clause is generated automatically. Laravel only compiles its native `using()` modifier since 13.23, so `PgArrayColumnDefinition` overrides it and `PgArraySchema` appends the clause to the altered type itself: it works on every supported Laravel version, and is ignored when the column is not being changed. Since Laravel adds the `COLLATE` clause after the type, combining `using()` with `collation()` in a `change()` throws a `RuntimeException` (change the collation separately).
> - Dropping uses the native `dropColumn()`: no dedicated helper.
> - PostgreSQL does not store the declared number of dimensions: `integer[][]` is introspected as `integer[]`.
> - Integration tests (`tests/Integration`, group `pgsql`) use the `PGARRAY_DB_*` connection and are skipped when PostgreSQL (or an extension) is not available, unless `PGARRAY_REQUIRE_DB=true`. The `integration` job of `run-tests.yml` runs them on PostgreSQL 15 to 18 (`postgis/postgis` images, see Milestone 15) with pgvector installed and `PGARRAY_REQUIRE_DB=true`, so CI never skips them.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 13 — Query Builder [DONE]

Support PostgreSQL array operators:

```text
@>  contains
<@  contained by
&&  overlap
||  concatenation
```

### Tasks

- [x] Design public API.
- [x] Implement contains.
- [x] Implement contained by.
- [x] Implement overlap.
- [x] Implement concatenation if appropriate.
- [x] Support arrays.
- [x] Support Collections.
- [x] Handle empty values.
- [x] Handle strings/numbers/UUIDs.
- [x] Handle multidimensional values where applicable.
- [x] Test generated SQL.
- [x] Test bindings.
- [x] Add real PostgreSQL integration tests.

Usage:

```php
use AndreaColzani\PgArray\Enums\PgArrayType;

Post::query()
    ->wherePgArrayContains('tags', ['php', 'laravel'])          // "tags" @> ?
    ->wherePgArrayContainedBy('tags', $allowed)                 // "tags" <@ ?
    ->orWherePgArrayOverlaps('tags', collect(['vue', 'php']))   // or "tags" && ?
    ->wherePgArrayDoesntContain('tags', 'legacy')               // not ("tags" @> ?)
    ->wherePgArrayOverlaps('ids', $ids, PgArrayType::BigInt)    // "ids" && ?::bigint[]
    ->get();

Post::whereKey($id)->pgArrayAppend('tags', ['new']);          // set "tags" = "tags" || '{new}'
Post::whereKey($id)->pgArrayPrepend('tags', 'first');         // set "tags" = '{first}' || "tags"
```

> **Design decisions:**
> - Filters are `Query\Builder` macros registered by `Database\PgArrayQuery::register()` in the service provider, so they also work on Eloquent builders and relations. Each operator has the full Laravel set of variants, named like `whereJsonContains` / `whereJsonDoesntContain`:
>   - `@>`: `wherePgArrayContains`, `orWherePgArrayContains`, `wherePgArrayDoesntContain`, `orWherePgArrayDoesntContain`
>   - `<@`: `wherePgArrayContainedBy`, `orWherePgArrayContainedBy`, `wherePgArrayNotContainedBy`, `orWherePgArrayNotContainedBy`
>   - `&&`: `wherePgArrayOverlaps`, `orWherePgArrayOverlaps`, `wherePgArrayDoesntOverlap`, `orWherePgArrayDoesntOverlap`
> - Signature: `(string|Expression $column, mixed $values, PgArrayType|PgArrayTypeDefinition|null $type = null)`. They add a `PgArray` where type, compiled by a `wherePgArray` grammar macro. Columns are wrapped by the grammar, so `table.column` and `Expression` columns work. Other drivers throw a `RuntimeException`.
> - `$values` can be an array, an `Arrayable` (Collection) or a single value, which is wrapped into a one-element array. Elements can be scalars, `null`, nested arrays (multidimensional), `BackedEnum`, and `Stringable` (Ramsey / Symfony UUIDs, Carbon). A `null` value throws an `InvalidArgumentException`. `Support\PgArrayLiteral` turns the value into an array literal (e.g. `{php,laravel}`). `PgArrayDefault` uses it too.
> - The literal is sent as a single binding. PostgreSQL resolves an untyped parameter to the type of the other operand, so no cast is needed. Passing `$type` adds an explicit cast (`?::bigint[]`), for example with expressions.
> - Empty values keep PostgreSQL semantics:
>   - `@> '{}'` is always true.
>   - `<@ '{}'` matches only empty arrays.
>   - `&& '{}'` is always false.
>   - Negated forms (`not (...)`) exclude rows where the column is `NULL`, like `whereJsonDoesntContain`.
> - Multidimensional values follow PostgreSQL semantics: `@>` / `<@` / `&&` compare the elements regardless of dimensions.
> - Concatenation is an update, not a filter: `pgArrayAppend()` / `pgArrayPrepend()` `(string $column, mixed $values, $type = null, array $extra = [])`. Like `increment()`, they return the number of affected rows. They are registered on both `Query\Builder` and `Eloquent\Builder`, and the Eloquent version goes through `Eloquent\Builder::update()`, so `updated_at` is touched. Appending to a `NULL` column yields the appended values.
> - `update()` does not support bindings inside expressions. So `Database\PgArrayConcatenation` inlines the literal, escaped by the connection (`Grammar::escape()`, i.e. PDO quoting). The untyped literal takes the column's array type.
> - Integration tests (`tests/Integration/PgArrayQueryTest.php`) cover every operator on `text[]`, `integer[]`, `uuid[]` and `integer[][]`, plus empty values, `NULL` columns, special characters, append / prepend and Eloquent timestamps.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 14 — Real PostgreSQL integration suite

Introduce/expand tests against real PostgreSQL.

### Storage

- [ ] Insert
- [ ] Select
- [ ] Update
- [ ] Null arrays
- [ ] Empty arrays

### Types

- [ ] Integer
- [ ] BigInt
- [ ] SmallInt
- [ ] Decimal
- [ ] Numeric
- [ ] Float
- [ ] Double
- [ ] Real
- [ ] Boolean
- [ ] Char
- [ ] Varchar
- [ ] Text
- [ ] UUID
- [ ] Date
- [ ] Time
- [ ] TimeTz
- [ ] Timestamp
- [ ] TimestampTz
- [ ] JSON
- [ ] JSONB

### Structure

- [ ] Multidimensional arrays
- [ ] Quoted values
- [ ] Null elements
- [ ] Empty strings
- [ ] Escaped/special characters

### Advanced

- [ ] Backed enums
- [ ] Custom objects
- [ ] External serializers
- [ ] Encrypted values
- [ ] Hashed values
- [ ] Special PostgreSQL types (`bytea`, `inet`, `macaddr`, pgvector `vector`, PostGIS `geometry` / `geography` with `Point` EWKB round trip)

### Query builder

- [ ] Contains
- [ ] Contained by
- [ ] Overlap
- [ ] Concatenation

> **Fixes found by the integration suite:**
> - **Date-times keep their offset (differs from Laravel).** `DateTimeCaster` / `ImmutableDateTimeCaster` write `Y-m-d H:i:s.uP` (e.g. `2026-08-20 14:30:00.000000+02:00`), while Laravel's `datetime` cast writes `Y-m-d H:i:s` without an offset. Without the offset, PostgreSQL reads a `timestamptz` value in the **session** time zone: with an application in UTC and a server in Europe/Berlin, `14:30 UTC` was stored as `14:30+02` and read back as `12:30 UTC`, and any Carbon instance not in the session time zone was shifted. With the offset, `timestamptz[]` stores the exact instant whatever the session time zone; `timestamp[]` (without time zone) ignores the offset, so it keeps the wall-clock time as before. Values read with an offset (`timestamptz`) are converted to the default PHP time zone (`date_default_timezone_get()`, i.e. `app.timezone`), like Laravel's dates. Query builder operators format `DateTimeInterface` elements the same way. To be documented in the README (Milestone 17).

---

## Milestone 15 — Compatibility matrix

### PHP

- [ ] PHP 8.3
- [ ] PHP 8.4
- [ ] PHP 8.5

### Laravel

- [ ] Laravel 12
- [ ] Laravel 13

### PostgreSQL

Evaluate and test the PostgreSQL versions officially targeted by the package at release time.

Potential matrix:

- [ ] PostgreSQL 15
- [ ] PostgreSQL 16
- [ ] PostgreSQL 17
- [ ] PostgreSQL 18

The final matrix should reflect versions actually supported by the package and its dependencies at release time.

> **Decisions taken so far:**
> - The minimum PHP version is 8.3, the oldest version tested in CI (Laravel 12 requires PHP 8.2, the Pest 4 test suite PHP 8.3).
> - Laravel 11 (end of security support: March 2026) is no longer supported: `illuminate/*` `^12.0||^13.0`, `orchestra/testbench` `^10.0||^11.0`.
> - PostgreSQL compatibility is guaranteed from PostgreSQL 15 (PostgreSQL 14 reaches end of life in November 2026).
> - `run-tests.yml` CI matrix:
>   - `test` job: Ubuntu and Windows × PHP 8.3 / 8.4 / 8.5 × Laravel 12 / 13 × `prefer-lowest` / `prefer-stable`, excluding the `pgsql` group.
>   - `integration` job: PHP 8.3 / 8.4 / 8.5 × Laravel 12 / 13 × `prefer-lowest` / `prefer-stable` on PostgreSQL 18 (PostGIS 3.6, pgvector), plus one combination each on PostgreSQL 15, 16 and 17 (PostGIS 3.5).
>   - PHP 8.6 (nightly build) runs as an experimental combination in both jobs (`continue-on-error`, `--ignore-platform-req=php+`) until its release. It runs with `display_errors=Off` and `log_errors=Off`: otherwise PHP prints the deprecations raised by third-party code (e.g. `spl_object_hash()` in Laravel 12) as test output, making tests risky (`failOnRisky`). Deprecations are still reported by Pest.
>   - `fail-fast` stays enabled: the first failure cancels the run.

---

## Milestone 16 — Public API audit

Review the complete public API before the first stable release.

### Review

- [ ] Naming
- [ ] Namespaces
- [ ] Exceptions
- [ ] PHPDoc
- [ ] Type declarations
- [ ] Fluent APIs
- [ ] Backward compatibility
- [ ] Public vs internal classes

Pay particular attention to:

```text
PgArray
PgArrayCastable
AsPgArray
As*Array
PgArrayType
PgArrayCast
PgArrayContainer
PgArrayValue
```

Each public component should have one clear responsibility.

---

## Milestone 17 — Documentation

Update the README to describe only implemented and stable functionality.

Suggested structure:

```text
# Laravel PostgreSQL Arrays

## Installation

## Basic usage

## Built-in casts

## Collections

## Multidimensional arrays

## Enums

## Custom value objects

## JSON / JSONB

## Dates and time zones

## Encrypted arrays

## Hashed arrays

## PostgreSQL special types

## Migrations

## Query builder

## Supported PostgreSQL types

## PHP / Laravel compatibility

## Testing

## License
```

Include practical examples for:

```php
AsIntegerArray::class
```

```php
AsIntegerArray::collect()
```

```php
AsPgArray::of(PgArrayCast::Integer)
```

```php
AsPgArray::of(Status::class)
```

```php
AsPgArray::of(Address::class)
```

---

## Milestone 18 — Stable release

- [ ] Stable public API
- [ ] Full test suite green
- [ ] PHPStan green
- [ ] Pint clean
- [ ] PostgreSQL integration suite green
- [ ] CI matrix green
- [ ] README finalized
- [ ] CHANGELOG finalized
- [ ] Examples reviewed
- [ ] Migration documentation reviewed
- [ ] Query Builder documentation reviewed
- [ ] `composer validate`
- [ ] GitHub release
- [ ] Packagist publication
- [ ] Version `1.0.0`

---

## Development workflow

Each milestone should follow:

```text
Implement
   ↓
Add/update tests
   ↓
composer test
   ↓
composer analyse
   ↓
composer format
   ↓
Review diff
   ↓
Commit
   ↓
Next milestone
```

Avoid combining unrelated milestones into a single commit.

When a future feature requires an architectural decision, make that decision immediately before implementing the feature rather than prematurely designing the entire future subsystem.

---

## Roadmap at a glance

```text
01  PostgreSQL primitive types
        ↓
02  Generalize AsPgArray::of()
        ↓
03  Element resolver
        ↓
04  BackedEnum
        ↓
05  PgArrayValue
        ↓
06  JSON / JSONB objects
        ↓
07  External serializers
        ↓
08  Encrypted
        ↓
09  Hashed
        ↓
10  bytea / inet / macaddr / vector / geometry / geography
        ↓
11  Parameterized PostgreSQL types
        ↓
12  Migration helper
        ↓
13  Query Builder
        ↓
14  Real PostgreSQL integration
        ↓
15  PHP/Laravel/PostgreSQL compatibility matrix
        ↓
16  Public API audit
        ↓
17  README / CHANGELOG
        ↓
18  v1.0.0
```
