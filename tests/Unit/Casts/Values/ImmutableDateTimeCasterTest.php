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

it('converts datetimes with an offset to the default time zone', function (): void {
    $result = (new ImmutableDateTimeCaster)->get('2026-08-20 14:30:00.5+02');

    expect($result->getTimezone()->getName())
        ->toBe(date_default_timezone_get())
        ->and($result->format('Y-m-d H:i:s.uP'))
        ->toBe('2026-08-20 12:30:00.500000+00:00');
});

it('serializes datetimes with their offset', function (): void {
    expect((new ImmutableDateTimeCaster)->set(CarbonImmutable::parse('2026-08-20 14:30:00.123456', 'Europe/Rome')))
        ->toBe('2026-08-20 14:30:00.123456+02:00')
        ->and((new ImmutableDateTimeCaster)->set('2026-08-20 14:30:00'))
        ->toBe('2026-08-20 14:30:00.000000+00:00');
});

it('preserves null values', function (): void {
    expect((new ImmutableDateTimeCaster)->get(null))
        ->toBeNull();
});
