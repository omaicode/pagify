<?php

namespace Pagify\PageBuilder\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PageBuilderDatabaseSeeder::class);
    }
}
