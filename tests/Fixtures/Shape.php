<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

use AndreaColzani\PgArray\Attributes\PgArraySerializer;

/**
 * A geometry of a "GIS library", stored in geometry[] / geography[] columns
 * through ShapeSerializer.
 */
#[PgArraySerializer(ShapeSerializer::class)]
final class Shape
{
    public function __construct(
        public readonly string $wkt,
        public readonly int $srid = 4326,
    ) {}
}
