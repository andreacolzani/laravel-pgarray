<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\DoubleCaster;
use AndreaColzani\PgArray\Casts\Values\FloatCaster;
use AndreaColzani\PgArray\Casts\Values\RealCaster;

it('casts values to double precision', function (mixed $value, ?float $expected): void {
    expect((new DoubleCaster)->get($value))->toBe($expected)
        ->and((new DoubleCaster)->set($value))->toBe($expected);
})->with([
    ['123.45', 123.45],
    [123, 123.0],
    [123.45, 123.45],
    [true, 1.0],
    [false, 0.0],
    [null, null],
]);

it('casts the special floating-point values', function (string $caster): void {
    /** @var DoubleCaster|FloatCaster|RealCaster $instance */
    $instance = new $caster;

    expect($instance->get('Infinity'))->toBe(INF)
        ->and($instance->get('-Infinity'))->toBe(-INF)
        ->and($instance->get('NaN'))->toBeNan()
        ->and($instance->set('-Infinity'))->toBe(-INF)
        ->and($instance->set(INF))->toBe(INF);
})->with([DoubleCaster::class, FloatCaster::class, RealCaster::class]);
