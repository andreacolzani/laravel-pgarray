<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Casts\PgArray;
use AndreaColzani\PgArray\Enums\PgArrayCast;
use AndreaColzani\PgArray\Enums\PgArrayContainer;
use AndreaColzani\PgArray\Exceptions\UnsupportedElementException;
use AndreaColzani\PgArray\Tests\Fixtures\Address;
use AndreaColzani\PgArray\Tests\Fixtures\Cents;
use AndreaColzani\PgArray\Tests\Fixtures\Color;
use AndreaColzani\PgArray\Tests\Fixtures\Contact;
use AndreaColzani\PgArray\Tests\Fixtures\Email;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use AndreaColzani\PgArray\Tests\Models\TestModel;
use Illuminate\Support\Collection;

it('casts a postgres string array to a php string array', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::String,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'tags',
        '{"foo","bar"}',
        [],
    ))->toBe([
        'foo',
        'bar',
    ]);
});

it('casts a postgres integer array to a php integer array', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'numbers',
        '{1,2,3}',
        [],
    ))->toBe([1, 2, 3]);
});

it('casts a postgres boolean array to a php boolean array', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Boolean,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'flags',
        '{t,f,t}',
        [],
    ))->toBe([true, false, true]);
});

it('casts floating point postgres arrays to php float arrays', function (
    PgArrayCast $type,
): void {
    $cast = new PgArray(
        type: $type,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'values',
        '{1.25,2.5,3.75}',
        [],
    ))->toBe([1.25, 2.5, 3.75]);
})->with([
    PgArrayCast::Float,
    PgArrayCast::Double,
    PgArrayCast::Real,
]);

it('casts a postgres decimal array to a php string array', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Decimal,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'values',
        '{123456789.123456789,0.000000001}',
        [],
    ))->toBe([
        '123456789.123456789',
        '0.000000001',
    ]);
});

it('preserves multidimensional arrays', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'numbers',
        '{{1,2},{3,4}}',
        [],
    ))->toBe([
        [1, 2],
        [3, 4],
    ]);
});

it('returns a collection when using the collection container', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Collection,
    );

    $result = $cast->get(
        new TestModel,
        'numbers',
        '{1,2,3}',
        [],
    );

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect([1, 2, 3]));
});

it('serializes a php integer array to postgres', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(
        new TestModel,
        'numbers',
        [1, 2, 3],
        [],
    ))->toBe('{1,2,3}');
});

it('serializes a php decimal array without losing precision', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Decimal,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(
        new TestModel,
        'values',
        [
            '123456789.123456789',
            '0.000000001',
        ],
        [],
    ))->toBe('{123456789.123456789,0.000000001}');
});

it('serializes a multidimensional php array to postgres', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(
        new TestModel,
        'numbers',
        [[1, 2], [3, 4]],
        [],
    ))->toBe('{{1,2},{3,4}}');
});

it('serializes a collection to postgres', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Collection,
    );

    expect($cast->set(
        new TestModel,
        'numbers',
        collect([1, 2, 3]),
        [],
    ))->toBe('{1,2,3}');
});

it('returns null when the database value is null', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'numbers',
        null,
        [],
    ))->toBeNull();
});

it('returns null when the assigned value is null', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(
        new TestModel,
        'numbers',
        null,
        [],
    ))->toBeNull();
});

it('preserves null values', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'numbers',
        '{1,NULL,3}',
        [],
    ))->toBe([1, null, 3]);
});

it('serializes null values', function (): void {
    $cast = new PgArray(
        type: PgArrayCast::Integer,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(
        new TestModel,
        'numbers',
        [1, null, 3],
        [],
    ))->toBe('{1,NULL,3}');
});

it('fails explicitly when the element type cannot be resolved', function (): void {
    new PgArray(
        type: stdClass::class,
        container: PgArrayContainer::Array,
    );
})->throws(UnsupportedElementException::class);

it('casts a postgres array to backed enum cases', function (): void {
    $cast = new PgArray(
        type: Status::class,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'statuses',
        '{active,inactive}',
        [],
    ))->toBe([Status::Active, Status::Inactive]);
});

it('serializes backed enum cases to postgres', function (): void {
    $cast = new PgArray(
        type: Status::class,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(
        new TestModel,
        'statuses',
        [Status::Active, Status::Inactive],
        [],
    ))->toBe('{active,inactive}');
});

it('round-trips int-backed enum cases', function (): void {
    $cast = new PgArray(
        type: Priority::class,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(new TestModel, 'priorities', '{1,3}', []))
        ->toBe([Priority::Low, Priority::High])
        ->and($cast->set(new TestModel, 'priorities', [Priority::Low, Priority::High], []))
        ->toBe('{1,3}');
});

it('preserves multidimensional backed enum arrays', function (): void {
    $cast = new PgArray(
        type: Status::class,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(
        new TestModel,
        'statuses',
        '{{active,inactive},{inactive,NULL}}',
        [],
    ))->toBe([
        [Status::Active, Status::Inactive],
        [Status::Inactive, null],
    ])->and($cast->set(
        new TestModel,
        'statuses',
        [[Status::Active, Status::Inactive], [Status::Inactive, null]],
        [],
    ))->toBe('{{active,inactive},{inactive,NULL}}');
});

it('returns a collection of backed enum cases', function (): void {
    $cast = new PgArray(
        type: Status::class,
        container: PgArrayContainer::Collection,
    );

    $result = $cast->get(new TestModel, 'statuses', '{active,inactive}', []);

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->and($result->all())
        ->toBe([Status::Active, Status::Inactive])
        ->and($cast->set(new TestModel, 'statuses', collect([Status::Inactive]), []))
        ->toBe('{inactive}');
});

it('fails explicitly on invalid backed enum values', function (): void {
    $cast = new PgArray(
        type: Status::class,
        container: PgArrayContainer::Array,
    );

    $cast->get(new TestModel, 'statuses', '{active,archived}', []);
})->throws(UnexpectedValueException::class);

it('casts a postgres array to PgArrayValue objects', function (): void {
    $cast = new PgArray(
        type: Email::class,
        container: PgArrayContainer::Array,
    );

    $result = $cast->get(new TestModel, 'emails', '{john@example.com,NULL,jane@example.com}', []);

    expect($result)->toHaveCount(3)
        ->and($result[0])->toEqual(new Email('john@example.com'))
        ->and($result[1])->toBeNull()
        ->and($result[2])->toEqual(new Email('jane@example.com'));
});

it('serializes PgArrayValue objects to postgres', function (): void {
    $cast = new PgArray(
        type: Email::class,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(
        new TestModel,
        'emails',
        [new Email('john@example.com'), null, 'Jane@Example.com'],
        [],
    ))->toBe('{john@example.com,NULL,jane@example.com}');
});

it('round-trips multidimensional PgArrayValue arrays', function (): void {
    $cast = new PgArray(
        type: Cents::class,
        container: PgArrayContainer::Array,
    );

    $values = [
        [new Cents(100), new Cents(250)],
        [new Cents(0), new Cents(-50)],
    ];

    expect($cast->set(new TestModel, 'amounts', $values, []))
        ->toBe('{{100,250},{0,-50}}')
        ->and($cast->get(new TestModel, 'amounts', '{{100,250},{0,-50}}', []))
        ->toEqual($values);
});

it('returns a collection of PgArrayValue objects', function (): void {
    $cast = new PgArray(
        type: Email::class,
        container: PgArrayContainer::Collection,
    );

    $result = $cast->get(new TestModel, 'emails', '{john@example.com}', []);

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect([new Email('john@example.com')]))
        ->and($cast->set(new TestModel, 'emails', collect([new Email('jane@example.com')]), []))
        ->toBe('{jane@example.com}');
});

it('escapes logical values of PgArrayValue objects', function (): void {
    $cast = new PgArray(
        type: Email::class,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(new TestModel, 'emails', [new Email('"john,doe"@example.com')], []))
        ->toBe('{"\"john,doe\"@example.com"}')
        ->and($cast->get(new TestModel, 'emails', '{"\"john,doe\"@example.com"}', []))
        ->toEqual([new Email('"john,doe"@example.com')]);
});

it('prefers the PgArrayValue contract over automatic backed enum support', function (): void {
    $cast = new PgArray(
        type: Color::class,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(new TestModel, 'colors', [Color::Red, Color::Green], []))
        ->toBe('{RED,GREEN}')
        ->and($cast->get(new TestModel, 'colors', '{RED,GREEN}', []))
        ->toBe([Color::Red, Color::Green]);
});

it('casts a postgres json array to PgArrayJsonValue objects', function (): void {
    $cast = new PgArray(
        type: Address::class,
        container: PgArrayContainer::Array,
    );

    $result = $cast->get(
        new TestModel,
        'addresses',
        '{"{\"city\": \"Milano\", \"street\": \"Via Roma 1\"}",NULL,"{\"city\": \"Roma\", \"street\": \"Via Po 2\"}"}',
        [],
    );

    expect($result)->toEqual([
        new Address('Via Roma 1', 'Milano'),
        null,
        new Address('Via Po 2', 'Roma'),
    ]);
});

it('serializes PgArrayJsonValue objects to a postgres json array', function (): void {
    $cast = new PgArray(
        type: Address::class,
        container: PgArrayContainer::Array,
    );

    expect($cast->set(
        new TestModel,
        'addresses',
        [new Address('Via Roma 1', 'Milano'), null],
        [],
    ))->toBe('{"{\"street\":\"Via Roma 1\",\"city\":\"Milano\"}",NULL}');
});

it('round-trips multidimensional PgArrayJsonValue arrays', function (): void {
    $cast = new PgArray(
        type: Address::class,
        container: PgArrayContainer::Array,
    );

    $values = [
        [new Address('Via Roma 1', 'Milano'), new Address('Via Po 2', 'Roma')],
        [null, new Address('Via Dante 3', 'Torino')],
    ];

    $serialized = $cast->set(new TestModel, 'addresses', $values, []);

    expect($serialized)
        ->toStartWith('{{"{\"street\":\"Via Roma 1\"')
        ->and($cast->get(new TestModel, 'addresses', $serialized, []))
        ->toEqual($values);
});

it('returns a collection of PgArrayJsonValue objects', function (): void {
    $cast = new PgArray(
        type: Address::class,
        container: PgArrayContainer::Collection,
    );

    $values = collect([new Address('Via Roma 1', 'Milano')]);

    expect($cast->get(new TestModel, 'addresses', $cast->set(new TestModel, 'addresses', $values, []), []))
        ->toBeInstanceOf(Collection::class)
        ->toEqual($values);
});

it('round-trips JSON edge cases', function (Address $address): void {
    $cast = new PgArray(
        type: Address::class,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(new TestModel, 'addresses', $cast->set(new TestModel, 'addresses', [$address], []), []))
        ->toEqual([$address]);
})->with([
    'quotes' => [new Address('Via "Roma"', 'Milano')],
    'backslashes' => [new Address('C:\\Via\\Roma', 'Milano\\')],
    'array delimiters' => [new Address('{Via, Roma}', 'Milano,Roma')],
    'empty strings' => [new Address('', '')],
    'unicode' => [new Address('Straße', 'Forlì 🍕')],
    'null literal' => [new Address('null', 'NULL')],
    'whitespace' => [new Address("Via\tRoma\n1", ' Milano ')],
]);

it('preserves nested structures of PgArrayJsonValue objects', function (): void {
    $cast = new PgArray(
        type: Contact::class,
        container: PgArrayContainer::Array,
    );

    $values = [
        new Contact('John', ['+39 02 1234'], Priority::High, new Address('Via Roma 1', 'Milano')),
        new Contact('Jane'),
    ];

    expect($cast->get(new TestModel, 'contacts', $cast->set(new TestModel, 'contacts', $values, []), []))
        ->toEqual($values);
});

it('treats JSON null elements as null', function (): void {
    $cast = new PgArray(
        type: Address::class,
        container: PgArrayContainer::Array,
    );

    expect($cast->get(new TestModel, 'addresses', '{null,NULL}', []))
        ->toBe([null, null]);
});

it('fails explicitly on invalid JSON elements', function (): void {
    $cast = new PgArray(
        type: Address::class,
        container: PgArrayContainer::Array,
    );

    $cast->get(new TestModel, 'addresses', '{"{\"street\":"}', []);
})->throws(JsonException::class);
