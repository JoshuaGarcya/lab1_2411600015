<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // NOTE: the default "WithoutModelEvents" trait was removed on purpose.
    // It switches off model events while seeding, which would also switch off the
    // AccountObserver, so opening deposits would not update the balances.

    public function run(): void
    {
        $this->call([
            BankSeeder::class,
        ]);
    }
}
