<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Exceptions;

use Throwable;

/**
 * Marker interface implemented by every exception thrown by the package.
 *
 *   try { ... } catch (PgArrayException $e) { ... }
 *
 * Each exception also extends the SPL exception of its category, so it can
 * be caught as InvalidArgumentException, UnexpectedValueException or
 * RuntimeException as well.
 */
interface PgArrayException extends Throwable {}
