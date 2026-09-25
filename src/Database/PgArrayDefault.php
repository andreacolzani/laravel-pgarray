<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Database;

use AndreaColzani\PgArray\Support\PgArrayParser;
use BackedEnum;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Grammar;
use InvalidArgumentException;
use Stringable;

/**
 * Default value of a pgArray() column, e.g. '{a,b}'::text[].
 *
 * The cast is rendered when the migration is compiled, so the column type
 * modifiers can be declared before or after default().
 */
final class PgArrayDefault implements Expression
{
    /** @var list<mixed> */
    private readonly array $value;

    /**
     * @param  array<array-key, mixed>|Arrayable<array-key, mixed>  $value
     */
    public function __construct(
        private readonly PgArrayColumnDefinition $column,
        array|Arrayable $value,
    ) {
        $this->value = self::normalize($value);
    }

    /**
     * The PostgreSQL array literal, e.g. {a,b}.
     */
    public function literal(): string
    {
        /** @var array<int, string|int|bool|null|array<int, mixed>> $value */
        $value = $this->value;

        return PgArrayParser::serialize($value);
    }

    public function getValue(Grammar $grammar): string
    {
        return "'".str_replace("'", "''", $this->literal())."'::".$this->column->toArraySql();
    }

    /**
     * @param  array<array-key, mixed>|Arrayable<array-key, mixed>  $value
     * @return list<mixed>
     */
    private static function normalize(array|Arrayable $value): array
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        return array_map(static fn (mixed $element): mixed => match (true) {
            is_array($element), $element instanceof Arrayable => self::normalize($element),
            $element instanceof BackedEnum => $element->value,
            $element instanceof Stringable, is_float($element) => (string) $element,
            $element === null, is_string($element), is_int($element), is_bool($element) => $element,
            default => throw new InvalidArgumentException(sprintf(
                'Unsupported pgArray() default element of type [%s].',
                get_debug_type($element),
            )),
        }, array_values($value));
    }
}
