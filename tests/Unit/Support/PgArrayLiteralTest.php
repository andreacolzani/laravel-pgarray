<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Support\PgArrayLiteral;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

it('builds PostgreSQL array literals', function (mixed $values, string $literal): void {
    expect(PgArrayLiteral::from($values))->toBe($literal);
})->with([
    'strings' => [['php', 'laravel'], '{php,laravel}'],
    'integers' => [[1, 2, 3], '{1,2,3}'],
    'floats' => [[1.5, 2.25], '{1.5,2.25}'],
    'booleans' => [[true, false], '{t,f}'],
    'null elements' => [['a', null], '{a,NULL}'],
    'empty' => [[], '{}'],
    'keyed' => [['x' => 'a', 'y' => 'b'], '{a,b}'],
    'collection' => [collect(['a', 'b']), '{a,b}'],
    'nested collections' => [collect([collect([1, 2]), [3, 4]]), '{{1,2},{3,4}}'],
    'multidimensional' => [[[1, 2], [3, 4]], '{{1,2},{3,4}}'],
    'backed enums' => [[Status::Active, Priority::High], '{active,3}'],
    'stringable' => [[Str::of('a b')], '{"a b"}'],
    'ramsey uuid' => [[Uuid::fromString('0b8e4f3a-5d3c-4d7e-9f1a-2b3c4d5e6f70')], '{0b8e4f3a-5d3c-4d7e-9f1a-2b3c4d5e6f70}'],
    'symfony uuid' => [[SymfonyUuid::fromString('0b8e4f3a-5d3c-4d7e-9f1a-2b3c4d5e6f70')], '{0b8e4f3a-5d3c-4d7e-9f1a-2b3c4d5e6f70}'],
    'special characters' => [['a,b', 'say "hi"', 'back\\slash', "it's", '{x}', ''], '{"a,b","say \\"hi\\"","back\\\\slash",it\'s,"{x}",""}'],
    'dates' => [[CarbonImmutable::parse('2026-08-20 14:30:00.5', 'Europe/Rome'), new DateTime('2026-08-20 14:30:00+05:00')], '{"2026-08-20 14:30:00.500000+02:00","2026-08-20 14:30:00.000000+05:00"}'],
    'scalar' => ['php', '{php}'],
    'scalar enum' => [Status::Inactive, '{inactive}'],
]);

it('rejects null values', function (): void {
    PgArrayLiteral::from(null);
})->throws(InvalidArgumentException::class, 'A PostgreSQL array value cannot be null.');

it('rejects unsupported elements', function (): void {
    PgArrayLiteral::from([new stdClass]);
})->throws(InvalidArgumentException::class, 'Unsupported PostgreSQL array element of type [stdClass].');

it('builds literals with the delimiter of the element type', function (): void {
    expect(PgArrayLiteral::from(['POINT(1 2)', 'POINT(3 4)'], ':'))->toBe('{"POINT(1 2)":"POINT(3 4)"}');
});
