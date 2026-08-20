<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\PgArray;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use AndreaColzani\PgArray\Tests\Models\TestModel;
use Illuminate\Support\Collection;

it('casts a postgres string array to a php string array', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::String,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'tags',
        '{"foo","bar"}',
        [],
    ))->toBe([
        'foo',
        'bar',
    ]);
});

it('casts a postgres integer array to a php integer array', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'numbers',
        '{1,2,3}',
        [],
    ))->toBe([1, 2, 3]);
});

it('casts a postgres boolean array to a php boolean array', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Boolean,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'flags',
        '{t,f,t}',
        [],
    ))->toBe([true, false, true]);
});

it('preserves multidimensional arrays', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'numbers',
        '{{1,2},{3,4}}',
        [],
    ))->toBe([
        [1, 2],
        [3, 4],
    ]);
});

it('returns a collection when using the collection container', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Collection,
    );

    $result = $cast->get(
        new TestModel,
        'numbers',
        '{1,2,3}',
        [],
    );

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect([1, 2, 3]));
});

it('serializes a php integer array to postgres', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(
        new TestModel,
        'numbers',
        [1, 2, 3],
        [],
    ))->toBe('{1,2,3}');
});

it('serializes a multidimensional php array to postgres', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(
        new TestModel,
        'numbers',
        [[1, 2], [3, 4]],
        [],
    ))->toBe('{{1,2},{3,4}}');
});

it('serializes a collection to postgres', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Collection,
    );

    expect($cast->set(
        new TestModel,
        'numbers',
        collect([1, 2, 3]),
        [],
    ))->toBe('{1,2,3}');
});

it('returns null when the database value is null', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'numbers',
        null,
        [],
    ))->toBeNull();
});

it('returns null when the assigned value is null', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(
        new TestModel,
        'numbers',
        null,
        [],
    ))->toBeNull();
});

it('preserves null values', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'numbers',
        '{1,NULL,3}',
        [],
    ))->toBe([1, null, 3]);
});

it('serializes null values', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(
        new TestModel,
        'numbers',
        [1, null, 3],
        [],
    ))->toBe('{1,NULL,3}');
});
