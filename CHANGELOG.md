# Changelog

All notable changes to `laravel-pgarray` will be documented in this file.

## 1.0.1 - 2026-09-28

### Fixed

- Documented config publish tag: the config file is published with `php artisan vendor:publish --tag="pgarray-config"` (the `laravel-pgarray-config` tag in the 1.0.0 README and Boost skill found nothing).

## 1.0.0 - 2026-09-28

First stable release: complete, idiomatic support for PostgreSQL arrays in Laravel.

Requires PHP 8.3+, Laravel 12 or 13 and PostgreSQL 15+. PostGIS and pgvector are optional.

### Eloquent casts

- A cast for each element type: `AsStringArray`, `AsIntegerArray`, `AsDecimalArray`, `AsFloatArray`, `AsDoubleArray`, `AsRealArray`, `AsBooleanArray`, `AsDateArray`, `AsDateTimeArray` (and immutable variants), `AsUuidArray`, `AsUlidArray`, `AsStringableArray`, `AsUriArray`.
- Arrays or Collections (`::collect()`), multidimensional arrays, `NULL` elements, empty arrays and any quoting or escaping.
- `AsPgArray::of()` for backed enums, value objects (`PgArrayValue`), JSON objects in `json[]` / `jsonb[]` columns (`PgArrayJsonValue` + `InteractsWithPgArrayJson`) and classes handled by external serializers.
- Element-level encryption (`AsEncryptedArray`, `AsPgArray::encrypted()`) and hashing (`AsHashedArray` + `PgArrayHash`).
- Special types: `bytea[]`, `inet[]`, `macaddr[]`, pgvector `vector[]` (`Vector`) and PostGIS `geometry[]` / `geography[]` (`Point`).
- Date-times keep microseconds and UTC offset, so `timestamptz[]` stores the right instant whatever the session time zone.

### Migrations

- `$table->pgArray('tags', PgArrayType::Text)` with type modifiers (`length()`, `precision()`, `size()`, `subtype()`, `srid()`), `dimensions()`, `withoutNullElements()`, array defaults and `change()` / `using()` on every supported Laravel version.

### Query builder

- `wherePgArrayContains()`, `wherePgArrayContainedBy()`, `wherePgArrayOverlaps()` with their negated and `orWhere…` variants.
- `pgArrayAppend()` / `pgArrayPrepend()` to update arrays without loading the rows.

### AI assistants

- A Laravel Boost guideline and `pgarray-development` skill.

Every exception implements `AndreaColzani\PgArray\Exceptions\PgArrayException`.
