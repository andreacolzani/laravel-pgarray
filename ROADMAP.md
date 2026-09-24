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
- [x] Resolve class-string element types (routing only: unknown classes, `BackedEnum` until Milestone 4, and other classes fail with `UnsupportedElementException`).
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

## Milestone 4 — Automatic `BackedEnum` support

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

- [ ] Implement `EnumCaster`.
- [ ] Support string-backed enums.
- [ ] Support int-backed enums.
- [ ] Handle invalid backing values explicitly.
- [ ] Keep `UnitEnum` unsupported automatically unless a future explicit representation is designed.
- [ ] Test hydration.
- [ ] Test serialization.
- [ ] Test multidimensional arrays.
- [ ] Test Collection containers.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 5 — `PgArrayValue` contract

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

- [ ] Create `Contracts/PgArrayValue.php`.
- [ ] Define/document the contract.
- [ ] Implement the generic object caster.
- [ ] Resolve `PgArrayValue` implementations automatically.
- [ ] Test object → logical value.
- [ ] Test logical value → object.
- [ ] Test full array round-trip.
- [ ] Test Collection.
- [ ] Test multidimensional arrays.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 6 — JSON / JSONB object serialization

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

- [ ] Implement/verify `JsonCaster`.
- [ ] Implement/verify `JsonbCaster`.
- [ ] Define object → JSON behavior.
- [ ] Define JSON → object behavior.
- [ ] Preserve nested structures.
- [ ] Test `Address[]`.
- [ ] Test `Address[][]`.
- [ ] Test `null` values.
- [ ] Test JSON edge cases.
- [ ] Test full Eloquent round-trip.

### Checkpoint

Run the standard suite and commit.

---

## Milestone 7 — External serializers

Add optional support for classes that cannot be modified, require complex serialization, or share serialization rules.

Potential contract:

```php
interface PgArrayValueSerializer
{
    public function serialize(mixed $value): mixed;

    public function deserialize(mixed $value): mixed;
}
```

The exact naming and API should be finalized when implementing this milestone.

### Tasks

- [ ] Define serializer contract.
- [ ] Decide configuration/registry mechanism.
- [ ] Support class → serializer mapping.
- [ ] Support serializers shared by multiple classes.
- [ ] Integrate external serializers into the resolver.
- [ ] Define precedence relative to `PgArrayValue`.
- [ ] Test unmodifiable classes.
- [ ] Test shared serializers.
- [ ] Test missing serializer errors.
- [ ] Test Collection and multidimensional arrays.

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
