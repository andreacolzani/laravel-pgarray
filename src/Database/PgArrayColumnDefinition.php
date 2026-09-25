<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Database;

use AndreaColzani\PgArray\Enums\PgArrayType;
use AndreaColzani\PgArray\Exceptions\InvalidDefinitionException;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Schema\ColumnDefinition;

/**
 * Column definition returned by the pgArray() migration helper.
 *
 *   $table->pgArray('codes', PgArrayType::Varchar)->length(50)->nullable();
 *   $table->pgArray('matrix', PgArrayType::Integer)->dimensions(2)->default([[1, 2], [3, 4]]);
 *
 * Type modifiers are validated eagerly through PgArrayTypeDefinition.
 */
final class PgArrayColumnDefinition extends ColumnDefinition
{
    /** Laravel's default SRID for geography columns. */
    public const DEFAULT_GEOGRAPHY_SRID = 4326;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(PgArrayType|PgArrayTypeDefinition $type, array $attributes = [])
    {
        parent::__construct($attributes);

        $this->attributes['pgArrayDefinition'] = $type instanceof PgArrayType
            ? PgArrayTypeDefinition::of($type)
            : $type;
        $this->attributes['pgArrayDimensions'] = 1;
        $this->attributes['pgArrayWithoutNullElements'] = false;
    }

    /**
     * @internal
     */
    public function definition(): PgArrayTypeDefinition
    {
        /** @var PgArrayTypeDefinition */
        return $this->attributes['pgArrayDefinition'];
    }

    /**
     * @internal
     */
    public function arrayDimensions(): int
    {
        /** @var int */
        return $this->attributes['pgArrayDimensions'];
    }

    /**
     * @internal
     */
    public function forbidsNullElements(): bool
    {
        return $this->attributes['pgArrayWithoutNullElements'] === true;
    }

    /**
     * @internal
     */
    public function usingExpression(): string|Expression|null
    {
        /** @var string|Expression|null */
        return $this->attributes['pgArrayUsing'] ?? null;
    }

    /**
     * The array type, e.g. varchar(50)[].
     *
     * @internal
     */
    public function toArraySql(): string
    {
        return $this->definition()->toArraySql($this->arrayDimensions());
    }

    /**
     * char(n) / varchar(n) length.
     */
    public function length(int $length): self
    {
        $this->ensureType('length()', PgArrayType::Char, PgArrayType::Varchar);

        return $this->withParameters([$length]);
    }

    /**
     * decimal(p,s) / numeric(p,s) precision and scale, or the fractional
     * seconds precision of time / timetz / timestamp / timestamptz.
     */
    public function precision(int $precision, ?int $scale = null): self
    {
        $type = $this->ensureType(
            'precision()',
            PgArrayType::Decimal,
            PgArrayType::Numeric,
            PgArrayType::Time,
            PgArrayType::TimeTz,
            PgArrayType::Timestamp,
            PgArrayType::TimestampTz,
        );

        if ($scale !== null && ! in_array($type, [PgArrayType::Decimal, PgArrayType::Numeric], true)) {
            throw new InvalidDefinitionException("A scale is only supported by decimal and numeric, [{$type->value}] given.");
        }

        return $this->withParameters($scale === null ? [$precision] : [$precision, $scale]);
    }

    /**
     * pgvector vector(n) dimensions.
     */
    public function size(int $dimensions): self
    {
        $this->ensureType('size()', PgArrayType::Vector);

        return $this->withParameters([$dimensions]);
    }

    /**
     * PostGIS subtype, e.g. Point. As in Laravel's geography() column, a
     * geography subtype without an explicit SRID uses SRID 4326.
     */
    public function subtype(string $subtype): self
    {
        $type = $this->ensureType('subtype()', PgArrayType::Geometry, PgArrayType::Geography);

        $srid = $this->definition()->parameters[1]
            ?? ($type === PgArrayType::Geography ? self::DEFAULT_GEOGRAPHY_SRID : null);

        return $this->withParameters($srid === null ? [$subtype] : [$subtype, $srid]);
    }

    /**
     * PostGIS SRID. Without a subtype the generic Geometry subtype is used,
     * as PostGIS requires.
     */
    public function srid(int $srid): self
    {
        $this->ensureType('srid()', PgArrayType::Geometry, PgArrayType::Geography);

        return $this->withParameters([$this->definition()->parameters[0] ?? 'Geometry', $srid]);
    }

    /**
     * Number of array dimensions, e.g. 2 for integer[][].
     *
     * PostgreSQL does not enforce the declared number of dimensions.
     */
    public function dimensions(int $dimensions): self
    {
        if ($dimensions < 1) {
            throw new InvalidDefinitionException("Array dimensions must be greater than zero, [{$dimensions}] given.");
        }

        if ($dimensions > 1 && $this->forbidsNullElements()) {
            throw self::multidimensionalNullElements();
        }

        $this->attributes['pgArrayDimensions'] = $dimensions;

        return $this;
    }

    /**
     * Forbid NULL elements with a CHECK constraint. Use nullable() to allow
     * a NULL column value.
     *
     * Only supported by one-dimensional arrays, when creating or adding the column.
     */
    public function withoutNullElements(): self
    {
        if ($this->arrayDimensions() > 1) {
            throw self::multidimensionalNullElements();
        }

        $this->attributes['pgArrayWithoutNullElements'] = true;

        return $this;
    }

    /**
     * PHP arrays and Arrayable values (e.g. Collections) are rendered as a
     * PostgreSQL array literal cast to the column type.
     */
    public function default(mixed $value): self
    {
        $this->attributes['default'] = is_array($value) || $value instanceof Arrayable
            ? new PgArrayDefault($this, $value)
            : $value;

        return $this;
    }

    /**
     * Casting expression applied when changing the column type
     * (ALTER COLUMN ... TYPE ... USING ...).
     *
     * Laravel only compiles using() since 13.23: the package compiles it
     * itself, so it works on every supported Laravel version.
     */
    public function using(string|Expression $expression): self
    {
        $this->attributes['pgArrayUsing'] = $expression;

        return $this;
    }

    private function ensureType(string $modifier, PgArrayType ...$types): PgArrayType
    {
        $type = $this->definition()->type;

        if (! in_array($type, $types, true)) {
            throw new InvalidDefinitionException(sprintf(
                '%s is only supported by %s, [%s] given.',
                $modifier,
                implode(', ', array_map(static fn (PgArrayType $type): string => $type->value, $types)),
                $type->value,
            ));
        }

        return $type;
    }

    /**
     * @param  list<int|string>  $parameters
     */
    private function withParameters(array $parameters): self
    {
        $this->attributes['pgArrayDefinition'] = new PgArrayTypeDefinition($this->definition()->type, $parameters);

        return $this;
    }

    private static function multidimensionalNullElements(): InvalidDefinitionException
    {
        return new InvalidDefinitionException('withoutNullElements() is only supported by one-dimensional arrays.');
    }
}
