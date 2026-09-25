<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\VectorCaster;
use AndreaColzani\PgArray\Types\Vector;
use Illuminate\Support\Stringable;

it('retrieves vectors', function (): void {
    expect((new VectorCaster)->get('[1,2.5,3]'))
        ->toBeInstanceOf(Vector::class)
        ->toEqual(new Vector([1, 2.5, 3]));
});

it('sets vectors in the pgvector format', function (mixed $value): void {
    expect((new VectorCaster)->set($value))->toBe('[1,2.5,3]');
})->with([
    'vector' => [new Vector([1, 2.5, 3])],
    'string' => ['[1, 2.5, 3]'],
    'stringable' => [new Stringable('[1,2.5,3]')],
]);

it('round trips realistic embeddings', function (): void {
    $components = array_map(
        static fn (int $i): float => round(sin($i) / 10, 6),
        range(1, 1536),
    );

    $caster = new VectorCaster(1536);
    $vector = $caster->get($caster->set(new Vector($components)));

    expect($vector?->dimensions())->toBe(1536)
        ->and($vector?->toArray())->toBe($components);
});

it('validates the configured dimensions', function (): void {
    $caster = new VectorCaster(3);

    expect($caster->set(new Vector([1, 2, 3])))->toBe('[1,2,3]')
        ->and($caster->get('[1,2,3]'))->toEqual(new Vector([1, 2, 3]));
});

it('rejects vectors with other dimensions when setting', function (): void {
    (new VectorCaster(3))->set(new Vector([1, 2]));
})->throws(UnexpectedValueException::class, 'Expected a vector with 3 dimensions, 2 given.');

it('rejects vectors with other dimensions when retrieving', function (): void {
    (new VectorCaster(2))->get('[1,2,3]');
})->throws(UnexpectedValueException::class, 'Expected a vector with 2 dimensions, 3 given.');

it('rejects invalid dimensions', function (): void {
    new VectorCaster(0);
})->throws(InvalidArgumentException::class, 'Vector dimensions must be greater than zero');

it('preserves null', function (): void {
    $caster = new VectorCaster;

    expect($caster->get(null))->toBeNull()
        ->and($caster->set(null))->toBeNull();
});

it('rejects values that are not vectors', function (mixed $value): void {
    (new VectorCaster)->set($value);
})->with([
    'integer' => [1],
    'float' => [1.5],
    'object' => [new stdClass],
])->throws(UnexpectedValueException::class, 'Unable to cast [');
