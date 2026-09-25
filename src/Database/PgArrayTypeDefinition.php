<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Database;

use AndreaColzani\PgArray\Enums\PgArrayType;
use AndreaColzani\PgArray\Exceptions\InvalidDefinitionException;
use Stringable;

/**
 * PostgreSQL element type with its optional type modifiers, e.g. varchar(50),
 * decimal(10,2), timestamp(6), vector(1536) or geography(Point,4326).
 *
 *   PgArrayTypeDefinition::varchar(50)->toArraySql();  // varchar(50)[]
 *   new PgArrayTypeDefinition(PgArrayType::Timestamp, [6]);  // timestamp(6)
 *
 * This is a database-level definition (migrations and other SQL generation):
 * it does not affect Eloquent casts. Parameters are validated against the
 * limits documented by PostgreSQL, pgvector and PostGIS.
 */
final class PgArrayTypeDefinition implements Stringable
{
    /** character(n) / character varying(n) */
    public const MAX_LENGTH = 10485760;

    /** numeric(p,s) */
    public const MAX_PRECISION = 1000;

    /** time(p) / timestamp(p) */
    public const MAX_TEMPORAL_PRECISION = 6;

    /** pgvector vector(n) */
    public const MAX_VECTOR_DIMENSIONS = 16000;

    private const SPATIAL_SUBTYPES = [
        'Geometry',
        'Point',
        'LineString',
        'Polygon',
        'MultiPoint',
        'MultiLineString',
        'MultiPolygon',
        'GeometryCollection',
        'CircularString',
        'CompoundCurve',
        'CurvePolygon',
        'MultiCurve',
        'MultiSurface',
        'PolyhedralSurface',
        'Triangle',
        'Tin',
    ];

    /** @var list<int|string> */
    public readonly array $parameters;

    /**
     * @param  array<array-key, int|string>  $parameters
     */
    public function __construct(
        public readonly PgArrayType $type,
        array $parameters = [],
    ) {
        $this->parameters = $this->validate(array_values($parameters));
    }

    public static function of(PgArrayType $type): self
    {
        return new self($type);
    }

    public static function char(?int $length = null): self
    {
        return new self(PgArrayType::Char, self::parameters($length));
    }

    public static function varchar(?int $length = null): self
    {
        return new self(PgArrayType::Varchar, self::parameters($length));
    }

    public static function decimal(?int $precision = null, ?int $scale = null): self
    {
        return new self(PgArrayType::Decimal, self::numericParameters($precision, $scale));
    }

    public static function numeric(?int $precision = null, ?int $scale = null): self
    {
        return new self(PgArrayType::Numeric, self::numericParameters($precision, $scale));
    }

    public static function time(?int $precision = null): self
    {
        return new self(PgArrayType::Time, self::parameters($precision));
    }

    public static function timeTz(?int $precision = null): self
    {
        return new self(PgArrayType::TimeTz, self::parameters($precision));
    }

    public static function timestamp(?int $precision = null): self
    {
        return new self(PgArrayType::Timestamp, self::parameters($precision));
    }

    public static function timestampTz(?int $precision = null): self
    {
        return new self(PgArrayType::TimestampTz, self::parameters($precision));
    }

    public static function vector(?int $dimensions = null): self
    {
        return new self(PgArrayType::Vector, self::parameters($dimensions));
    }

    /**
     * A SRID without subtype uses the generic Geometry subtype, as PostGIS requires.
     */
    public static function geometry(?string $subtype = null, ?int $srid = null): self
    {
        return new self(PgArrayType::Geometry, self::spatialParameters($subtype, $srid));
    }

    /**
     * A SRID without subtype uses the generic Geometry subtype, as PostGIS requires.
     */
    public static function geography(?string $subtype = null, ?int $srid = null): self
    {
        return new self(PgArrayType::Geography, self::spatialParameters($subtype, $srid));
    }

    /**
     * The element type, e.g. varchar(50).
     */
    public function toSql(): string
    {
        return $this->parameters === []
            ? $this->type->value
            : $this->type->value.'('.implode(',', $this->parameters).')';
    }

    /**
     * The array type, e.g. varchar(50)[] or integer[][] for two dimensions.
     *
     * PostgreSQL does not enforce the declared number of dimensions.
     */
    public function toArraySql(int $dimensions = 1): string
    {
        if ($dimensions < 1) {
            throw new InvalidDefinitionException(
                "Array dimensions must be greater than zero, [{$dimensions}] given.",
            );
        }

        return $this->toSql().str_repeat('[]', $dimensions);
    }

    public function __toString(): string
    {
        return $this->toSql();
    }

    /**
     * @return list<int>
     */
    private static function parameters(?int ...$parameters): array
    {
        return array_values(array_filter(
            $parameters,
            static fn (?int $parameter): bool => $parameter !== null,
        ));
    }

    /**
     * @return list<int>
     */
    private static function numericParameters(?int $precision, ?int $scale): array
    {
        if ($precision === null && $scale !== null) {
            throw new InvalidDefinitionException('A numeric scale requires a precision.');
        }

        return self::parameters($precision, $scale);
    }

    /**
     * @return list<int|string>
     */
    private static function spatialParameters(?string $subtype, ?int $srid): array
    {
        return match (true) {
            $srid !== null => [$subtype ?? 'Geometry', $srid],
            $subtype !== null => [$subtype],
            default => [],
        };
    }

    /**
     * @param  list<int|string>  $parameters
     * @return list<int|string>
     */
    private function validate(array $parameters): array
    {
        return match ($this->type) {
            PgArrayType::Char, PgArrayType::Varchar => $this->validateIntegers($parameters, [[1, self::MAX_LENGTH]], 'length'),
            PgArrayType::Decimal, PgArrayType::Numeric => $this->validateNumeric($parameters),
            PgArrayType::Time, PgArrayType::TimeTz,
            PgArrayType::Timestamp, PgArrayType::TimestampTz => $this->validateIntegers($parameters, [[0, self::MAX_TEMPORAL_PRECISION]], 'precision'),
            PgArrayType::Vector => $this->validateIntegers($parameters, [[1, self::MAX_VECTOR_DIMENSIONS]], 'dimensions'),
            PgArrayType::Geometry, PgArrayType::Geography => $this->validateSpatial($parameters),
            default => $this->validateIntegers($parameters, [], 'parameters'),
        };
    }

    /**
     * @param  list<int|string>  $parameters
     * @param  list<array{int, int}>  $ranges  allowed range of each optional parameter
     * @return list<int>
     */
    private function validateIntegers(array $parameters, array $ranges, string $name): array
    {
        if (count($parameters) > count($ranges)) {
            throw $this->invalid($ranges === []
                ? 'does not accept parameters'
                : sprintf('accepts at most %d parameter(s)', count($ranges)));
        }

        $validated = [];

        foreach ($parameters as $index => $parameter) {
            [$min, $max] = $ranges[$index];

            if (! is_int($parameter) || $parameter < $min || $parameter > $max) {
                throw $this->invalid(sprintf(
                    '%s must be an integer between %d and %d, [%s] given',
                    $name,
                    $min,
                    $max,
                    $parameter,
                ));
            }

            $validated[] = $parameter;
        }

        return $validated;
    }

    /**
     * @param  list<int|string>  $parameters
     * @return list<int>
     */
    private function validateNumeric(array $parameters): array
    {
        [$precision, $scale] = $this->validateIntegers(
            $parameters,
            [[1, self::MAX_PRECISION], [0, self::MAX_PRECISION]],
            'precision and scale',
        ) + [null, null];

        if ($precision !== null && $scale !== null && $scale > $precision) {
            throw $this->invalid("scale [{$scale}] cannot be greater than precision [{$precision}]");
        }

        return self::parameters($precision, $scale);
    }

    /**
     * @param  list<int|string>  $parameters
     * @return list<int|string>
     */
    private function validateSpatial(array $parameters): array
    {
        if (count($parameters) > 2) {
            throw $this->invalid('accepts at most 2 parameter(s)');
        }

        if ($parameters === []) {
            return [];
        }

        $subtype = $this->spatialSubtype($parameters[0]);

        if (! array_key_exists(1, $parameters)) {
            return [$subtype];
        }

        $srid = $parameters[1];

        if (! is_int($srid) || $srid < 0) {
            throw $this->invalid("SRID must be a non-negative integer, [{$srid}] given");
        }

        return [$subtype, $srid];
    }

    /**
     * Normalizes the subtype casing (point → Point, pointz → PointZ).
     */
    private function spatialSubtype(int|string $subtype): string
    {
        $pattern = '/^('.implode('|', self::SPATIAL_SUBTYPES).')(ZM|Z|M)?$/i';

        if (! is_string($subtype) || preg_match($pattern, $subtype, $matches) !== 1) {
            throw $this->invalid("unknown spatial subtype [{$subtype}]");
        }

        $base = (string) current(array_filter(
            self::SPATIAL_SUBTYPES,
            static fn (string $candidate): bool => strcasecmp($candidate, $matches[1]) === 0,
        ));

        return $base.strtoupper($matches[2] ?? '');
    }

    private function invalid(string $reason): InvalidDefinitionException
    {
        return new InvalidDefinitionException("Invalid [{$this->type->value}] type definition: {$reason}.");
    }
}
