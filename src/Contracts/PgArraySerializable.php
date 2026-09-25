<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Contracts;

use AndreaColzani\PgArray\Attributes\PgArraySerializer;

/**
 * Declares the external serializer of an element class through a method.
 *
 * Alternative to the #[PgArraySerializer] attribute, which takes precedence
 * when a class uses both. A serializer mapped in the pgarray.serializers
 * configuration overrides both declarations.
 *
 * @see PgArraySerializer
 */
interface PgArraySerializable
{
    /**
     * @return class-string<PgArrayValueSerializer>
     */
    public static function pgArraySerializer(): string;
}
