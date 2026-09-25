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

        // Real PostgreSQL connection for the "pgsql" test group, see tests/Pest.php.
        config()->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('PGARRAY_DB_HOST', '127.0.0.1'),
            'port' => env('PGARRAY_DB_PORT', '5432'),
            'database' => env('PGARRAY_DB_DATABASE', 'pgarray_testing'),
            'username' => env('PGARRAY_DB_USERNAME', 'postgres'),
            'password' => env('PGARRAY_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'search_path' => 'public',
            'sslmode' => env('PGARRAY_DB_SSLMODE', 'disable'),
        ]);

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
