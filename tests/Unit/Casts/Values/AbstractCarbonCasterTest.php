<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\AbstractCarbonCaster;
use AndreaColzani\PgArray\Exceptions\InvalidValueException;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->caster = new class extends AbstractCarbonCaster
    {
        public function get(mixed $value): ?Carbon
        {
            return $value === null ? null : Carbon::parse((string) $value);
        }

        protected function format(): string
        {
            return 'Y-m-d H:i:s.u';
        }
    };
});

it('serializes Carbon values', function (): void {
    $date = Carbon::createFromFormat(
        'Y-m-d H:i:s.u',
        '2026-08-20 14:30:00.123456',
    );

    expect($this->caster->set($date))
        ->toBe('2026-08-20 14:30:00.123456');
});

it('serializes CarbonImmutable values', function (): void {
    $date = CarbonImmutable::createFromFormat(
        'Y-m-d H:i:s.u',
        '2026-08-20 14:30:00.123456',
    );

    expect($this->caster->set($date))
        ->toBe('2026-08-20 14:30:00.123456');
});

it('serializes DateTimeInterface values', function (): void {
    $date = new DateTimeImmutable(
        '2026-08-20 14:30:00.123456',
    );

    expect($this->caster->set($date))
        ->toBe('2026-08-20 14:30:00.123456');
});

it('serializes date strings', function (): void {
    expect($this->caster->set(
        '2026-08-20 14:30:00.123456',
    ))->toBe('2026-08-20 14:30:00.123456');
});

it('preserves null values', function (): void {
    expect($this->caster->set(null))
        ->toBeNull();
});

it('rejects unsupported values', function (): void {
    $this->caster->set(123);
})->throws(InvalidValueException::class);
