<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Support;

use AndreaColzani\PgArray\Attributes\PgArraySerializer;
use AndreaColzani\PgArray\Casts\Values\UnsupportedElementException;
use AndreaColzani\PgArray\Contracts\PgArraySerializable;
use AndreaColzani\PgArray\Contracts\PgArrayValueSerializer;
use Illuminate\Contracts\Container\Container;
use ReflectionClass;

/**
 * Maps element classes to external serializers.
 *
 * Lookup is by exact class name, in order of precedence:
 *
 *   1. serializers registered here (the pgarray.serializers configuration
 *      is registered when the registry is built)
 *   2. the #[PgArraySerializer] attribute on the class
 *   3. the PgArraySerializable contract
 *
 * Serializer classes are instantiated through the container once and shared
 * by every class they are mapped to.
 */
final class PgArraySerializerRegistry
{
    /** @var array<string, class-string<PgArrayValueSerializer>|PgArrayValueSerializer> */
    private array $serializers = [];

    /** @var array<string, PgArrayValueSerializer> */
    private array $instances = [];

    /**
     * @param  array<class-string, class-string<PgArrayValueSerializer>|PgArrayValueSerializer>  $serializers
     */
    public function __construct(
        private readonly Container $container,
        array $serializers = [],
    ) {
        foreach ($serializers as $class => $serializer) {
            $this->register($class, $serializer);
        }
    }

    /**
     * @param  class-string|list<class-string>  $classes
     * @param  class-string<PgArrayValueSerializer>|PgArrayValueSerializer  $serializer
     */
    public function register(string|array $classes, string|PgArrayValueSerializer $serializer): void
    {
        foreach ((array) $classes as $class) {
            $this->serializers[$class] = $serializer;
        }
    }

    /**
     * @param  class-string  $class
     *
     * @throws UnsupportedElementException
     */
    public function resolve(string $class): ?PgArrayValueSerializer
    {
        $serializer = $this->serializers[$class] ?? self::declaredSerializer($class);

        if ($serializer === null || $serializer instanceof PgArrayValueSerializer) {
            return $serializer;
        }

        return $this->instances[$serializer] ??= $this->instantiate($class, $serializer);
    }

    /**
     * @param  class-string  $class
     */
    private static function declaredSerializer(string $class): ?string
    {
        $attributes = (new ReflectionClass($class))->getAttributes(PgArraySerializer::class);

        if ($attributes !== []) {
            return $attributes[0]->newInstance()->serializer;
        }

        if (is_subclass_of($class, PgArraySerializable::class)) {
            return $class::pgArraySerializer();
        }

        return null;
    }

    /**
     * @throws UnsupportedElementException
     */
    private function instantiate(string $class, string $serializer): PgArrayValueSerializer
    {
        if (! is_subclass_of($serializer, PgArrayValueSerializer::class)) {
            throw UnsupportedElementException::invalidSerializer($class, $serializer);
        }

        return $this->container->make($serializer);
    }
}
