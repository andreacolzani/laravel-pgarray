<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\DateCaster;
use Carbon\Carbon;

it('casts postgres dates to Carbon', function (): void {
    $result = (new DateCaster)->get('2026-08-20');

    expect($result)
        ->toBeInstanceOf(Carbon::class)
        ->and($result->toDateString())
        ->toBe('2026-08-20');
});

it('preserves null values', function (): void {
    $caster = new DateCaster;

    expect($caster->get(null))
        ->toBeNull();
});
