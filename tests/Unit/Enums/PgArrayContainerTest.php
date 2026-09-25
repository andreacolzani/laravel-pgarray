<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Enums\PgArrayContainer;
use AndreaColzani\PgArray\Exceptions\InvalidDefinitionException;

it('defines the supported containers', function (): void {
    expect(PgArrayContainer::cases())
        ->toHaveCount(2)
        ->toContain(PgArrayContainer::Array)
        ->toContain(PgArrayContainer::Collection);
});

it('resolves the container of a cast argument', function (?string $argument, PgArrayContainer $expected): void {
    expect(PgArrayContainer::fromCastArgument($argument))->toBe($expected);
})->with([
    'default' => [null, PgArrayContainer::Array],
    'array' => ['array', PgArrayContainer::Array],
    'collection' => ['collection', PgArrayContainer::Collection],
]);

it('rejects an unsupported container argument', function (): void {
    PgArrayContainer::fromCastArgument('set');
})->throws(InvalidDefinitionException::class, 'Unsupported array container [set]. Expected one of [array, collection].');
