<?php

declare(strict_types=1);

namespace AndreaColzani\PgArray;

use AndreaColzani\PgArray\Database\PgArrayQuery;
use AndreaColzani\PgArray\Database\PgArraySchema;
use AndreaColzani\PgArray\Support\PgArraySerializerRegistry;
use Illuminate\Contracts\Container\Container;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class PgArrayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
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
