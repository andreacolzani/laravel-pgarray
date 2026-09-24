<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

use AndreaColzani\PgArray\Concerns\InteractsWithPgArrayJson;
use AndreaColzani\PgArray\Contracts\PgArrayJsonValue;

/**
 * A JSON element relying on the default InteractsWithPgArrayJson
 * serialization, with a nested list, a backed enum and a nested
 * PgArrayJsonValue.
 */
final class Contact implements PgArrayJsonValue
{
    use InteractsWithPgArrayJson;

    /**
     * @param  list<string>  $phones
     */
    public function __construct(
        public readonly string $name,
        public readonly array $phones = [],
        public readonly ?Priority $priority = null,
        public readonly ?Address $address = null,
    ) {}
}
