<?php

namespace AndreaColzani\PgArray\Support;

use InvalidArgumentException;

final class PgArrayParser
{
    /**
     * Parse a PostgreSQL array representation into a PHP array.
     *
     * @return array<int, string|int|bool|null|array<int, mixed>>
     */
    public static function parse(string $value): array
    {
        if ($value === '') {
            throw new InvalidArgumentException('The PostgreSQL array cannot be empty.');
        }

        $position = 0;
        $length = strlen($value);

        return self::parseArray($value, $position, $length);
    }

    /**
     * Serialize a PHP array into a PostgreSQL array representation.
     *
     * @param  array<int, string|int|bool|null|array<int, mixed>>  $value
     */
    public static function serialize(array $value): string
    {
        return self::serializeArray($value);
    }

    /**
     * @return array<int, string|int|bool|null|array<int, mixed>>
     */
    private static function parseArray(
        string $value,
        int &$position,
        int $length,
    ): array {
        if ($position >= $length || $value[$position] !== '{') {
            throw new InvalidArgumentException(
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
                $result[] = self::parseArray($value, $position, $length);
            } else {
                $result[] = self::parseValue($value, $position, $length);
            }

            if ($position >= $length) {
                break;
            }

            if ($value[$position] === ',') {
                $position++;

                continue;
            }

            if ($value[$position] === '}') {
                $position++;

                return $result;
            }

            throw new InvalidArgumentException(
                'Invalid PostgreSQL array representation.',
            );
        }

        throw new InvalidArgumentException(
            'Unterminated PostgreSQL array representation.',
        );
    }

    private static function parseValue(
        string $value,
        int &$position,
        int $length,
    ): ?string {
        if ($position >= $length) {
            throw new InvalidArgumentException(
                'Unexpected end of PostgreSQL array.',
            );
        }

        if ($value[$position] === '"') {
            return self::parseQuotedValue($value, $position, $length);
        }

        $start = $position;

        while (
            $position < $length
            && ! in_array($value[$position], [',', '}'], true)
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
                    throw new InvalidArgumentException(
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

        throw new InvalidArgumentException(
            'Unterminated quoted value in PostgreSQL array.',
        );
    }

    /**
     * @param  array<int, string|int|bool|null|array<int, mixed>>  $value
     */
    private static function serializeArray(array $value): string
    {
        $values = array_map(
            static function (mixed $item): string {
                if (is_array($item)) {
                    return self::serializeArray($item);
                }

                if ($item === null) {
                    return 'NULL';
                }

                $item = match (true) {
                    is_bool($item) => $item ? 't' : 'f',
                    default => (string) $item,
                };

                return self::serializeValue($item);
            },
            $value,
        );

        return '{'.implode(',', $values).'}';
    }

    private static function serializeValue(string $value): string
    {
        if ($value === '') {
            return '""';
        }

        if (preg_match('/[\s,{}"\\\\]/', $value) === 1) {
            return '"'.addcslashes($value, '\\"').'"';
        }

        if (strcasecmp($value, 'NULL') === 0) {
            return '"NULL"';
        }

        return $value;
    }
}
