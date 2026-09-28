<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Tests\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

uses(TestCase::class)->in(__DIR__);

uses()->group('pgsql')->in('Integration');

/**
 * The real PostgreSQL connection of the "pgsql" test group (PGARRAY_DB_* env
 * variables). The test is skipped when PostgreSQL is not reachable, unless
 * PGARRAY_REQUIRE_DB is true (as in CI), where it fails instead.
 */
function pgsql(): Connection
{
    $failure = pgsqlFailure();

    if ($failure !== null) {
        pgsqlUnavailable('PostgreSQL is not available', $failure);
    }

    return DB::connection('pgsql');
}

/**
 * The connection error, if PostgreSQL is not reachable. It is checked once per
 * process: connecting to a closed port can be slow (e.g. on Windows).
 */
function pgsqlFailure(): ?Throwable
{
    static $checked = false;
    static $failure = null;

    if (! $checked) {
        $checked = true;

        try {
            DB::connection('pgsql')->getPdo();
        } catch (Throwable $exception) {
            $failure = $exception;
        }
    }

    return $failure;
}

/**
 * Creates a PostgreSQL extension (e.g. vector, postgis), skipping the test
 * when it is not installed, unless PGARRAY_REQUIRE_DB is true.
 */
function pgsqlExtension(string $extension): void
{
    $connection = pgsql();

    try {
        $connection->statement("create extension if not exists \"{$extension}\"");
    } catch (Throwable $exception) {
        pgsqlUnavailable("The [{$extension}] extension is not available", $exception);
    }
}

function pgsqlUnavailable(string $reason, Throwable $exception): never
{
    if (filter_var(env('PGARRAY_REQUIRE_DB', false), FILTER_VALIDATE_BOOL)) {
        throw $exception;
    }

    test()->markTestSkipped($reason.': '.$exception->getMessage());
}
