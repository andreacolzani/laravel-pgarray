<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use Carbon\CarbonImmutable;

/**
 * Immutable variant of DateTimeCaster.
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
