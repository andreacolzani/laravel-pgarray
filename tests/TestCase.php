<?php

namespace AndreaColzani\PgArray\Tests;

use AndreaColzani\PgArray\PgArrayServiceProvider;
use AndreaColzani\PgArray\Tests\Fixtures\Money;
use AndreaColzani\PgArray\Tests\Fixtures\MoneySerializer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'AndreaColzani\\PgArray\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            PgArrayServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        config()->set('hashing.bcrypt.rounds', 4);

        config()->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));

        config()->set('pgarray.serializers', [
            Money::class => MoneySerializer::class,
        ]);

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
