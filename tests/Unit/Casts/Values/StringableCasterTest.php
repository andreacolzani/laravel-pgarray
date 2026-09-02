<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\StringableCaster;
use Illuminate\Support\Stringable;

it('casts values to Laravel Stringable instances', function (mixed $value, ?string $expected): void {
    $result = (new StringableCaster)->get($value);

    if ($expected === null) {
        expect($result)->toBeNull();

        return;
    }

    expect($result)
        ->toBeInstanceOf(Stringable::class)
        ->and((string) $result)
        ->toBe($expected);
})->with([
    ['foo', 'foo'],
    [123, '123'],
    [true, '1'],
    [null, null],
]);

it('serializes stringable values to strings', function (): void {
    $caster = new StringableCaster;

    expect($caster->set(new Stringable('foo')))
        ->toBe('foo')
        ->and($caster->set('bar'))
        ->toBe('bar')
        ->and($caster->set(null))
        ->toBeNull();
});
