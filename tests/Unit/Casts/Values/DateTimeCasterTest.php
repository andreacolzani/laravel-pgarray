<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\DateTimeCaster;
use Carbon\Carbon;

it('casts postgres datetimes to Carbon', function (): void {
    $result = (new DateTimeCaster)->get(
        '2026-08-20 14:30:00.123456',
    );

    expect($result)
        ->toBeInstanceOf(Carbon::class)
        ->and($result->format('Y-m-d H:i:s.u'))
        ->toBe('2026-08-20 14:30:00.123456');
});

it('preserves null values', function (): void {
    expect((new DateTimeCaster)->get(null))
        ->toBeNull();
});
