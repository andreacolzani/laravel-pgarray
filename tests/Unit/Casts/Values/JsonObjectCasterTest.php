<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\Values\JsonObjectCaster;
use AndreaColzani\PgArray\Tests\Fixtures\Address;
use AndreaColzani\PgArray\Tests\Fixtures\Contact;
use AndreaColzani\PgArray\Tests\Fixtures\Email;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;

it('creates objects from JSON values', function (): void {
    expect((new JsonObjectCaster(Address::class))->get('{"street":"Via Roma 1","city":"Milano"}'))
        ->toEqual(new Address('Via Roma 1', 'Milano'));
});

it('accepts JSON as formatted by jsonb', function (): void {
    expect((new JsonObjectCaster(Address::class))->get('{"city": "Milano", "street": "Via Roma 1"}'))
        ->toEqual(new Address('Via Roma 1', 'Milano'));
});

it('returns instances unchanged when retrieving', function (): void {
    $address = new Address('Via Roma 1', 'Milano');

    expect((new JsonObjectCaster(Address::class))->get($address))->toBe($address);
});

it('serializes objects to JSON', function (): void {
    expect((new JsonObjectCaster(Address::class))->set(new Address('Via Roma 1', 'Milano')))
        ->toBe('{"street":"Via Roma 1","city":"Milano"}');
});

it('preserves nested structures', function (): void {
    $caster = new JsonObjectCaster(Contact::class);

    $contact = new Contact(
        name: 'John',
        phones: ['+39 02 1234', '+39 333 5678'],
        priority: Priority::High,
        address: new Address('Via Roma 1', 'Milano'),
    );

    $json = $caster->set($contact);

    expect($json)
        ->toBe('{"name":"John","phones":["+39 02 1234","+39 333 5678"],"priority":3,"address":{"street":"Via Roma 1","city":"Milano"}}')
        ->and($caster->get($json))
        ->toEqual($contact);
});

it('does not escape slashes or unicode characters', function (): void {
    expect((new JsonObjectCaster(Address::class))->set(new Address('Corso d/Italia', 'Forlì')))
        ->toBe('{"street":"Corso d/Italia","city":"Forlì"}');
});

it('normalizes raw JSON strings through the class when serializing', function (): void {
    expect((new JsonObjectCaster(Address::class))->set('{"city": "Milano", "street": "Via Roma 1"}'))
        ->toBe('{"street":"Via Roma 1","city":"Milano"}');
});

it('preserves null', function (): void {
    $caster = new JsonObjectCaster(Address::class);

    expect($caster->get(null))->toBeNull()
        ->and($caster->set(null))->toBeNull();
});

it('treats JSON null elements as null', function (): void {
    $caster = new JsonObjectCaster(Address::class);

    expect($caster->get('null'))->toBeNull()
        ->and($caster->set('null'))->toBeNull();
});

it('fails explicitly on invalid JSON', function (): void {
    (new JsonObjectCaster(Address::class))->get('{"street":');
})->throws(JsonException::class);

it('rejects non-string raw values', function (): void {
    (new JsonObjectCaster(Address::class))->set(123);
})->throws(
    UnexpectedValueException::class,
    'Unable to cast [int] to ['.Address::class.']: expected a JSON string.',
);

it('rejects objects of a different class when serializing', function (): void {
    (new JsonObjectCaster(Address::class))->set(new Email('john@example.com'));
})->throws(
    UnexpectedValueException::class,
    'Unable to cast ['.Email::class.'] to ['.Address::class.'].',
);

it('fails explicitly on values that cannot be encoded', function (): void {
    (new JsonObjectCaster(Contact::class))->set(new Contact("\xB1\x31"));
})->throws(JsonException::class);
