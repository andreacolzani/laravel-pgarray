<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\ByteaCaster;
use AndreaColzani\PgArray\Support\PgArrayParser;
use Illuminate\Support\Stringable;

it('encodes binary strings in the hex format', function (): void {
    expect((new ByteaCaster)->set("\x00\x01\xfe\xff"))->toBe('\x0001feff');
});

it('decodes the hex format', function (): void {
    expect((new ByteaCaster)->get('\x0001FEff'))->toBe("\x00\x01\xfe\xff");
});

it('round trips binary data', function (string $binary): void {
    $caster = new ByteaCaster;

    expect($caster->get($caster->set($binary)))->toBe($binary);
})->with([
    'nul bytes' => ["\x00\x00\x00"],
    'high bytes' => ["\x80\x9f\xff"],
    'all bytes' => [implode('', array_map('chr', range(0, 255)))],
    'backslashes and quotes' => ['\\"{},'],
    'utf-8 text' => ['città'],
    'empty string' => [''],
    'random' => [random_bytes(64)],
]);

it('round trips binary data through the array literal', function (): void {
    $caster = new ByteaCaster;
    $binary = ["\x00\\\"", random_bytes(32)];

    $literal = PgArrayParser::serialize(array_map($caster->set(...), $binary));

    expect($literal)->toStartWith('{"\\\\x')
        ->and(array_map($caster->get(...), PgArrayParser::parse($literal)))->toBe($binary);
});

it('decodes the legacy escape format', function (string $escaped, string $binary): void {
    expect((new ByteaCaster)->get($escaped))->toBe($binary);
})->with([
    'printable' => ['abc', 'abc'],
    'octal' => ['\000\001\377', "\x00\x01\xff"],
    'backslash' => ['a\\\\b', 'a\\b'],
    'mixed' => ['x\012y\\\\', "x\ny\\"],
]);

it('accepts stringable values', function (): void {
    expect((new ByteaCaster)->set(new Stringable('ab')))->toBe('\x6162');
});

it('preserves null', function (): void {
    $caster = new ByteaCaster;

    expect($caster->get(null))->toBeNull()
        ->and($caster->set(null))->toBeNull();
});

it('rejects invalid database values', function (string $value): void {
    (new ByteaCaster)->get($value);
})->with([
    'odd hex length' => ['\x012'],
    'non hex digits' => ['\xzz'],
    'invalid escape' => ['a\9b'],
])->throws(UnexpectedValueException::class, 'Invalid bytea');

it('rejects values that are not strings', function (mixed $value): void {
    (new ByteaCaster)->set($value);
})->with([
    'integer' => [1],
    'boolean' => [true],
    'array' => [[1]],
    'object' => [new stdClass],
])->throws(UnexpectedValueException::class, 'to bytea.');
