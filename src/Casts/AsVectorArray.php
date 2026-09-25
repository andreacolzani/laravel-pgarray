<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Casts\Values\VectorCaster;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use AndreaColzani\PgArray\Exceptions\InvalidDefinitionException;

/**
 * Array of pgvector values (vector[] column), retrieved as Types\Vector.
 *
 * Use withDimensions() to validate the dimensions of every element.
 */
final class AsVectorArray extends PgArrayCastable
{
    /**
     * @param  array{0?: value-of<PgArrayContainer>, 1?: numeric-string}  $arguments
     */
    public static function castUsing(array $arguments): PgArray
    {
        $dimensions = $arguments[1] ?? null;

        if ($dimensions !== null && ! ctype_digit($dimensions)) {
            throw new InvalidDefinitionException(
                "Invalid vector dimensions [{$dimensions}].",
            );
        }

        return new PgArray(
            type: $dimensions === null
                ? PgArrayCast::Vector
                : new VectorCaster((int) $dimensions),
            container: PgArrayContainer::fromCastArgument($arguments[0] ?? null),
        );
    }

    public static function withDimensions(
        int $dimensions,
        PgArrayContainer $container = PgArrayContainer::Array,
    ): string {
        return self::class.':'.$container->value.','.$dimensions;
    }
}
