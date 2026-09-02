<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\ImmutableDateTimeCaster;
use Carbon\CarbonImmutable;

it('casts postgres datetimes to CarbonImmutable', function (): void {
    $result = (new ImmutableDateTimeCaster)->get(
        '2026-08-20 14:30:00.123456',
    );

    expect($result)
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($result->format('Y-m-d H:i:s.u'))
        ->toBe('2026-08-20 14:30:00.123456');
});

it('preserves null values', function (): void {
    expect((new ImmutableDateTimeCaster)->get(null))
        ->toBeNull();
});
