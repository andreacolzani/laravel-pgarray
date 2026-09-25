<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Exceptions;

use UnexpectedValueException;

/**
 * Thrown when a value cannot be cast, serialized or parsed: an element that
 * does not match its element type, a malformed PostgreSQL array literal, or
 * an invalid Point / Vector.
 */
final class InvalidValueException extends UnexpectedValueException implements PgArrayException {}
