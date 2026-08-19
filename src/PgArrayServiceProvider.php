<?php

namespace AndreaColzani\PgArray;

use AndreaColzani\PgArray\Commands\PgArrayCommand;
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
}
