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

## Milestone 8 — Element-level `Encrypted`

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

- [ ] Implement `EncryptedCaster`.
- [ ] Integrate it into the resolver.
- [ ] Encrypt each element on `set()`.
- [ ] Decrypt each element on `get()`.
- [ ] Handle `null`.
- [ ] Handle Collection.
- [ ] Test full round-trip.
- [ ] Test that encryption remains element-level.
- [ ] Do not duplicate Laravel's existing `encrypted:array` semantics.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 9 — Element-level `Hashed`

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

- [ ] Implement `HashedCaster`.
- [ ] Integrate it into the resolver.
- [ ] Hash each element on `set()`.
- [ ] Preserve hashes on `get()`.
- [ ] Design the verification API.
- [ ] Consider whether a specialized `HashedArray` wrapper is justified.
- [ ] Test positive verification.
- [ ] Test negative verification.
- [ ] Test multiple hashes.
- [ ] Test null.
- [ ] Test Collection.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 10 — Special PostgreSQL types

### `bytea`

Initial representation:

```php
string
```

- [ ] `ByteaCaster`
- [ ] Binary-safe get/set behavior
- [ ] Tests with binary data

### `inet`

Initial representation:

```php
string
```

- [ ] `InetCaster`
- [ ] Validation/normalization as appropriate
- [ ] Tests for IPv4 and IPv6

### `macaddr`

Initial representation:

```php
string
```

- [ ] `MacaddrCaster`
- [ ] Validation/normalization as appropriate
- [ ] Tests

### `vector`

Initial candidate representation:

```php
list<float>
```

- [ ] Define representation.
- [ ] Implement `VectorCaster`.
- [ ] Handle pgvector format.
- [ ] Consider dimension validation.
- [ ] Test realistic vectors.

### `geometry` / `geography`

Do not turn the package into a GIS library.

- [ ] Decide on default representation: WKT, WKB, GeoJSON, or value object.
- [ ] Define serialization/deserialization boundaries.
- [ ] Consider external serializers for richer GIS objects.
- [ ] Implement the minimum useful representation.
- [ ] Add PostgreSQL/PostGIS integration tests where applicable.

### Checkpoint

Run the standard suite and commit each logical group.

---

## Milestone 11 — Parameterized PostgreSQL types

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

- [ ] Design `PgArrayTypeDefinition`.
- [ ] Validate parameters.
- [ ] Support type rendering.
- [ ] Support `varchar(n)`.
- [ ] Support `char(n)`.
- [ ] Support `decimal(p,s)` / `numeric(p,s)`.
- [ ] Support temporal precision.
- [ ] Support `vector(n)`.
- [ ] Add tests.
- [ ] Integrate with migration helpers.
- [ ] Integrate with any other API that needs PostgreSQL type definitions.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 12 — Migration helper

Provide a minimal migration API based around `pgArray()`.

Target concept:

```php
$table->pgArray('tags', PgArrayType::String);
```

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

- [ ] Design final `pgArray()` API.
- [ ] Implement migration helper.
- [ ] Support simple types.
- [ ] Support parameterized types.
- [ ] Support nullable.
- [ ] Support defaults where appropriate.
- [ ] Support alterations if feasible.
- [ ] Support dropping arrays.
- [ ] Test generated SQL.
- [ ] Add PostgreSQL integration tests.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 13 — Query Builder

Support PostgreSQL array operators:

```text
@>  contains
<@  contained by
&&  overlap
||  concatenation
```

Do not finalize method names until this milestone begins.

### Tasks

- [ ] Design public API.
- [ ] Implement contains.
- [ ] Implement contained by.
- [ ] Implement overlap.
- [ ] Implement concatenation if appropriate.
- [ ] Support arrays.
- [ ] Support Collections.
- [ ] Handle empty values.
- [ ] Handle strings/numbers/UUIDs.
- [ ] Handle multidimensional values where applicable.
- [ ] Test generated SQL.
- [ ] Test bindings.
- [ ] Add real PostgreSQL integration tests.

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
- [ ] Special PostgreSQL types

### Query builder

- [ ] Contains
- [ ] Contained by
- [ ] Overlap
- [ ] Concatenation

---

## Milestone 15 — Compatibility matrix

### PHP

- [ ] PHP 8.1
- [ ] PHP 8.2
- [ ] PHP 8.3
- [ ] PHP 8.4

### Laravel

- [ ] Laravel 11
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
