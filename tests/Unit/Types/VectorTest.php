<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Types\Vector;

it('stores components as floats', function (): void {
    $vector = new Vector([1, 2.5, -3]);

    expect($vector->toArray())->toBe([1.0, 2.5, -3.0])
        ->and($vector->dimensions())->toBe(3)
        ->and($vector)->toHaveCount(3);
});

it('reindexes components', function (): void {
    expect((new Vector(['a' => 1, 'b' => 2]))->toArray())->toBe([1.0, 2.0]);
});

it('parses the pgvector format', function (string $value, array $expected): void {
    expect(Vector::fromString($value)->toArray())->toBe($expected);
})->with([
    'integers' => ['[1,2,3]', [1.0, 2.0, 3.0]],
    'decimals' => ['[0.5,-1.25]', [0.5, -1.25]],
    'exponent' => ['[1e-07,2.5E+3]', [1.0E-7, 2500.0]],
    'spaces' => [' [ 1 , 2 ] ', [1.0, 2.0]],
    'single' => ['[42]', [42.0]],
]);

it('formats the pgvector representation', function (): void {
    expect((string) new Vector([1, 2.5, -3, 1.0E-7]))->toBe('[1,2.5,-3,1.0E-7]');
});

it('round trips the pgvector representation', function (): void {
    $vector = new Vector([0.1, -0.2, 0.3]);

    expect(Vector::fromString((string) $vector))->toEqual($vector);
});

it('serializes to a JSON list', function (): void {
    expect(json_decode((string) json_encode(new Vector([1, 2.5]))))->toEqual([1.0, 2.5]);
});

it('rejects invalid pgvector strings', function (string $value): void {
    Vector::fromString($value);
})->with([
    'missing brackets' => ['1,2,3'],
    'postgres array' => ['{1,2,3}'],
    'empty' => ['[]'],
    'non numeric' => ['[1,a]'],
    'trailing comma' => ['[1,2,]'],
])->throws(UnexpectedValueException::class);

it('rejects empty vectors', function (): void {
    new Vector([]);
})->throws(UnexpectedValueException::class, 'at least one dimension');

it('rejects non finite or non numeric components', function (mixed $value): void {
    new Vector([1, $value]);
})->with([
    'nan' => [NAN],
    'infinity' => [INF],
    'string' => ['1'],
    'null' => [null],
])->throws(UnexpectedValueException::class, 'Vector components must be finite numbers');
