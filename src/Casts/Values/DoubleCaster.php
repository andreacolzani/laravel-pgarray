<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Casts\Values;

/**
 * @internal
 */
final class DoubleCaster implements PgArrayValueCaster
{
    use CastsFloats;
}
