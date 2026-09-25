<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

/**
 * Shared by the floating-point casters (real, double precision): PostgreSQL
 * outputs the special values as NaN, Infinity and -Infinity, which a (float)
 * cast would turn into 0.
 *
 * @internal
 */
trait CastsFloats
{
    public function get(mixed $value): ?float
    {
        return match ($value) {
            null => null,
            'NaN' => NAN,
            'Infinity' => INF,
            '-Infinity' => -INF,
            default => (float) $value,
        };
    }

    public function set(mixed $value): ?float
    {
        return $this->get($value);
    }
}
