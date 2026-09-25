<?php

namespace AndreaColzani\PgArray;

use AndreaColzani\PgArray\Database\PgArrayQuery;
use AndreaColzani\PgArray\Database\PgArraySchema;
use AndreaColzani\PgArray\Support\PgArraySerializerRegistry;
use Illuminate\Contracts\Container\Container;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PgArrayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-pgarray')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(
            PgArraySerializerRegistry::class,
            fn (Container $app): PgArraySerializerRegistry => new PgArraySerializerRegistry(
                $app,
                $app->make('config')->get('pgarray.serializers', []),
            ),
        );
    }

    public function packageBooted(): void
    {
        PgArraySchema::register();
        PgArrayQuery::register();
    }
}
