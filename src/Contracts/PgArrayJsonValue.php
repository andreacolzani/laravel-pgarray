<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Contracts;

use AndreaColzani\PgArray\Concerns\InteractsWithPgArrayJson;

/**
 * Marker contract for custom value objects stored as JSON array elements,
 * i.e. in PostgreSQL json[] / jsonb[] columns.
 *
 *   PHP → toPgArrayValue()   → logical value → json_encode → PostgreSQL element
 *   DB  → PostgreSQL element → json_decode   → fromPgArrayValue() → PHP object
 *
 * toPgArrayValue() may return any JSON-encodable logical value (arrays,
 * nested structures, scalars); returning null stores a SQL NULL element.
 *
 * fromPgArrayValue() receives the decoded JSON value, with JSON objects
 * decoded as associative arrays. It is never called with null: a SQL NULL
 * element and a JSON null element are both retrieved as null.
 *
 * The InteractsWithPgArrayJson trait provides a default implementation of
 * both methods, which classes may override.
 *
 * @see InteractsWithPgArrayJson
 */
interface PgArrayJsonValue extends PgArrayValue {}
