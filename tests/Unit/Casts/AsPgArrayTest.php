<?php

use AndreaColzani\PgArray\Casts\AsPgArray;
use AndreaColzani\PgArray\Casts\PgArray;
use AndreaColzani\PgArray\Enums\PgArrayType;
use AndreaColzani\PgArray\Tests\Models\TestModel;

it('creates a PgArray cast with the default string type', function () {
    $cast = AsPgArray::castUsing([]);

    expect($cast)
        ->toBeInstanceOf(PgArray::class);
});

it('creates a PgArray cast with the specified type', function () {
    $cast = AsPgArray::castUsing([
        PgArrayType::Integer->value,
    ]);

    expect($cast)
        ->toBeInstanceOf(PgArray::class);
});

it('creates a typed cast definition', function () {
    expect(AsPgArray::of(PgArrayType::Integer))
        ->toBe(AsPgArray::class.':integer');
});

it('rejects an unsupported type', function () {
    AsPgArray::castUsing(['unsupported']);
})->throws(ValueError::class);

it('can be used as an Eloquent cast', function () {
    $model = new TestModel;

    expect($model->getCasts())
        ->toMatchArray([
            'tags' => AsPgArray::class,
            'numbers' => AsPgArray::class.':integer',
        ]);
});

it('resolves the castable class', function () {
    $model = new TestModel;

    $casts = $model->getCasts();

    expect($casts['tags'])
        ->toBe(AsPgArray::class)
        ->and($casts['numbers'])
        ->toBe(AsPgArray::class.':integer');
});
