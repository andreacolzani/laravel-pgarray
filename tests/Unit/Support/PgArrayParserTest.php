<?php

use AndreaColzani\PgArray\Support\PgArrayParser;

describe('parse', function () {
    it('parses an empty array', function () {
        expect(PgArrayParser::parse('{}'))
            ->toBe([]);
    });

    it('parses a simple array', function () {
        expect(PgArrayParser::parse('{foo,bar,baz}'))
            ->toBe(['foo', 'bar', 'baz']);
    });

    it('parses quoted values', function () {
        expect(PgArrayParser::parse('{"foo","bar baz","qux"}'))
            ->toBe(['foo', 'bar baz', 'qux']);
    });

    it('preserves null elements', function () {
        expect(PgArrayParser::parse('{foo,NULL,bar}'))
            ->toBe(['foo', null, 'bar']);
    });

    it('distinguishes null from the string NULL', function () {
        expect(PgArrayParser::parse('{NULL,"NULL"}'))
            ->toBe([null, 'NULL']);
    });

    it('parses empty string elements', function () {
        expect(PgArrayParser::parse('{,""}'))
            ->toBe(['', '']);
    });

    it('parses values containing commas', function () {
        expect(PgArrayParser::parse('{"foo,bar","baz"}'))
            ->toBe(['foo,bar', 'baz']);
    });

    it('parses escaped quotes', function () {
        expect(PgArrayParser::parse('{"foo\\"bar"}'))
            ->toBe(['foo"bar']);
    });

    it('parses escaped backslashes', function () {
        expect(PgArrayParser::parse('{"foo\\\\bar"}'))
            ->toBe(['foo\\bar']);
    });

    it('parses multidimensional arrays', function () {
        expect(PgArrayParser::parse('{{foo,bar},{baz,qux}}'))
            ->toBe([
                ['foo', 'bar'],
                ['baz', 'qux'],
            ]);
    });
});

describe('serialize', function () {
    it('serializes an empty array', function () {
        expect(PgArrayParser::serialize([]))
            ->toBe('{}');
    });

    it('serializes floats without losing precision', function () {
        expect(PgArrayParser::serialize([0.1 + 0.2, M_PI, 1.0, -0.0, 1.0e-300, 1.0e20, NAN, INF, -INF]))
            ->toBe('{0.30000000000000004,3.141592653589793,1.0,-0.0,1.0E-300,1.0E+20,NaN,Infinity,-Infinity}');
    });

    it('serializes a simple array', function () {
        expect(PgArrayParser::serialize(['foo', 'bar', 'baz']))
            ->toBe('{foo,bar,baz}');
    });

    it('serializes null elements', function () {
        expect(PgArrayParser::serialize(['foo', null, 'bar']))
            ->toBe('{foo,NULL,bar}');
    });

    it('quotes the string NULL preserving its case', function () {
        expect(PgArrayParser::serialize(['NULL', 'null', 'Null']))
            ->toBe('{"NULL","null","Null"}');
    });

    it('quotes values when required', function () {
        expect(PgArrayParser::serialize(['foo,bar', 'baz']))
            ->toBe('{"foo,bar",baz}');
    });

    it('escapes quotes and backslashes', function () {
        expect(PgArrayParser::serialize(['foo"bar', 'foo\\bar']))
            ->toBe('{"foo\\"bar","foo\\\\bar"}');
    });

    it('serializes multidimensional arrays', function () {
        expect(PgArrayParser::serialize([
            ['foo', 'bar'],
            ['baz', 'qux'],
        ]))
            ->toBe('{{foo,bar},{baz,qux}}');
    });
});

it('round trips parsed values', function (string $postgres, array $expected) {
    $parsed = PgArrayParser::parse($postgres);

    expect($parsed)
        ->toBe($expected)
        ->and(PgArrayParser::parse(
            PgArrayParser::serialize($parsed)
        ))->toBe($expected);
})->with([
    ['{}', []],
    ['{foo,bar}', ['foo', 'bar']],
    ['{NULL,"NULL"}', [null, 'NULL']],
    ['{NULL,"null"}', [null, 'null']],
    ['{"foo,bar","baz"}', ['foo,bar', 'baz']],
    ['{{foo,bar},{baz,qux}}', [
        ['foo', 'bar'],
        ['baz', 'qux'],
    ]],
]);
