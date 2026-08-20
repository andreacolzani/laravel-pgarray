<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\PgArrayValueCaster;

it('implements PgArrayValueCaster', function (string $caster): void {
    expect($caster)
        ->toImplement(PgArrayValueCaster::class);
})->with('pg array value casters');
