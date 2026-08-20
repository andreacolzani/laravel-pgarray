<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Tests\Models\TestModel;
use Illuminate\Support\Collection;

it('casts an attribute when retrieving it from an eloquent model', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'numbers' => '{1,2,3}',
    ]);

    expect($model->numbers)
        ->toBe([1, 2, 3]);
});

it('casts an attribute when setting it on an eloquent model', function (): void {
    $model = new TestModel;

    $model->numbers = [1, 2, 3];

    expect($model->getAttributes()['numbers'])
        ->toBe('{1,2,3}');
});

it('supports a collection container when retrieving an attribute', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'number_collection' => '{1,2,3}',
    ]);

    expect($model->number_collection)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect([1, 2, 3]));
});

it('accepts a collection when setting an attribute', function (): void {
    $model = new TestModel;

    $model->number_collection = collect([1, 2, 3]);

    expect($model->getAttributes()['number_collection'])
        ->toBe('{1,2,3}');
});

it('supports a concrete array castable', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'integer_array' => '{1,2,3}',
    ]);

    expect($model->integer_array)
        ->toBe([1, 2, 3]);
});

it('supports a concrete collection castable', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'integer_collection' => '{1,2,3}',
    ]);

    expect($model->integer_collection)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect([1, 2, 3]));
});

it('casts an attribute when mass assigning it', function (): void {
    $model = new TestModel;

    $model->fill([
        'numbers' => [1, 2, 3],
    ]);

    expect($model->getAttributes()['numbers'])
        ->toBe('{1,2,3}');
});
