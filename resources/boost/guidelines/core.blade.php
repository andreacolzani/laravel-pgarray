## Laravel PostgreSQL Arrays

This application uses `andreacolzani/laravel-pgarray` for PostgreSQL array columns (`text[]`, `integer[]`, `uuid[]`, `jsonb[]`, …). Activate the `pgarray-development` skill for complete documentation and examples.

- Create array columns with `$table->pgArray('column', PgArrayType::Text)`, never with `json()` columns or raw SQL.
- Cast them with the package casts (`AsStringArray`, `AsIntegerArray`, `AsPgArray::of(...)`, …), never with Laravel's `array`, `json` or `AsCollection` casts.
- Query them with the package macros (`wherePgArrayContains()`, `wherePgArrayOverlaps()`, `wherePgArrayContainedBy()`, `pgArrayAppend()`, …), never by building array literals by hand.
- Cast array attributes cannot be modified in place (`$model->tags[] = 'x'` has no effect): assign a new array instead.

@verbatim
<code-snippet name="PostgreSQL array column, cast and query" lang="php">
use AndreaColzani\PgArray\Casts\AsStringArray;
use AndreaColzani\PgArray\Enums\PgArrayType;

// Migration
$table->pgArray('tags', PgArrayType::Text)->default([]);

// Model
protected function casts(): array
{
    return ['tags' => AsStringArray::class];
}

// Query
Post::wherePgArrayContains('tags', 'laravel')->get();
</code-snippet>
@endverbatim
