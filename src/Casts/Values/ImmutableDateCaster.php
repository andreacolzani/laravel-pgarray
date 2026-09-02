<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

use Carbon\CarbonImmutable;

final class ImmutableDateCaster extends AbstractCarbonCaster
{
    public function get(mixed $value): ?CarbonImmutable
    {
        return $value === null
            ? null
            : CarbonImmutable::parse((string) $value);
    }

    protected function format(): string
    {
        return 'Y-m-d';
    }
}
