<?php

namespace AndreaColzani\PgArray;

use AndreaColzani\PgArray\Commands\PgArrayCommand;
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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_pgarray_table')
            ->hasCommand(PgArrayCommand::class);
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
}
