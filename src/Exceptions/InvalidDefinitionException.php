<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a cast, type or column definition is invalid: unknown cast
 * arguments, type modifiers outside the PostgreSQL limits, column modifiers
 * that do not apply to the element type, or an invalid array delimiter.
 */
final class InvalidDefinitionException extends InvalidArgumentException implements PgArrayException {}
