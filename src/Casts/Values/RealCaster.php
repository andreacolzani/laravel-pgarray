<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

final class RealCaster implements PgArrayValueCaster
{
    use CastsFloats;
}
