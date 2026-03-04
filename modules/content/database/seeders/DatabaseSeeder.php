<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ContentDatabaseSeeder::class);
    }
}
