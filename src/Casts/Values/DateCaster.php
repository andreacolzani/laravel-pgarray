<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use Carbon\Carbon;

final class DateCaster extends AbstractCarbonCaster
{
    public function get(mixed $value): ?Carbon
    {
        return $value === null
            ? null
            : Carbon::parse((string) $value);
    }

    protected function format(): string
    {
        return 'Y-m-d';
    }
}
