<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Concerns;

use AndreaColzani\PgArray\Contracts\PgArrayValue;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use BackedEnum;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Default JSON serialization for PgArrayJsonValue implementations.
 *
 *   toPgArrayValue()   → the object's properties, keyed by name
 *   fromPgArrayValue() → a new instance, passing the decoded keys to the
 *                        constructor as named arguments
 *
 * Nested PgArrayValue objects are serialized through their own contract and
 * restored from the constructor parameter type, as are backed enums. Keys
 * that do not match a constructor parameter are ignored, so stored elements
 * keep working after a property is removed.
 *
 * Classes can override either method to change the stored structure.
 */
trait InteractsWithPgArrayJson
{
    public function toPgArrayValue(): mixed
    {
        return array_map(
            static fn (mixed $value): mixed => $value instanceof PgArrayValue
                ? $value->toPgArrayValue()
                : $value,
            get_object_vars($this),
        );
    }

    public static function fromPgArrayValue(mixed $value): static
    {
        if (! is_array($value)) {
            throw new InvalidValueException(sprintf(
                'Unable to create [%s] from [%s]: expected a JSON object.',
                static::class,
                get_debug_type($value),
            ));
        }

        $class = new ReflectionClass(static::class);
        $arguments = [];

        foreach ($class->getConstructor()?->getParameters() ?? [] as $parameter) {
            if (array_key_exists($parameter->getName(), $value)) {
                $arguments[$parameter->getName()] = self::restorePgArrayJsonArgument(
                    $parameter,
                    $value[$parameter->getName()],
                );
            }
        }

        return $class->newInstanceArgs($arguments);
    }

    private static function restorePgArrayJsonArgument(ReflectionParameter $parameter, mixed $value): mixed
    {
        $type = $parameter->getType();

        if ($value === null || ! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return $value;
        }

        $class = $type->getName();

        if (is_subclass_of($class, PgArrayValue::class)) {
            return $value instanceof $class ? $value : $class::fromPgArrayValue($value);
        }

        if (is_subclass_of($class, BackedEnum::class) && (is_int($value) || is_string($value))) {
            return $class::tryFrom($value) ?? throw new InvalidValueException(sprintf(
                'Unable to cast [%s] to enum [%s].',
                $value,
                $class,
            ));
        }

        return $value;
    }
}
