<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\AsPgArray;
use AndreaColzani\PgArray\Casts\PgArray;
use AndreaColzani\PgArray\Casts\Values\UnsupportedElementException;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use AndreaColzani\PgArray\Tests\Models\TestModel;

it('creates a PgArray cast with the default string type and array container', function () {
    $cast = AsPgArray::castUsing([]);

    expect($cast)
        ->toBeInstanceOf(PgArray::class);
});

it('creates a PgArray cast with the specified type', function () {
    $cast = AsPgArray::castUsing([
        PgArrayCast::Integer->value,
    ]);

    expect($cast)
        ->toBeInstanceOf(PgArray::class);
});

it('creates a PgArray cast with the specified container', function () {
    $cast = AsPgArray::castUsing([
        PgArrayCast::Integer->value,
        PgArrayContainer::Collection->value,
    ]);

    expect($cast)
        ->toBeInstanceOf(PgArray::class);
});

it('creates a typed array cast definition', function () {
    expect(AsPgArray::of(PgArrayCast::Integer))
        ->toBe(AsPgArray::class.':integer,array');
});

it('creates a typed collection cast definition', function () {
    expect(
        AsPgArray::of(
            PgArrayCast::Integer,
            PgArrayContainer::Collection,
        ),
    )->toBe(AsPgArray::class.':integer,collection');
});

it('creates a class-string array cast definition', function () {
    expect(AsPgArray::of(Status::class))
        ->toBe(AsPgArray::class.':'.Status::class.',array');
});

it('creates a class-string collection cast definition', function () {
    expect(
        AsPgArray::of(
            Status::class,
            PgArrayContainer::Collection,
        ),
    )->toBe(AsPgArray::class.':'.Status::class.',collection');
});

it('rejects an unknown class-string definition', function () {
    AsPgArray::of('App\\Missing\\Element');
})->throws(
    UnsupportedElementException::class,
    'Unknown element type [App\\Missing\\Element]',
);

it('passes class-string types to the element resolver', function () {
    AsPgArray::castUsing([stdClass::class]);
})->throws(
    UnsupportedElementException::class,
    'Unsupported element type [stdClass].',
);

it('rejects an unknown class-string type', function () {
    AsPgArray::castUsing(['App\\Missing\\Element']);
})->throws(ValueError::class);

it('rejects an unsupported type', function () {
    AsPgArray::castUsing(['unsupported']);
})->throws(ValueError::class);

it('rejects an unsupported container', function () {
    AsPgArray::castUsing([
        PgArrayCast::Integer->value,
        'unsupported',
    ]);
})->throws(ValueError::class);

it('can be used as an Eloquent cast', function () {
    $model = new TestModel;

    expect($model->getCasts())
        ->toMatchArray([
            'tags' => AsPgArray::class,
            'numbers' => AsPgArray::class.':integer,array',
        ]);
});

it('resolves the castable classes', function () {
    $model = new TestModel;

    $casts = $model->getCasts();

    expect($casts['tags'])
        ->toBe(AsPgArray::class)
        ->and($casts['numbers'])
        ->toBe(AsPgArray::class.':integer,array');
});
