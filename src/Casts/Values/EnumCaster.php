<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use BackedEnum;
use ReflectionEnum;

/**
 * Casts array elements to and from a BackedEnum.
 *
 *   DB  → BackedEnum case (via tryFrom on the backing value)
 *   PHP → backing value (int|string)
 *
 * Both enum instances and raw backing values are accepted on set(); any value
 * that does not match a case fails explicitly instead of being stored as is.
 *
 * @internal
 */
final class EnumCaster implements PgArrayValueCaster
{
    private readonly bool $intBacked;

    /**
     * @param  class-string<BackedEnum>  $enum
     */
    public function __construct(
        private readonly string $enum,
    ) {
        $this->intBacked = (string) (new ReflectionEnum($enum))->getBackingType() === 'int';
    }

    public function get(mixed $value): ?BackedEnum
    {
        if ($value === null) {
            return null;
        }

        return $this->toCase($value);
    }

    public function set(mixed $value): int|string|null
    {
        if ($value === null) {
            return null;
        }

        return $this->toCase($value)->value;
    }

    private function toCase(mixed $value): BackedEnum
    {
        if ($value instanceof $this->enum) {
            return $value;
        }

        $backingValue = $this->normalize($value);

        return ($backingValue === null ? null : $this->enum::tryFrom($backingValue))
            ?? throw new InvalidValueException(sprintf(
                'Unable to cast [%s] to enum [%s].',
                is_int($value) || is_string($value) ? $value : get_debug_type($value),
                $this->enum,
            ));
    }

    private function normalize(mixed $value): int|string|null
    {
        if ($this->intBacked) {
            $int = is_int($value) || is_string($value)
                ? filter_var($value, FILTER_VALIDATE_INT)
                : false;

            return $int === false ? null : $int;
        }

        return is_string($value) ? $value : null;
    }
}
