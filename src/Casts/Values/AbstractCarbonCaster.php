<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use Carbon\Carbon;
use DateTimeInterface;

/**
 * @internal
 */
abstract class AbstractCarbonCaster implements PgArrayValueCaster
{
    public function set(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->normalize($value)
            ->format($this->format());
    }

    protected function normalize(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value)) {
            return Carbon::parse($value);
        }

        throw new InvalidValueException(
            sprintf(
                '%s expects a string or DateTimeInterface.',
                static::class,
            ),
        );
    }

    abstract protected function format(): string;
}
