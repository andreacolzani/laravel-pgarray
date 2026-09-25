<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use Carbon\CarbonImmutable;

/**
 * timestamp / timestamptz elements.
 *
 * Unlike Laravel's datetime cast, values are written with their UTC offset
 * (2026-08-20 14:30:00.000000+02:00): timestamptz stores the right instant
 * whatever the session time zone, and timestamp ignores the offset. Values
 * read with an offset (timestamptz) are converted to the default time zone.
 *
 * @internal
 */
final class ImmutableDateTimeCaster extends AbstractCarbonCaster
{
    public function get(mixed $value): ?CarbonImmutable
    {
        return $value === null
            ? null
            : CarbonImmutable::parse((string) $value)->setTimezone(date_default_timezone_get());
    }

    protected function format(): string
    {
        return 'Y-m-d H:i:s.uP';
    }
}
