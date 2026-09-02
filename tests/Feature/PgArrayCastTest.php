<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Tests\Models\TestModel;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
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

it('supports temporal concrete castables when retrieving attributes', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'date_array' => '{2026-08-20,2026-08-21}',
        'datetime_array' => '{"2026-08-20 14:30:00.123456"}',
        'immutable_date_array' => '{2026-08-20}',
        'immutable_datetime_array' => '{"2026-08-20 14:30:00.123456"}',
    ]);

    expect($model->date_array[0])
        ->toBeInstanceOf(Carbon::class)
        ->and($model->date_array[0]->toDateString())
        ->toBe('2026-08-20')
        ->and($model->datetime_array[0])
        ->toBeInstanceOf(Carbon::class)
        ->and($model->datetime_array[0]->format('Y-m-d H:i:s.u'))
        ->toBe('2026-08-20 14:30:00.123456')
        ->and($model->immutable_date_array[0])
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($model->immutable_date_array[0]->toDateString())
        ->toBe('2026-08-20')
        ->and($model->immutable_datetime_array[0])
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($model->immutable_datetime_array[0]->format('Y-m-d H:i:s.u'))
        ->toBe('2026-08-20 14:30:00.123456');
});

it('serializes temporal concrete castables when setting attributes', function (): void {
    $model = new TestModel;

    $model->date_array = [
        Carbon::parse('2026-08-20 14:30:00'),
    ];
    $model->datetime_array = [
        new DateTimeImmutable('2026-08-20 14:30:00.123456'),
    ];
    $model->immutable_date_array = [
        CarbonImmutable::parse('2026-08-20 14:30:00'),
    ];
    $model->immutable_datetime_array = [
        CarbonImmutable::parse('2026-08-20 14:30:00.123456'),
    ];

    expect($model->getAttributes()['date_array'])
        ->toBe('{2026-08-20}')
        ->and($model->getAttributes()['datetime_array'])
        ->toBe('{"2026-08-20 14:30:00.123456"}')
        ->and($model->getAttributes()['immutable_date_array'])
        ->toBe('{2026-08-20}')
        ->and($model->getAttributes()['immutable_datetime_array'])
        ->toBe('{"2026-08-20 14:30:00.123456"}');
});

it('supports a temporal concrete collection castable', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'datetime_collection' => '{"2026-08-20 14:30:00.123456"}',
    ]);

    expect($model->datetime_collection)
        ->toBeInstanceOf(Collection::class)
        ->and($model->datetime_collection->first())
        ->toBeInstanceOf(Carbon::class)
        ->and($model->datetime_collection->first()->format('Y-m-d H:i:s.u'))
        ->toBe('2026-08-20 14:30:00.123456');
});
