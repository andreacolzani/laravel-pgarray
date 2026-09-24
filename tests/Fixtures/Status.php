<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
