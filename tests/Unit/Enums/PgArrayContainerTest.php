<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Enums\PgArrayContainer;

it('defines the supported containers', function (): void {
    expect(PgArrayContainer::cases())
        ->toHaveCount(2)
        ->toContain(PgArrayContainer::Array)
        ->toContain(PgArrayContainer::Collection);
});
