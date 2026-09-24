<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use AndreaColzani\PgArray\Enums\PgArrayCast;
use BackedEnum;

/**
 * Resolution layer between PgArray and individual value casters.
 *
 *   Resolver
 *    ├── PgArrayCast  →  PgArrayValueCasterFactory
 *    └── class-string →  Enum/Object/etc.
 *
 * Built-in PgArrayCast values are delegated to PgArrayValueCasterFactory,
 * keeping that factory focused on built-in casters. Class-string casters
 * for enums (Milestone 4) and PgArrayValue objects (Milestone 5) will be
 * added in subsequent milestones.
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
            throw UnsupportedElementException::notYetSupported($type);
        }

        throw UnsupportedElementException::unsupportedClass($type);
    }
}
