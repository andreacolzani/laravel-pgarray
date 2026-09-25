<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Attributes;

use AndreaColzani\PgArray\Contracts\PgArrayValueSerializer;
use Attribute;

/**
 * Declares the external serializer of an element class.
 *
 *   #[PgArraySerializer(MoneySerializer::class)]
 *   final class Money { ... }
 *
 * The attribute applies to the annotated class only, not to its subclasses.
 * A serializer mapped in the pgarray.serializers configuration overrides it.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class PgArraySerializer
{
    /**
     * @param  class-string<PgArrayValueSerializer>  $serializer
     */
    public function __construct(
        public readonly string $serializer,
    ) {}
}
