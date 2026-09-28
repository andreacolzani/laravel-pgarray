<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use Carbon\Carbon;

/**
 * Unlike Laravel's datetime cast, values are written with their UTC offset, so
 * timestamptz stores the right instant whatever the session time zone (timestamp
 * ignores it). Values read with an offset are converted to the default time zone.
 *
 * @internal
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
