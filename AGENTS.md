# AGENTS.md

Guidance for AI coding agents working on `andreacolzani/laravel-pgarray`. It records the architecture and the design decisions taken so far: follow them unless the maintainer explicitly asks otherwise. The code and the tests are the source of truth for the implementation; when this file and the code disagree, trust the code and point out the mismatch.

## Project

- Package `andreacolzani/laravel-pgarray`, namespace `AndreaColzani\PgArray`, MIT license, based on `spatie/package-skeleton-laravel` (`spatie/laravel-package-tools`).
- Goal: complete, idiomatic support for PostgreSQL arrays in Laravel: Eloquent casts, a `pgArray()` migration column type and query builder macros for the array operators.
- It is a public portfolio project: API naming, PHPDoc, tests, PHPStan and overall quality matter.
- Supported versions: PHP 8.3 / 8.4 / 8.5, Laravel 12 / 13, PostgreSQL 15–18. Optional extensions: PostGIS 3.5 / 3.6, pgvector.
- Runtime dependencies: `illuminate/{container,contracts,database,support}` `^12.0||^13.0`, `ramsey/uuid` `^4.7`, `symfony/uid` `^7.2||^8.0`, `spatie/laravel-package-tools`.

### Documentation map

| File | Audience | Content |
|---|---|---|
| `README.md` | package users | Short introduction to the implemented, stable features only. Never document planned features. |
| `resources/boost/skills/pgarray-development/SKILL.md` | AI assistants in user apps (Laravel Boost) | The **detailed behavioural spec** of the public API: every cast, modifier, macro, edge case and pitfall. |
| `resources/boost/guidelines/core.blade.php` | AI assistants in user apps | Short Boost guideline. |
| `AGENTS.md` (this file) | agents working on the package | Architecture, internal design decisions, conventions, workflow. |
| `CHANGELOG.md` | users | Updated by the `update-changelog` workflow on release. |

When a public behaviour changes, update `README.md` (if it is covered there) **and** the Boost skill/guideline in the same change.

## Commands

```bash
composer test      # Pest (whole suite; pgsql tests skip without a database)
composer analyse   # PHPStan level 8 (src, config, tests/Fixtures)
composer format    # Pint
vendor/bin/pest --group=pgsql           # real PostgreSQL integration tests only
vendor/bin/pest --exclude-group=pgsql   # everything else
```

A change is complete only when Pest, PHPStan and Pint are all green. Do not add entries to `phpstan-baseline.neon` to silence new errors.

## Git workflow

- `main` is protected: every change goes through a dedicated branch (`feat/…`, `fix/…`, `docs/…`, `ci/…`, `chore/…`) and a pull request to `main`. Never commit or push to `main`. There is no `develop` branch.
- Conventional commit messages (`feat:`, `fix:`, `docs:`, `ci:`, `chore:`, `test:`, `refactor:`).
- Keep each change focused: change only what was asked, and suggest related improvements instead of applying them. Do not combine unrelated work in one commit or PR.
- New public API is proposed and discussed with the maintainer before it is implemented. The maintainer prefers Laravel-style chained modifiers (`->length(50)`) over positional parameters, with defaults that match Laravel.
- When a feature needs an architectural decision, take it right before implementing that feature: do not design future subsystems in advance, and do not add APIs that have not been designed.

## Architecture

### Three separate concepts

```text
PostgreSQL element type  →  Enums\PgArrayType       (migrations, query builder)
PHP element conversion   →  Enums\PgArrayCast       (Eloquent casts)
PHP container            →  Enums\PgArrayContainer  (Array | Collection)
```

- `PgArrayType` is the database level (`Char`, `Varchar`, `Text`, `SmallInt`, `Integer`, `BigInt`, `Real`, `DoublePrecision`, `Decimal`, `Numeric`, `Boolean`, `Date`, `Time`, `TimeTz`, `Timestamp`, `TimestampTz`, `Bytea`, `Uuid`, `Inet`, `MacAddr`, `Json`, `Jsonb`, `Geometry`, `Geography`, `Vector`). Do not add Laravel casts to it.
- `PgArrayCast` is the PHP side (`Boolean`, `Date`, `DateTime`, `ImmutableDate`, `ImmutableDateTime`, `Decimal`, `Double`, `Float`, `Integer`, `Real`, `String`, `Stringable`, `Hashed`, `Bytea`, `Inet`, `MacAddr`, `Vector`, `Geometry`, `Geography`, `Uri`, `Uuid`, `Ulid`).
- Never assume `PgArrayCast == PgArrayType`: e.g. `PgArrayCast::Ulid` has no PostgreSQL type (ULIDs are stored as `char(26)` / `text`), and `char`, `varchar`, `text`, `time`, `timetz` all use `StringCaster`.
- `PgArrayContainer` has only `Array` (default) and `Collection`.

### Cast pipeline

```text
Eloquent model
  → As*Array / AsPgArray                      (Castable, public)
  → Casts\PgArray                             (CastsAttributes, @internal)
      → PgArrayElementDefinition(type, encrypted)
      → PgArrayValueCasterResolver
          ├─ PgArrayValueCaster instance → used as is (configured casters, e.g. VectorCaster with dimensions)
          ├─ PgArrayCast                 → PgArrayValueCasterFactory (exhaustive match, no default)
          └─ class-string, by precedence:
               1. external serializer  → JsonSerializerCaster (PgArrayJsonSerializer) / SerializerCaster
               2. PgArrayJsonValue     → JsonObjectCaster
               3. PgArrayValue         → ObjectCaster
               4. BackedEnum           → EnumCaster
               pure enum / anything else → UnsupportedElementException
          encrypted → wrapped in EncryptedCaster (decorator)
      → Support\PgArrayParser::parse() / serialize()  (structure, quoting, NULL, dimensions)
      → container conversion
```

- `PgArray` owns the array structure and the recursion over dimensions; a `PgArrayValueCaster` converts **one element** (`get(mixed): mixed`, `set(mixed): mixed`, where `set()` returns a scalar logical value or `null`).
- `PgArray::get()`: `null` → `null`; otherwise parse → recursive element cast → container. `PgArray::set()`: `null` → `null`; a `Collection` is converted with `->all()`; then recursive element cast → `PgArrayParser::serialize()`.
- With the `Collection` container only the top level becomes a `Collection`; nested dimensions stay PHP arrays.
- `PgArrayParser` has no external dependency. It must keep distinguishing `{NULL}` (`null`), `{"NULL"}` (the string `'NULL'`, case preserved) and `{""}` (empty string), and support quoting, escaping, delimiters inside quoted values, special characters, empty arrays and multidimensional arrays.
- Parser typing: the internal type is `array<int, string|int|bool|null|array<int, mixed>>`. A recursive type alias was tried and rejected by PHPStan (`typeAlias.circular`): do not reintroduce it.

### Delimiters

PostgreSQL separates elements with the delimiter of the element type (`pg_type.typdelim`): `:` for PostGIS `geometry` / `geography`, `,` otherwise.

- `PgArrayParser::parse()` / `serialize()` and `PgArrayLiteral::from()` take an optional delimiter (`PgArrayParser::DEFAULT_DELIMITER`), validated by `PgArrayParser::ensureDelimiter()` (one character, not `{`, `}`, `"`, `\` or whitespace). Elements containing the delimiter or `,` are quoted.
- `Contracts\PgArrayDelimited` declares a delimiter; it can be implemented by value casters, `PgArrayValue` classes and serializers (`GeometryCaster` and `Types\Point` use `:`). `PgArrayValueCasterResolver::delimiter()` looks at the caster, or for class-strings the serializer then the class; encrypted elements always use `,` (they live in `text[]`).
- `PgArrayType::delimiter()` is used by `pgArray()` defaults and by the query builder, which does not know the column type: with more than one PostGIS value the `$type` argument is required.

## Public API and internals

Covered by semantic versioning:

| Area | Components |
|---|---|
| Eloquent casts | `Casts\AsPgArray`, `Casts\As*Array`, `Casts\PgArrayCastable` |
| Enums | `PgArrayCast`, `PgArrayType`, `PgArrayContainer` |
| Custom elements | `Contracts\*`, `Attributes\PgArraySerializer`, `Concerns\InteractsWithPgArrayJson`, `Support\PgArraySerializerRegistry` |
| Value objects | `Types\Point`, `Types\Vector` |
| Migrations | `$table->pgArray()`, `Database\PgArrayColumnDefinition` modifiers, `Database\PgArrayTypeDefinition` |
| Query builder | `wherePgArray*()` / `orWherePgArray*()`, `pgArrayAppend()` / `pgArrayPrepend()` |
| Hashing | `Support\PgArrayHash` |
| Exceptions | `Exceptions\*` |

`@internal` (may change without notice, never documented for users): `Casts\PgArray`, everything in `Casts\Values` (including the `PgArrayValueCaster` interface), `Database\PgArraySchema`, `PgArrayQuery`, `PgArrayConcatenation`, `PgArrayDefault`, `Support\PgArrayParser`, `PgArrayLiteral`, `PgArrayContainer::fromCastArgument()` and the read accessors of `PgArrayColumnDefinition`. Mark every new implementation class `@internal`. Custom element types are supported through `PgArrayValue`, `PgArrayJsonValue` or serializers, never by exposing value casters.

The package is stateless: there is no root `PgArray` class and no facade (both skeleton leftovers were removed on purpose).

### Exceptions

Every exception lives in `Exceptions`, is `final`, implements the `PgArrayException` marker and extends the SPL exception of its category:

- `UnsupportedElementException` (`InvalidArgumentException`): element type that cannot be resolved.
- `InvalidDefinitionException` (`InvalidArgumentException`): invalid cast arguments, type/column modifiers, delimiters.
- `InvalidValueException` (`UnexpectedValueException`): values that cannot be cast, serialized or parsed.
- `UnsupportedDriverException` (`RuntimeException`): PostgreSQL-only features on another driver.

Arch tests forbid throwing SPL exceptions or `ValueError` outside `Exceptions` (e.g. never let `PgArrayCast::from()` leak a `ValueError`: use `tryFrom()` and throw a package exception). `JsonException` and Laravel's `DecryptException` are allowed to propagate.

## Design decisions

### Castables

- `AsPgArray` is the generic escape hatch: `AsPgArray::of(PgArrayCast|class-string $type, PgArrayContainer $container = Array)` produces `AsPgArray:<type>,<container>`; `AsPgArray::encrypted(...)` appends `,encrypted`. `AsPgArray::class` without arguments means `PgArrayCast::String`. Built-in cast values win over class-strings when parsing the definition. Unknown classes fail with `UnsupportedElementException` when the definition is built.
- Specific castables (`AsIntegerArray`, …) are the preferred API for the common case. They are `final`, extend `PgArrayCastable` and implement `castUsing()` directly, knowing their own `PgArrayCast`.
- `PgArrayCastable` only implements `collect()` (`<Class>:collection`). **Do not add an abstract `type()` method** to the base class: it was discussed and rejected. Concrete castables must not re-implement `collect()`.
- `AsVectorArray::withDimensions(n)` passes a configured `VectorCaster`: this is how parameterized element casters are expressed.

### Adding a new `PgArrayCast`

Update together: the enum case, the value caster, `PgArrayValueCasterFactory`, the specific `As*Array` castable (if it makes sense), the `pg array castables` / value caster datasets, unit tests, integration tests (`tests/Integration`, `PgsqlModel`), the README table and the Boost skill.

### Element types

- **Strings**: `char`, `varchar`, `text`, `time`, `timetz` share `StringCaster`; there are no separate caster classes.
- **Numbers**: `Integer` casts with `(int)`. `Float` / `Double` / `Real` return floats and share the internal `CastsFloats` trait. `Decimal` returns strings to preserve precision: never convert decimals to float. Floats are serialized with the shortest round-trip representation (`var_export()`), because `(string)` keeps only 14 significant digits; `NAN` / `INF` / `-INF` are written as `NaN` / `Infinity` / `-Infinity` and read back.
- **Booleans**: written as `t` / `f`; assigned values are cast with `(bool)`, while unrecognized values read from the database throw instead of being cast silently.
- **Dates**: `AbstractCarbonCaster` holds the shared normalization (do not duplicate it in subclasses). Inputs: Carbon, `CarbonImmutable`, any `DateTimeInterface`, strings, `null`. `Date*` casters write `Y-m-d`; `DateTime*` casters write `Y-m-d H:i:s.uP`, with microseconds **and offset**. Without the offset PostgreSQL reads `timestamptz` values in the session time zone and silently shifts instants. This intentionally differs from Laravel's `datetime` cast (`Y-m-d H:i:s`, `$dateFormat` is ignored), gives the same result when session and application time zones match, and stays correct when they don't. Values read with an offset are converted to the default PHP time zone. Query builder literals format `DateTimeInterface` the same way. Microsecond precision is part of the contract and must stay covered by tests.
- **Uri / Ulid / Uuid**: typed objects, like the other typed casters: `Illuminate\Support\Uri`, `Symfony\Component\Uid\Ulid`, `Ramsey\Uuid\UuidInterface` (`Illuminate\Support\Ulid` / `Uuid` do not exist).
- **Backed enums**: resolved automatically for `AsPgArray::of(Status::class)`. `set()` accepts cases or valid backing values (int-backed values validated with `FILTER_VALIDATE_INT`), rejects cases of other enums; invalid values fail on both `get()` and `set()`. Pure enums are rejected (`UnsupportedElementException::pureEnum()`).
- **`PgArrayValue`**: `toPgArrayValue()` returns a logical PHP value, never an encoded PostgreSQL string, and for plain `PgArrayValue` only `string|int|float|bool|null` (arrays would be read as an extra dimension). `fromPgArrayValue()` receives the PostgreSQL text of the element and is never called with `null`. On `set()` raw values are normalized through `fromPgArrayValue()` → `toPgArrayValue()` so the class validates them; objects of other classes are rejected. A `BackedEnum` implementing `PgArrayValue` uses the contract.
- **JSON (`json[]` / `jsonb[]`)**: declared at class level with the marker `PgArrayJsonValue extends PgArrayValue`, never detected from the value (`"123"` is ambiguous). One `JsonObjectCaster` serves both types. There is **no raw JSON cast** returning associative arrays (Laravel's `json` cast already covers it, and PHP lists would be ambiguous with dimensions). Encoding flags: `JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION`; decoding to associative arrays. SQL `NULL`, JSON `null` and `null` logical values are all `null`. `set()` accepts instances and raw JSON strings, never raw PHP arrays. `InteractsWithPgArrayJson` is optional: properties by name out, constructor named arguments in (nested `PgArrayValue` and backed enums restored from the parameter type, unknown keys ignored, missing required arguments fail).
- **External serializers**: `PgArrayValueSerializer` (scalar storage) and the marker `PgArrayJsonSerializer` (JSON storage). Mapping precedence: `pgarray.serializers` config / `PgArraySerializerRegistry::register()`, then `#[PgArraySerializer]`, then `PgArraySerializable`. Lookup is by exact class name (no inheritance, no interfaces). A serializer overrides `PgArrayJsonValue` / `PgArrayValue` / enum support. `deserialize()` receives the target class (so a serializer can be shared), must return an instance of it, and is never called with `null`. Serializers are resolved once through the container (dependency injection) and shared.
- **Encryption**: an element modifier, not a `PgArrayCast` case. `EncryptedCaster` decorates any resolved caster: `set()` casts first then encrypts the PostgreSQL text form (`t` / `f` for booleans), `get()` decrypts then casts. Each element has its own IV; structure and `NULL` elements stay visible. It is intentionally different from Laravel's `encrypted:array` (whole array in one payload). Uses `Model::currentEncrypter()` on every call (supports `encryptUsing()` and previous keys). Columns must be `text[]` / `varchar[]`.
- **Hashing**: `PgArrayCast::Hashed` + `AsHashedArray`, with no `AsPgArray::hashed()` modifier (read values are always hash strings). Mirrors Laravel's `hashed` cast per element: existing hashes (`Hash::isHashed()`) are stored unchanged and must pass `Hash::verifyConfiguration()`. Accepts strings, integers, floats, `Stringable`. Verification through the static `Support\PgArrayHash::check()` / `find()`. No `HashedArray` wrapper: it would add a third container for two methods.
- **`bytea`**: assigned strings are always raw binary; written in hex format, read in hex and legacy `escape` format.
- **`inet` / `macaddr`**: validated and normalized to PostgreSQL's output form. `cidr` and `macaddr8` are out of scope.
- **`vector`**: elements are `Types\Vector` (immutable, `Countable`, `Stringable`, `JsonSerializable`), because a PHP list would be read as an array dimension. Dimension validation is optional and applied on both `get()` and `set()`.
- **`geometry` / `geography`**: do not turn the package into a GIS library. `GeometryCaster` is a string passthrough (reads hex EWKB, writes WKT / EWKT / EWKB unchanged). `Types\Point(latitude, longitude, srid = 4326)` is the only spatial object (argument order as `tarfin-labs/laravel-spatial`): it writes EWKT, reads 2D EWKB (either byte order) and WKT / EWKT, serializes to GeoJSON. Richer geometries go through external serializers.

### Migrations

- A single `$table->pgArray(string $column, PgArrayType|PgArrayTypeDefinition $type)` Blueprint macro, never one method per type (`stringArray()`, …). It returns `Database\PgArrayColumnDefinition` (a `ColumnDefinition`), so native modifiers keep working. It is compiled by a `typePgArray` grammar macro registered in `PgArraySchema::register()`; other drivers throw `UnsupportedDriverException`.
- `Database\PgArrayTypeDefinition` models parameterized types (`PgArrayType` + modifiers) with named constructors and `toSql()` / `toArraySql(int $dimensions = 1)`. Validation limits are class constants: length 1–10485760, numeric precision 1–1000 with scale 0–precision (the portable rule: PostgreSQL 15+ negative scales are rejected), temporal precision 0–6, vector 1–16000, PostGIS subtypes with optional `Z` / `M` / `ZM` suffix (casing normalized) and SRID ≥ 0. Integer parameters must be real integers. It does not affect Eloquent casts, and there is no parsing from strings until a feature needs it.
- Chained modifiers: `length()`, `precision($precision, $scale = null)`, `size()`, `subtype()` / `srid()`, `dimensions()`, `withoutNullElements()`. Each rebuilds the type definition, so values are validated eagerly; unsupported modifiers throw. Spatial defaults follow Laravel's `geometry()` / `geography()` columns (geography subtype without SRID → 4326; SRID without subtype → `Geometry`).
- `withoutNullElements()` adds `check (array_position(col, NULL) is null)`: one-dimensional arrays only, not with `change()`.
- `default()` accepts arrays and `Arrayable`, rendered by `PgArrayDefault` as `'{…}'::type[]` at compile time.
- `change()` needs the full definition; `using()` is overridden by `PgArrayColumnDefinition` because Laravel only compiles it since 13.23, so it works on every supported version (combining it with `collation()` in a `change()` throws). Dropping uses the native `dropColumn()`.
- PostgreSQL does not enforce or store the declared number of dimensions.

### Query builder

- `Query\Builder` macros registered by `PgArrayQuery::register()` (so they also work on Eloquent builders and relations), named like Laravel's JSON methods: `wherePgArrayContains` / `DoesntContain` (`@>`), `ContainedBy` / `NotContainedBy` (`<@`), `Overlaps` / `DoesntOverlap` (`&&`), each with its `orWhere…` variant. Signature `(string|Expression $column, mixed $values, PgArrayType|PgArrayTypeDefinition|null $type = null)`, compiled by a `wherePgArray` grammar macro.
- Values (array, `Arrayable` or a single value) become one array-literal binding built by `PgArrayLiteral`; PostgreSQL infers the type from the column, `$type` adds an explicit cast. `null` values throw.
- Empty values and negations keep PostgreSQL semantics (`@> '{}'` always true, `<@ '{}'` only empty arrays, `&& '{}'` always false; `not (…)` excludes `NULL` columns, like `whereJsonDoesntContain`).
- `pgArrayAppend()` / `pgArrayPrepend()` `(string $column, mixed $values, $type = null, array $extra = [])` are updates returning affected rows, registered on both builders (the Eloquent one touches `updated_at`). Since `update()` does not support bindings inside expressions, `PgArrayConcatenation` inlines the literal escaped by the connection.

## Coding conventions

- `declare(strict_types=1);` in every PHP file (enforced by arch tests).
- Enum cases in PascalCase (`PgArrayCast::DateTime`, not `DATE_TIME`).
- Use enums, union types, readonly properties, return types and PHPStan-friendly PHPDoc (`value-of<>`, `class-string`, array shapes).
- No premature abstraction: no unnecessary factories, no base classes that only save a few lines, no generic APIs without a real use case.
- Match the style of the surrounding code: short class docblocks that show usage, comments that explain why.

## Testing

Pest 4 / 5 with Orchestra Testbench (dev constraints `^4.0||^5.0`, kept to support PHP 8.3 and Laravel 12). Tests verify public, observable behaviour: avoid reflection and assertions on private state.

Layers, to be kept separate:

1. **Value casters** (`tests/Unit/Casts/Values`): a single element (`"123"` → `123`).
2. **Castable contract** (`tests/Unit/Casts/PgArrayCastableTest.php` + `tests/Datasets`): every `As*Array` implements `Castable` and produces `<Class>:collection`. Add new castables to the dataset; do not duplicate these tests per class.
3. **`AsPgArray`**: the generic API and definition parsing.
4. **`PgArray`**: parser + recursive casting + containers.
5. **Eloquent** (`tests/Feature`, `tests/Models/TestModel`, which uses `casts()`): hydration and assignment round trips without a database.
6. **Real PostgreSQL** (`tests/Integration`, group `pgsql`, `tests/Models/PgsqlModel` on table `pgarray_models` with one `pgArray()` column per cast): write through the cast, check the text stored by PostgreSQL (and server functions such as `cardinality()`, `array_ndims()`, `ST_AsEWKT()`), read back with `fresh()`.

Integration test notes:

- Connection from `PGARRAY_DB_HOST` / `PORT` / `DATABASE` / `USERNAME` / `PASSWORD` / `SSLMODE` (default `postgres@127.0.0.1:5432/pgarray_testing`). Tests are skipped when PostgreSQL or an extension is missing, unless `PGARRAY_REQUIRE_DB=true` (always set in CI). Use the `pgsql()` and `pgsqlExtension()` helpers of `tests/Pest.php`.
- The test database must use UTF8 encoding. Assert SQLSTATE codes, not server messages (they can be localized). `bytea` columns selected directly come back as streams: compare with `encode(…, 'hex')`.
- `TestCase` lowers `hashing.bcrypt.rounds` to 4 and registers the fixture serializers; fixtures live in `tests/Fixtures` (analysed by PHPStan).

## CI

- `run-tests.yml`: `test` job (Ubuntu + Windows × PHP 8.3–8.5 × Laravel 12 / 13 × lowest / stable, `--exclude-group=pgsql`) and `integration` job (`postgis/postgis` images + pgvector: PostgreSQL 18 across the whole PHP / Laravel matrix, plus one combination each for 15, 16, 17). `fail-fast` stays enabled.
- The next PHP release runs as an experimental combination (`continue-on-error`, `--ignore-platform-req=php+`, `display_errors=Off, log_errors=Off` so third-party deprecations don't make tests risky).
- `phpstan.yml` on PHP 8.5; `fix-php-code-style-issues.yml` auto-commits Pint fixes on branches (skipped on `main`); `update-changelog.yml` updates `CHANGELOG.md` on release.
- Dependabot must not use `versioning-strategy: widen`: widen constraints by hand.
- Files that should not ship in the Composer dist are listed as `export-ignore` in `.gitattributes`.
