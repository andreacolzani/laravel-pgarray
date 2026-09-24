<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Tests\Fixtures\Address;
use AndreaColzani\PgArray\Tests\Fixtures\Contact;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;
use AndreaColzani\PgArray\Tests\Fixtures\Profile;

it('serializes the object properties by name', function (): void {
    expect((new Contact('John', ['+39 02 1234'], Priority::Low))->toPgArrayValue())
        ->toBe([
            'name' => 'John',
            'phones' => ['+39 02 1234'],
            'priority' => Priority::Low,
            'address' => null,
        ]);
});

it('serializes nested PgArrayValue objects through their contract', function (): void {
    expect((new Contact('John', address: new Address('Via Roma 1', 'Milano')))->toPgArrayValue())
        ->toMatchArray([
            'address' => ['street' => 'Via Roma 1', 'city' => 'Milano'],
        ]);
});

it('creates the object from constructor named arguments', function (): void {
    expect(Contact::fromPgArrayValue([
        'phones' => ['+39 02 1234'],
        'name' => 'John',
    ]))->toEqual(new Contact('John', ['+39 02 1234']));
});

it('restores nested PgArrayValue objects and backed enums', function (): void {
    expect(Contact::fromPgArrayValue([
        'name' => 'John',
        'priority' => 2,
        'address' => ['street' => 'Via Roma 1', 'city' => 'Milano'],
    ]))->toEqual(new Contact(
        name: 'John',
        priority: Priority::Medium,
        address: new Address('Via Roma 1', 'Milano'),
    ));
});

it('keeps null nested values', function (): void {
    expect(Contact::fromPgArrayValue(['name' => 'John', 'priority' => null, 'address' => null]))
        ->toEqual(new Contact('John'));
});

it('ignores keys that do not match a constructor parameter', function (): void {
    expect(Contact::fromPgArrayValue(['name' => 'John', 'email' => 'john@example.com']))
        ->toEqual(new Contact('John'));
});

it('fails when a required constructor argument is missing', function (): void {
    Contact::fromPgArrayValue(['phones' => []]);
})->throws(ArgumentCountError::class);

it('rejects values that are not JSON objects', function (): void {
    Contact::fromPgArrayValue('John');
})->throws(
    UnexpectedValueException::class,
    'Unable to create ['.Contact::class.'] from [string]: expected a JSON object.',
);

it('can be partially overridden by the class', function (): void {
    $profile = new Profile('john', 'secret');

    expect($profile->toPgArrayValue())
        ->toBe(['username' => 'john'])
        ->and(Profile::fromPgArrayValue($profile->toPgArrayValue()))
        ->toEqual(new Profile('john'));
});
