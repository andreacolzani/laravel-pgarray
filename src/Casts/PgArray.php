<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts;

use AndreaColzani\PgArray\Casts\Values\PgArrayElementDefinition;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCaster;
use AndreaColzani\PgArray\Casts\Values\PgArrayValueCasterResolver;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use AndreaColzani\PgArray\Support\PgArrayParser;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @internal
 *
 * @implements CastsAttributes<array<int, mixed>|Collection<int, mixed>|null, array<int, mixed>|Collection<int, mixed>>
 */
final class PgArray implements CastsAttributes
{
    private readonly PgArrayValueCaster $caster;

    private readonly string $delimiter;

    public function __construct(
        PgArrayCast|PgArrayValueCaster|string $type,
        private readonly PgArrayContainer $container,
        bool $encrypted = false,
    ) {
        $definition = new PgArrayElementDefinition($type, $encrypted);

        $this->caster = PgArrayValueCasterResolver::resolve($definition);
        $this->delimiter = PgArrayValueCasterResolver::delimiter($definition, $this->caster);
    }

    public function get(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): array|Collection|null {
        if ($value === null) {
            return null;
        }

        return $this->toContainer(
            $this->castFromDatabase(
                PgArrayParser::parse($value, $this->delimiter),
            ),
        );
    }

    public function set(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $values = $value instanceof Collection
            ? $value->all()
            : $value;

        return PgArrayParser::serialize(
            $this->castToDatabase($values),
            $this->delimiter,
        );
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, mixed>
     */
    private function castFromDatabase(array $values): array
    {
        return array_map(
            fn (mixed $value): mixed => is_array($value)
                ? $this->castFromDatabase($value)
                : $this->caster->get($value),
            $values,
        );
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, mixed>
     */
    private function castToDatabase(array $values): array
    {
        return array_map(
            fn (mixed $value): mixed => is_array($value)
                ? $this->castToDatabase($value)
                : $this->caster->set($value),
            $values,
        );
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, mixed>|Collection<int, mixed>
     */
    private function toContainer(array $values): array|Collection
    {
        return match ($this->container) {
            PgArrayContainer::Array => $values,
            PgArrayContainer::Collection => new Collection($values),
        };
    }
}
