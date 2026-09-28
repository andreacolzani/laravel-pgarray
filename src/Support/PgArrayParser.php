<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Support;

use AndreaColzani\PgArray\Exceptions\InvalidDefinitionException;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;

/**
 * Parses and serializes the PostgreSQL array text representation
 * ({a,"b c",NULL}, {{1,2},{3,4}}).
 *
 * Elements are parsed as strings (or null) and serialized from scalar
 * values: converting them to PHP types is up to the element casters.
 *
 * @internal
 */
final class PgArrayParser
{
    /**
     * The array delimiter of most PostgreSQL types (pg_type.typdelim). PostGIS
     * geometry and geography use ':' instead.
     */
    public const DEFAULT_DELIMITER = ',';

    /**
     * Explicit bounds ([0:1]={a,b}) are skipped: elements are always returned
     * as lists.
     *
     * @return array<int, string|int|bool|null|array<int, mixed>>
     */
    public static function parse(string $value, string $delimiter = self::DEFAULT_DELIMITER): array
    {
        if ($value === '') {
            throw new InvalidValueException('The PostgreSQL array cannot be empty.');
        }

        self::ensureDelimiter($delimiter);

        // PostgreSQL prefixes arrays whose lower bound is not 1 with their bounds.
        $position = preg_match('/^(?:\[[+-]?\d+:[+-]?\d+\])+=/', $value, $bounds) === 1
            ? strlen($bounds[0])
            : 0;
        $length = strlen($value);

        return self::parseArray($value, $position, $length, $delimiter);
    }

    /**
     * Serialize a PHP array into a PostgreSQL array representation.
     *
     * @param  array<int, string|int|float|bool|null|array<int, mixed>>  $value
     */
    public static function serialize(array $value, string $delimiter = self::DEFAULT_DELIMITER): string
    {
        self::ensureDelimiter($delimiter);

        return self::serializeArray($value, $delimiter);
    }

    /**
     * A delimiter is a single character with no other meaning in the array
     * syntax, as PostgreSQL requires.
     *
     * @throws InvalidDefinitionException
     */
    public static function ensureDelimiter(string $delimiter): void
    {
        if (strlen($delimiter) !== 1 || str_contains("{}\"\\ \t\n\r\v\f", $delimiter)) {
            throw new InvalidDefinitionException("Invalid PostgreSQL array delimiter [{$delimiter}].");
        }
    }

    /**
     * @return array<int, string|int|bool|null|array<int, mixed>>
     */
    private static function parseArray(
        string $value,
        int &$position,
        int $length,
        string $delimiter,
    ): array {
        if ($position >= $length || $value[$position] !== '{') {
            throw new InvalidValueException(
                'Invalid PostgreSQL array representation.',
            );
        }

        $position++;

        $result = [];

        while ($position < $length) {
            if ($value[$position] === '}') {
                $position++;

                return $result;
            }

            if ($value[$position] === '{') {
                $result[] = self::parseArray($value, $position, $length, $delimiter);
            } else {
                $result[] = self::parseValue($value, $position, $length, $delimiter);
            }

            if ($position >= $length) {
                break;
            }

            if ($value[$position] === $delimiter) {
                $position++;

                continue;
            }

            if ($value[$position] === '}') {
                $position++;

                return $result;
            }

            throw new InvalidValueException(
                'Invalid PostgreSQL array representation.',
            );
        }

        throw new InvalidValueException(
            'Unterminated PostgreSQL array representation.',
        );
    }

    private static function parseValue(
        string $value,
        int &$position,
        int $length,
        string $delimiter,
    ): ?string {
        if ($position >= $length) {
            throw new InvalidValueException(
                'Unexpected end of PostgreSQL array.',
            );
        }

        if ($value[$position] === '"') {
            return self::parseQuotedValue($value, $position, $length);
        }

        $start = $position;

        while (
            $position < $length
            && $value[$position] !== $delimiter
            && $value[$position] !== '}'
        ) {
            $position++;
        }

        $result = substr($value, $start, $position - $start);

        return strtoupper($result) === 'NULL' ? null : $result;
    }

    private static function parseQuotedValue(
        string $value,
        int &$position,
        int $length,
    ): string {
        $position++;

        $result = '';

        while ($position < $length) {
            $character = $value[$position];

            if ($character === '\\') {
                $position++;

                if ($position >= $length) {
                    throw new InvalidValueException(
                        'Invalid escape sequence in PostgreSQL array.',
                    );
                }

                $result .= $value[$position];
                $position++;

                continue;
            }

            if ($character === '"') {
                $position++;

                return $result;
            }

            $result .= $character;
            $position++;
        }

        throw new InvalidValueException(
            'Unterminated quoted value in PostgreSQL array.',
        );
    }

    /**
     * @param  array<int, string|int|float|bool|null|array<int, mixed>>  $value
     */
    private static function serializeArray(array $value, string $delimiter): string
    {
        $values = array_map(
            static function (mixed $item) use ($delimiter): string {
                if (is_array($item)) {
                    return self::serializeArray($item, $delimiter);
                }

                if ($item === null) {
                    return 'NULL';
                }

                return self::serializeValue(self::toText($item), $delimiter);
            },
            $value,
        );

        return '{'.implode($delimiter, $values).'}';
    }

    /**
     * The PostgreSQL text form of a scalar: booleans as t / f, floats with the
     * shortest representation that round trips (var_export(), since (string)
     * only keeps 14 significant digits) or NaN / Infinity / -Infinity.
     */
    public static function toText(string|int|float|bool $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 't' : 'f',
            is_float($value) && is_nan($value) => 'NaN',
            is_float($value) && is_infinite($value) => $value > 0 ? 'Infinity' : '-Infinity',
            is_float($value) => var_export($value, true),
            default => (string) $value,
        };
    }

    private static function serializeValue(string $value, string $delimiter): string
    {
        if ($value === '') {
            return '""';
        }

        // Elements containing ',' are quoted with any delimiter: it is harmless,
        // and ',' is part of the syntax of some element types.
        if (preg_match('/[\s,{}"\\\\'.preg_quote($delimiter, '/').']/', $value) === 1) {
            return '"'.addcslashes($value, '\\"').'"';
        }

        if (strcasecmp($value, 'NULL') === 0) {
            return '"'.$value.'"';
        }

        return $value;
    }
}
