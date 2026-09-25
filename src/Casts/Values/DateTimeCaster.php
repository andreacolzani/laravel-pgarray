<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use Carbon\Carbon;

/**
 * timestamp / timestamptz elements.
 *
 * Unlike Laravel's datetime cast, values are written with their UTC offset
 * (2026-08-20 14:30:00.000000+02:00): timestamptz stores the right instant
 * whatever the session time zone, and timestamp ignores the offset. Values
 * read with an offset (timestamptz) are converted to the default time zone.
 */
final class DateTimeCaster extends AbstractCarbonCaster
{
    public function get(mixed $value): ?Carbon
    {
        return $value === null
            ? null
            : Carbon::parse((string) $value)->setTimezone(date_default_timezone_get());
    }

    protected function format(): string
    {
        return 'Y-m-d H:i:s.uP';
    }
}
