<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\ObjectCaster;
use AndreaColzani\PgArray\Tests\Fixtures\Address;
use AndreaColzani\PgArray\Tests\Fixtures\Cents;
use AndreaColzani\PgArray\Tests\Fixtures\Email;

it('creates objects from logical values', function (): void {
    $email = (new ObjectCaster(Email::class))->get('john@example.com');

    expect($email)
        ->toBeInstanceOf(Email::class)
        ->and($email->address)
        ->toBe('john@example.com');
});

it('returns instances unchanged when retrieving', function (): void {
    $email = new Email('john@example.com');

    expect((new ObjectCaster(Email::class))->get($email))->toBe($email);
});

it('serializes objects to their logical values', function (): void {
    expect((new ObjectCaster(Email::class))->set(new Email('john@example.com')))
        ->toBe('john@example.com')
        ->and((new ObjectCaster(Cents::class))->set(new Cents(1250)))
        ->toBe(1250);
});

it('normalizes raw values through the class when serializing', function (): void {
    expect((new ObjectCaster(Email::class))->set('John@Example.com'))
        ->toBe('john@example.com')
        ->and((new ObjectCaster(Cents::class))->set('1250'))
        ->toBe(1250);
});

it('lets the class reject invalid raw values', function (): void {
    (new ObjectCaster(Email::class))->set('not-an-email');
})->throws(InvalidArgumentException::class, 'Invalid email address [not-an-email].');

it('lets the class reject invalid database values', function (): void {
    (new ObjectCaster(Email::class))->get('not-an-email');
})->throws(InvalidArgumentException::class, 'Invalid email address [not-an-email].');

it('preserves null', function (): void {
    $caster = new ObjectCaster(Email::class);

    expect($caster->get(null))->toBeNull()
        ->and($caster->set(null))->toBeNull();
});

it('rejects objects of a different class when serializing', function (): void {
    (new ObjectCaster(Email::class))->set(new Cents(100));
})->throws(
    UnexpectedValueException::class,
    'Unable to cast ['.Cents::class.'] to ['.Email::class.'].',
);

it('rejects non-scalar logical values', function (): void {
    (new ObjectCaster(Address::class))->set(new Address('Via Roma 1', 'Milano'));
})->throws(
    UnexpectedValueException::class,
    '['.Address::class.']::toPgArrayValue() must return a scalar value or null, [array] given.',
);
