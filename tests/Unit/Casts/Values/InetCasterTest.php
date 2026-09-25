<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\InetCaster;
use Illuminate\Support\Stringable;

it('normalizes inet values', function (string $value, string $expected): void {
    expect((new InetCaster)->set($value))->toBe($expected);
})->with([
    'ipv4' => ['192.168.1.5', '192.168.1.5'],
    'ipv4 with prefix' => ['192.168.1.5/24', '192.168.1.5/24'],
    'ipv4 full prefix' => ['10.0.0.1/32', '10.0.0.1'],
    'ipv4 zero prefix' => ['0.0.0.0/0', '0.0.0.0/0'],
    'ipv4 padded prefix' => ['10.0.0.0/08', '10.0.0.0/8'],
    'ipv4 surrounding spaces' => [' 10.0.0.1 ', '10.0.0.1'],
    'ipv6 expanded' => ['2001:0DB8:0000:0000:0000:0000:0000:0001', '2001:db8::1'],
    'ipv6 compressed' => ['2001:db8::1', '2001:db8::1'],
    'ipv6 with prefix' => ['2001:db8::/64', '2001:db8::/64'],
    'ipv6 full prefix' => ['::1/128', '::1'],
    'ipv6 loopback' => ['::1', '::1'],
    'ipv4 mapped ipv6' => ['::FFFF:192.168.1.5', '::ffff:192.168.1.5'],
]);

it('accepts stringable values', function (): void {
    expect((new InetCaster)->set(new Stringable('10.0.0.1')))->toBe('10.0.0.1');
});

it('retrieves inet values as strings', function (): void {
    expect((new InetCaster)->get('2001:db8::/64'))->toBe('2001:db8::/64');
});

it('preserves null', function (): void {
    $caster = new InetCaster;

    expect($caster->get(null))->toBeNull()
        ->and($caster->set(null))->toBeNull();
});

it('rejects invalid inet values', function (string $value): void {
    (new InetCaster)->set($value);
})->with([
    'out of range octet' => ['999.1.1.1'],
    'ipv4 prefix too long' => ['10.0.0.1/33'],
    'ipv6 prefix too long' => ['::1/129'],
    'negative prefix' => ['10.0.0.1/-1'],
    'empty prefix' => ['10.0.0.1/'],
    'non numeric prefix' => ['10.0.0.1/a'],
    'hostname' => ['localhost'],
    'empty' => [''],
])->throws(UnexpectedValueException::class, 'Invalid inet value');

it('rejects values that are not strings', function (mixed $value): void {
    (new InetCaster)->set($value);
})->with([
    'integer' => [1],
    'array' => [[1]],
    'object' => [new stdClass],
])->throws(UnexpectedValueException::class, 'to inet.');
