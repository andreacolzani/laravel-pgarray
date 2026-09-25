<?php

use AndreaColzani\PgArray\Exceptions\PgArrayException;

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('it uses strict types')
    ->expect('AndreaColzani\PgArray')
    ->toUseStrictTypes();

arch('exceptions implement the package marker interface')
    ->expect('AndreaColzani\PgArray\Exceptions')
    ->classes()
    ->toImplement(PgArrayException::class)
    ->toBeFinal()
    ->ignoring(PgArrayException::class);

arch('it throws package exceptions')
    ->expect('AndreaColzani\PgArray')
    ->not->toUse([InvalidArgumentException::class, UnexpectedValueException::class, RuntimeException::class, ValueError::class])
    ->ignoring('AndreaColzani\PgArray\Exceptions');
