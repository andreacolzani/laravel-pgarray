<?php

namespace AndreaColzani\PgArray\Commands;

use Illuminate\Console\Command;

class PgArrayCommand extends Command
{
    public $signature = 'laravel-pgarray';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
