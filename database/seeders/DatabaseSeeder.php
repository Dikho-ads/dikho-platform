<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Packages and occasions are reference data every environment needs; the demo shops are for
     * local and staging only.
     */
    public function run(): void
    {
        $this->call([PackageSeeder::class, OccasionSeeder::class]);

        if (! app()->isProduction()) {
            $this->call(DemoShopSeeder::class);
        }
    }
}
