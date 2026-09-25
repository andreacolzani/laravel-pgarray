<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

final class FloatCaster implements PgArrayValueCaster
{
    use CastsFloats;
}
