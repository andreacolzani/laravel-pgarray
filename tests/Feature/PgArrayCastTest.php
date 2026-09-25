<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Support\PgArrayHash;
use AndreaColzani\PgArray\Support\PgArrayParser;
use AndreaColzani\PgArray\Tests\Fixtures\Address;
use AndreaColzani\PgArray\Tests\Fixtures\Contact;
use AndreaColzani\PgArray\Tests\Fixtures\CountryCode;
use AndreaColzani\PgArray\Tests\Fixtures\Email;
use AndreaColzani\PgArray\Tests\Fixtures\Money;
use AndreaColzani\PgArray\Tests\Fixtures\Priority;
use AndreaColzani\PgArray\Tests\Fixtures\Sku;
use AndreaColzani\PgArray\Tests\Fixtures\Status;
use AndreaColzani\PgArray\Tests\Models\TestModel;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Stringable;

it('casts an attribute when retrieving it from an eloquent model', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'numbers' => '{1,2,3}',
    ]);

    expect($model->numbers)
        ->toBe([1, 2, 3]);
});

it('casts an attribute when setting it on an eloquent model', function (): void {
    $model = new TestModel;

    $model->numbers = [1, 2, 3];

    expect($model->getAttributes()['numbers'])
        ->toBe('{1,2,3}');
});

it('supports a collection container when retrieving an attribute', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'number_collection' => '{1,2,3}',
    ]);

    expect($model->number_collection)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect([1, 2, 3]));
});

it('accepts a collection when setting an attribute', function (): void {
    $model = new TestModel;

    $model->number_collection = collect([1, 2, 3]);

    expect($model->getAttributes()['number_collection'])
        ->toBe('{1,2,3}');
});

it('supports a concrete array castable', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'integer_array' => '{1,2,3}',
    ]);

    expect($model->integer_array)
        ->toBe([1, 2, 3]);
});

it('supports a concrete collection castable', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'integer_collection' => '{1,2,3}',
    ]);

    expect($model->integer_collection)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect([1, 2, 3]));
});

it('casts an attribute when mass assigning it', function (): void {
    $model = new TestModel;

    $model->fill([
        'numbers' => [1, 2, 3],
    ]);

    expect($model->getAttributes()['numbers'])
        ->toBe('{1,2,3}');
});

it('supports temporal concrete castables when retrieving attributes', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'date_array' => '{2026-08-20,2026-08-21}',
        'datetime_array' => '{"2026-08-20 14:30:00.123456"}',
        'immutable_date_array' => '{2026-08-20}',
        'immutable_datetime_array' => '{"2026-08-20 14:30:00.123456"}',
    ]);

    expect($model->date_array[0])
        ->toBeInstanceOf(Carbon::class)
        ->and($model->date_array[0]->toDateString())
        ->toBe('2026-08-20')
        ->and($model->datetime_array[0])
        ->toBeInstanceOf(Carbon::class)
        ->and($model->datetime_array[0]->format('Y-m-d H:i:s.u'))
        ->toBe('2026-08-20 14:30:00.123456')
        ->and($model->immutable_date_array[0])
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($model->immutable_date_array[0]->toDateString())
        ->toBe('2026-08-20')
        ->and($model->immutable_datetime_array[0])
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($model->immutable_datetime_array[0]->format('Y-m-d H:i:s.u'))
        ->toBe('2026-08-20 14:30:00.123456');
});

it('serializes temporal concrete castables when setting attributes', function (): void {
    $model = new TestModel;

    $model->date_array = [
        Carbon::parse('2026-08-20 14:30:00'),
    ];
    $model->datetime_array = [
        new DateTimeImmutable('2026-08-20 14:30:00.123456'),
    ];
    $model->immutable_date_array = [
        CarbonImmutable::parse('2026-08-20 14:30:00'),
    ];
    $model->immutable_datetime_array = [
        CarbonImmutable::parse('2026-08-20 14:30:00.123456'),
    ];

    expect($model->getAttributes()['date_array'])
        ->toBe('{2026-08-20}')
        ->and($model->getAttributes()['datetime_array'])
        ->toBe('{"2026-08-20 14:30:00.123456"}')
        ->and($model->getAttributes()['immutable_date_array'])
        ->toBe('{2026-08-20}')
        ->and($model->getAttributes()['immutable_datetime_array'])
        ->toBe('{"2026-08-20 14:30:00.123456"}');
});

it('supports a temporal concrete collection castable', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'datetime_collection' => '{"2026-08-20 14:30:00.123456"}',
    ]);

    expect($model->datetime_collection)
        ->toBeInstanceOf(Collection::class)
        ->and($model->datetime_collection->first())
        ->toBeInstanceOf(Carbon::class)
        ->and($model->datetime_collection->first()->format('Y-m-d H:i:s.u'))
        ->toBe('2026-08-20 14:30:00.123456');
});

it('supports numeric concrete castables when retrieving attributes', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'decimal_array' => '{123456789.123456789,0.000000001}',
        'double_array' => '{1.25,2.5}',
        'float_array' => '{1.25,2.5}',
        'real_array' => '{1.25,2.5}',
    ]);

    expect($model->decimal_array)
        ->toBe(['123456789.123456789', '0.000000001'])
        ->and($model->double_array)
        ->toBe([1.25, 2.5])
        ->and($model->float_array)
        ->toBe([1.25, 2.5])
        ->and($model->real_array)
        ->toBe([1.25, 2.5]);
});

it('serializes numeric concrete castables when setting attributes', function (): void {
    $model = new TestModel;

    $model->decimal_array = [
        '123456789.123456789',
        '0.000000001',
    ];
    $model->double_array = [1.25, 2.5];
    $model->float_array = [1.25, 2.5];
    $model->real_array = [1.25, 2.5];

    expect($model->getAttributes()['decimal_array'])
        ->toBe('{123456789.123456789,0.000000001}')
        ->and($model->getAttributes()['double_array'])
        ->toBe('{1.25,2.5}')
        ->and($model->getAttributes()['float_array'])
        ->toBe('{1.25,2.5}')
        ->and($model->getAttributes()['real_array'])
        ->toBe('{1.25,2.5}');
});

it('supports a numeric concrete collection castable', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'decimal_collection' => '{123456789.123456789,0.000000001}',
    ]);

    expect($model->decimal_collection)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect([
            '123456789.123456789',
            '0.000000001',
        ]));
});

it('supports a stringable concrete castable', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'stringable_array' => '{foo,bar}',
    ]);

    expect($model->stringable_array[0])
        ->toBeInstanceOf(Stringable::class)
        ->and((string) $model->stringable_array[0])
        ->toBe('foo')
        ->and($model->stringable_array[1])
        ->toBeInstanceOf(Stringable::class)
        ->and((string) $model->stringable_array[1])
        ->toBe('bar');
});

it('serializes a stringable concrete castable', function (): void {
    $model = new TestModel;

    $model->stringable_array = [
        new Stringable('foo'),
        'bar baz',
    ];

    expect($model->getAttributes()['stringable_array'])
        ->toBe('{foo,"bar baz"}');
});

it('supports a stringable concrete collection castable', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'stringable_collection' => '{foo,bar}',
    ]);

    expect($model->stringable_collection)
        ->toBeInstanceOf(Collection::class)
        ->and($model->stringable_collection->first())
        ->toBeInstanceOf(Stringable::class)
        ->and((string) $model->stringable_collection->first())
        ->toBe('foo');
});

it('supports backed enum element types when retrieving attributes', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'statuses' => '{active,inactive}',
        'priorities' => '{3,1}',
    ]);

    expect($model->statuses)
        ->toBe([Status::Active, Status::Inactive])
        ->and($model->priorities)
        ->toBe([Priority::High, Priority::Low]);
});

it('serializes backed enum element types when setting attributes', function (): void {
    $model = new TestModel;

    $model->statuses = [Status::Active, Status::Inactive];
    $model->priorities = [Priority::High, Priority::Low];

    expect($model->getAttributes()['statuses'])
        ->toBe('{active,inactive}')
        ->and($model->getAttributes()['priorities'])
        ->toBe('{3,1}');
});

it('supports a backed enum collection', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'status_collection' => '{inactive,active}',
    ]);

    expect($model->status_collection)
        ->toBeInstanceOf(Collection::class)
        ->and($model->status_collection->all())
        ->toBe([Status::Inactive, Status::Active]);

    $model->status_collection = collect([Status::Active]);

    expect($model->getAttributes()['status_collection'])
        ->toBe('{active}');
});

it('supports PgArrayValue element types when retrieving attributes', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'emails' => '{john@example.com,jane@example.com}',
    ]);

    expect($model->emails)
        ->toEqual([
            new Email('john@example.com'),
            new Email('jane@example.com'),
        ]);
});

it('serializes PgArrayValue element types when setting attributes', function (): void {
    $model = new TestModel;

    $model->emails = [
        new Email('john@example.com'),
        'Jane@Example.com',
    ];

    expect($model->getAttributes()['emails'])
        ->toBe('{john@example.com,jane@example.com}');
});

it('supports a PgArrayValue collection', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'email_collection' => '{john@example.com}',
    ]);

    expect($model->email_collection)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect([new Email('john@example.com')]));

    $model->email_collection = collect([new Email('jane@example.com')]);

    expect($model->getAttributes()['email_collection'])
        ->toBe('{jane@example.com}');
});

it('supports PgArrayJsonValue element types when retrieving attributes', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'addresses' => '{"{\"city\": \"Milano\", \"street\": \"Via Roma 1\"}",NULL}',
    ]);

    expect($model->addresses)
        ->toEqual([new Address('Via Roma 1', 'Milano'), null]);
});

it('serializes PgArrayJsonValue element types when setting attributes', function (): void {
    $model = new TestModel;

    $model->addresses = [
        new Address('Via Roma 1', 'Milano'),
        '{"city": "Roma", "street": "Via Po 2"}',
    ];

    expect($model->getAttributes()['addresses'])
        ->toBe('{"{\"street\":\"Via Roma 1\",\"city\":\"Milano\"}","{\"street\":\"Via Po 2\",\"city\":\"Roma\"}"}');
});

it('round-trips PgArrayJsonValue element types through an eloquent model', function (): void {
    $contacts = [
        [new Contact('John', ['+39 02 1234'], Priority::High, new Address('Via Roma 1', 'Milano'))],
        [new Contact('Jane', priority: Priority::Low)],
    ];

    $model = new TestModel;
    $model->contacts = $contacts;

    $restored = (new TestModel)->setRawAttributes($model->getAttributes());

    expect($restored->contacts)->toEqual($contacts);
});

it('supports a PgArrayJsonValue collection', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'address_collection' => '{"{\"street\":\"Via Roma 1\",\"city\":\"Milano\"}"}',
    ]);

    expect($model->address_collection)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect([new Address('Via Roma 1', 'Milano')]));

    $model->address_collection = collect([new Address('Via Po 2', 'Roma')]);

    expect($model->getAttributes()['address_collection'])
        ->toBe('{"{\"street\":\"Via Po 2\",\"city\":\"Roma\"}"}');
});

it('supports configured serializer element types when retrieving attributes', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'prices' => '{"{\"amount\": 1250, \"currency\": \"EUR\"}",NULL}',
    ]);

    expect($model->prices)
        ->toEqual([new Money(1250, 'EUR'), null]);
});

it('serializes configured serializer element types when setting attributes', function (): void {
    $model = new TestModel;

    $model->prices = [
        new Money(1250, 'EUR'),
        '{"currency": "USD", "amount": 99}',
    ];

    expect($model->getAttributes()['prices'])
        ->toBe('{"{\"amount\":1250,\"currency\":\"EUR\"}","{\"amount\":99,\"currency\":\"USD\"}"}');
});

it('round-trips multidimensional serializer element types through an eloquent model', function (): void {
    $prices = [
        [new Money(1250, 'EUR'), new Money(99, 'USD')],
        [new Money(0, 'GBP'), null],
    ];

    $model = new TestModel;
    $model->prices = $prices;

    $restored = (new TestModel)->setRawAttributes($model->getAttributes());

    expect($restored->prices)->toEqual($prices);
});

it('supports a serializer element type collection', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'price_collection' => '{"{\"amount\":1250,\"currency\":\"EUR\"}"}',
    ]);

    expect($model->price_collection)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect([new Money(1250, 'EUR')]));

    $model->price_collection = collect([new Money(99, 'USD')]);

    expect($model->getAttributes()['price_collection'])
        ->toBe('{"{\"amount\":99,\"currency\":\"USD\"}"}');
});

it('shares a declared serializer between element types', function (): void {
    $model = new TestModel;

    $model->skus = [new Sku('AB-1'), 'cd-2'];
    $model->country_codes = [[new CountryCode('IT'), 'fr'], ['de', null]];

    expect($model->getAttributes()['skus'])->toBe('{AB-1,CD-2}')
        ->and($model->getAttributes()['country_codes'])->toBe('{{IT,FR},{DE,NULL}}');

    $restored = (new TestModel)->setRawAttributes($model->getAttributes());

    expect($restored->skus)->toEqual([new Sku('AB-1'), new Sku('CD-2')])
        ->and($restored->country_codes)->toEqual([
            [new CountryCode('IT'), new CountryCode('FR')],
            [new CountryCode('DE'), null],
        ]);
});

it('encrypts each element when setting attributes', function (): void {
    $model = new TestModel;

    $model->secrets = ['alpha', null, 'alpha', ''];

    $elements = PgArrayParser::parse($model->getAttributes()['secrets']);

    expect($elements)->toHaveCount(4)
        ->and($elements[1])->toBeNull()
        ->and(Crypt::decryptString($elements[0]))->toBe('alpha')
        ->and(Crypt::decryptString($elements[2]))->toBe('alpha')
        ->and(Crypt::decryptString($elements[3]))->toBe('')
        ->and($elements[0])->not->toBe($elements[2]);
});

it('does not encrypt the array as a whole', function (): void {
    $model = new TestModel;

    $model->secrets = ['alpha', 'beta'];

    expect($model->getAttributes()['secrets'])->toStartWith('{')->toEndWith('}')
        ->and(fn () => Crypt::decryptString($model->getAttributes()['secrets']))
        ->toThrow(DecryptException::class);
});

it('decrypts each element when retrieving attributes', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'secrets' => PgArrayParser::serialize([
            Crypt::encryptString('alpha'),
            null,
            Crypt::encryptString('beta'),
        ]),
    ]);

    expect($model->secrets)->toBe(['alpha', null, 'beta']);
});

it('round-trips encrypted elements through an eloquent model', function (string $attribute, array $value): void {
    $model = new TestModel;
    $model->{$attribute} = $value;

    $restored = (new TestModel)->setRawAttributes($model->getAttributes());

    expect($restored->{$attribute})->toEqual($value);
})->with([
    'strings' => ['secrets', ['alpha', 'NULL', 'a,b {c} "d"', null]],
    'multidimensional strings' => ['secrets', [['alpha', 'beta'], ['gamma', null]]],
    'integers' => ['encrypted_numbers', [1, -2, null, 3]],
    'multidimensional integers' => ['encrypted_numbers', [[1, 2], [3, 4]]],
    'backed enums' => ['encrypted_statuses', [Status::Active, null, Status::Inactive]],
    'JSON objects' => ['encrypted_addresses', [new Address('Via Roma 1', 'Milano'), null]],
]);

it('preserves null encrypted arrays', function (): void {
    $model = new TestModel;

    $model->secrets = null;

    expect($model->getAttributes()['secrets'])->toBeNull()
        ->and($model->secrets)->toBeNull();
});

it('supports an encrypted collection', function (): void {
    $model = new TestModel;

    $model->secret_collection = collect(['alpha', 'beta']);

    $restored = (new TestModel)->setRawAttributes($model->getAttributes());

    expect($restored->secret_collection)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect(['alpha', 'beta']));
});

it('supports an encrypted element type collection', function (): void {
    $dates = collect([CarbonImmutable::parse('2026-09-25'), null]);

    $model = new TestModel;
    $model->encrypted_dates = $dates;

    $elements = PgArrayParser::parse($model->getAttributes()['encrypted_dates']);

    expect(Crypt::decryptString($elements[0]))->toBe('2026-09-25')
        ->and($elements[1])->toBeNull();

    $restored = (new TestModel)->setRawAttributes($model->getAttributes());

    expect($restored->encrypted_dates)
        ->toBeInstanceOf(Collection::class)
        ->toEqual($dates);
});

it('hashes each element when setting attributes', function (): void {
    $model = new TestModel;

    $model->recovery_codes = ['alpha', null, 'beta'];

    $elements = PgArrayParser::parse($model->getAttributes()['recovery_codes']);

    expect($elements)->toHaveCount(3)
        ->and($elements[1])->toBeNull()
        ->and(Hash::check('alpha', $elements[0]))->toBeTrue()
        ->and(Hash::check('beta', $elements[2]))->toBeTrue();
});

it('retrieves hashes and verifies values against them', function (): void {
    $model = new TestModel;
    $model->recovery_codes = ['alpha', 'beta'];

    $restored = (new TestModel)->setRawAttributes($model->getAttributes());

    expect($restored->recovery_codes)->toHaveCount(2)
        ->each(fn ($hash) => $hash->not->toBeIn(['alpha', 'beta']))
        ->and(PgArrayHash::check('beta', $restored->recovery_codes))->toBeTrue()
        ->and(PgArrayHash::check('gamma', $restored->recovery_codes))->toBeFalse();
});

it('keeps existing hashes when assigning them again', function (): void {
    $model = new TestModel;
    $model->recovery_codes = ['alpha', 'beta'];

    $restored = (new TestModel)->setRawAttributes($model->getAttributes());
    $hashes = $restored->recovery_codes;

    $restored->recovery_codes = [...$hashes, 'gamma'];

    expect($restored->recovery_codes)->toHaveCount(3)
        ->and(array_slice($restored->recovery_codes, 0, 2))->toBe($hashes)
        ->and(PgArrayHash::check('gamma', $restored->recovery_codes))->toBeTrue();
});

it('removes a consumed value from a hashed array', function (): void {
    $model = new TestModel;
    $model->recovery_codes = ['alpha', 'beta', 'gamma'];

    $codes = $model->recovery_codes;
    unset($codes[PgArrayHash::find('beta', $codes)]);
    $model->recovery_codes = array_values($codes);

    expect($model->recovery_codes)->toHaveCount(2)
        ->and(PgArrayHash::check('beta', $model->recovery_codes))->toBeFalse()
        ->and(PgArrayHash::check('alpha', $model->recovery_codes))->toBeTrue();
});

it('preserves null hashed arrays', function (): void {
    $model = new TestModel;

    $model->recovery_codes = null;

    expect($model->getAttributes()['recovery_codes'])->toBeNull()
        ->and($model->recovery_codes)->toBeNull()
        ->and(PgArrayHash::check('alpha', $model->recovery_codes))->toBeFalse();
});

it('supports a hashed collection', function (): void {
    $model = new TestModel;

    $model->recovery_code_collection = collect(['alpha', 'beta']);

    $restored = (new TestModel)->setRawAttributes($model->getAttributes());

    expect($restored->recovery_code_collection)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->and(PgArrayHash::find('beta', $restored->recovery_code_collection))->toBe(1);
});

it('stores bytea elements in the hex format', function (): void {
    $model = new TestModel;

    $model->bytea_array = ["\x00\x01", null, 'ab'];

    expect($model->getAttributes()['bytea_array'])
        ->toBe('{"\\\\x0001",NULL,"\\\\x6162"}');
});

it('retrieves bytea elements as binary strings', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'bytea_array' => '{"\\\\x00ff",NULL,"\\\\x"}',
    ]);

    expect($model->bytea_array)->toBe(["\x00\xff", null, '']);
});

it('round trips binary data through a bytea array', function (): void {
    $binary = [random_bytes(16), "\x00\"\\{},", ''];

    $model = new TestModel;
    $model->bytea_array = $binary;

    $restored = (new TestModel)->setRawAttributes($model->getAttributes());

    expect($restored->bytea_array)->toBe($binary);
});

it('normalizes inet elements when setting attributes', function (): void {
    $model = new TestModel;

    $model->inet_array = ['192.168.1.5/24', '2001:0DB8::0001/128', null];

    expect($model->getAttributes()['inet_array'])
        ->toBe('{192.168.1.5/24,2001:db8::1,NULL}')
        ->and($model->inet_array)->toBe(['192.168.1.5/24', '2001:db8::1', null]);
});

it('supports an inet collection', function (): void {
    $model = new TestModel;

    $model->setRawAttributes([
        'inet_collection' => '{10.0.0.1,::1}',
    ]);

    expect($model->inet_collection)
        ->toBeInstanceOf(Collection::class)
        ->toEqual(collect(['10.0.0.1', '::1']));
});

it('rejects invalid inet elements', function (): void {
    $model = new TestModel;

    $model->inet_array = ['10.0.0.1', 'not-an-ip'];
})->throws(UnexpectedValueException::class, 'Invalid inet value [not-an-ip].');

it('normalizes macaddr elements when setting attributes', function (): void {
    $model = new TestModel;

    $model->macaddr_array = ['08-00-2B-01-02-03', '0800.2b01.0204'];

    expect($model->getAttributes()['macaddr_array'])
        ->toBe('{08:00:2b:01:02:03,08:00:2b:01:02:04}')
        ->and($model->macaddr_array)->toBe(['08:00:2b:01:02:03', '08:00:2b:01:02:04']);
});
