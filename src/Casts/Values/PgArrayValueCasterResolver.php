<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Enums\PgArrayCast;
use BackedEnum;
use UnitEnum;

/**
 * Resolution layer between PgArray and individual value casters.
 *
 *   Resolver
 *    ├── PgArrayCast  →  PgArrayValueCasterFactory
 *    └── class-string →  EnumCaster (BackedEnum) / Object/etc.
 *
 * Built-in PgArrayCast values are delegated to PgArrayValueCasterFactory,
 * keeping that factory focused on built-in casters. BackedEnum class-strings
 * resolve to EnumCaster; pure (UnitEnum) enums have no storable representation
 * and are rejected explicitly. PgArrayValue objects (Milestone 5) will be
 * added in a subsequent milestone.
 */
final class PgArrayValueCasterResolver
{
    public static function resolve(PgArrayElementDefinition $definition): PgArrayValueCaster
    {
        $type = $definition->type;

        if ($type instanceof PgArrayCast) {
            return PgArrayValueCasterFactory::make($type);
        }

        return self::resolveClassString($type);
    }

    /**
     * @throws UnsupportedElementException
     */
    private static function resolveClassString(string $type): PgArrayValueCaster
    {
        if (! class_exists($type)) {
            throw UnsupportedElementException::unknownClass($type);
        }

        if (is_subclass_of($type, BackedEnum::class)) {
            return new EnumCaster($type);
        }

        if (is_subclass_of($type, UnitEnum::class)) {
            throw UnsupportedElementException::pureEnum($type);
        }

        throw UnsupportedElementException::unsupportedClass($type);
    }
}
