<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Contracts;

/**
 * Marker contract for external serializers whose logical values are stored
 * as JSON array elements, i.e. in PostgreSQL json[] / jsonb[] columns.
 *
 *   PHP → serialize()        → logical value → json_encode → PostgreSQL element
 *   DB  → PostgreSQL element → json_decode   → deserialize() → PHP object
 *
 * serialize() may return any JSON-encodable logical value; returning null
 * stores a SQL NULL element.
 *
 * deserialize() receives the decoded JSON value, with JSON objects decoded as
 * associative arrays. It is never called with null: a SQL NULL element and a
 * JSON null element are both retrieved as null.
 */
interface PgArrayJsonSerializer extends PgArrayValueSerializer {}
