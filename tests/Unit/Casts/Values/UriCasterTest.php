<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\UriCaster;
use Illuminate\Support\Uri;

it('casts values to Uri instances', function (mixed $value, ?string $expected): void {
    $result = (new UriCaster)->get($value);

    if ($expected === null) {
        expect($result)->toBeNull();

        return;
    }

    expect($result)
        ->toBeInstanceOf(Uri::class)
        ->and((string) $result)
        ->toBe($expected);
})->with([
    ['https://example.com', 'https://example.com'],
    [new Uri('https://example.com'), 'https://example.com'],
    [null, null],
]);

it('serializes Uri values to strings', function (): void {
    $caster = new UriCaster;
    $uri = new Uri('https://example.com');

    expect($caster->set($uri))
        ->toBe('https://example.com')
        ->and($caster->set('https://example.com'))
        ->toBe('https://example.com')
        ->and($caster->set(null))
        ->toBeNull();
});
