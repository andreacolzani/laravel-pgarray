<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray\Tests\Fixtures;

use AndreaColzani\PgArray\Concerns\InteractsWithPgArrayJson;
use AndreaColzani\PgArray\Contracts\PgArrayJsonValue;

/**
 * A JSON element using InteractsWithPgArrayJson but overriding
 * toPgArrayValue() to leave the token out of the stored data.
 */
final class Profile implements PgArrayJsonValue
{
    use InteractsWithPgArrayJson;

    public function __construct(
        public readonly string $username,
        public readonly ?string $token = null,
    ) {}

    /**
     * @return array{username: string}
     */
    public function toPgArrayValue(): array
    {
        return ['username' => $this->username];
    }
}
