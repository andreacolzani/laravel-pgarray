<?php

declare(strict_types=1);

use AndreaColzani\PgArray\PgArrayServiceProvider;
use Illuminate\Support\ServiceProvider;

it('publishes the config file with the documented tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(PgArrayServiceProvider::class, 'pgarray-config');

    expect($paths)->toHaveCount(1)
        ->and(array_values($paths)[0])->toBe(config_path('pgarray.php'))
        ->and(array_keys($paths)[0])->toEndWith('pgarray.php');
});
