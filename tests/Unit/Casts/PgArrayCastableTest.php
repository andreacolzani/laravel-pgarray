<?php

declare(strict_types=1);

use Illuminate\Contracts\Database\Eloquent\Castable;

it('implements Castable', function (string $castable): void {
    expect($castable)
        ->toImplement(Castable::class);
})->with('pg array castables');

it('creates a collection cast definition', function (string $castable): void {
    expect($castable::collect())
        ->toBe($castable.':collection');
})->with('pg array castables');
