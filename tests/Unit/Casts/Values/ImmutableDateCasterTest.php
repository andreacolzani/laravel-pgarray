<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\ImmutableDateCaster;
use Carbon\CarbonImmutable;

it('casts postgres dates to CarbonImmutable', function (): void {
    $result = (new ImmutableDateCaster)->get('2026-08-20');

    expect($result)
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($result->toDateString())
        ->toBe('2026-08-20');
});

it('preserves null values', function (): void {
    $caster = new ImmutableDateCaster;

    expect($caster->get(null))
        ->toBeNull();
});
