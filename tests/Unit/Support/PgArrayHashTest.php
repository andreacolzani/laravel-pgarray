<?php

declare(strict_types=1);

use AndreaColzani\PgArray\Support\PgArrayHash;
use Illuminate\Support\Facades\Hash;

it('verifies a value against multiple hashes', function (): void {
    $hashes = [Hash::make('alpha'), Hash::make('beta'), Hash::make('gamma')];

    expect(PgArrayHash::check('beta', $hashes))->toBeTrue()
        ->and(PgArrayHash::check('delta', $hashes))->toBeFalse();
});

it('finds the key of the matching hash', function (): void {
    $hashes = [Hash::make('alpha'), Hash::make('beta'), Hash::make('gamma')];

    expect(PgArrayHash::find('alpha', $hashes))->toBe(0)
        ->and(PgArrayHash::find('gamma', $hashes))->toBe(2)
        ->and(PgArrayHash::find('delta', $hashes))->toBeNull();
});

it('verifies values against collections', function (): void {
    $hashes = collect(['a' => Hash::make('alpha'), 'b' => Hash::make('beta')]);

    expect(PgArrayHash::check('beta', $hashes))->toBeTrue()
        ->and(PgArrayHash::find('beta', $hashes))->toBe('b');
});

it('skips null elements and nested arrays', function (): void {
    $hashes = [null, [Hash::make('alpha')], Hash::make('beta')];

    expect(PgArrayHash::check('alpha', $hashes))->toBeFalse()
        ->and(PgArrayHash::find('beta', $hashes))->toBe(2);
});

it('handles empty and null arrays', function (): void {
    expect(PgArrayHash::check('alpha', []))->toBeFalse()
        ->and(PgArrayHash::check('alpha', null))->toBeFalse()
        ->and(PgArrayHash::find('alpha', null))->toBeNull();
});
