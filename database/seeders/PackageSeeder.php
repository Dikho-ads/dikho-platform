<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['id' => 'shuruaat', 'name' => 'Shuruaat', 'price_paise' => 0, 'credits' => 10, 'blurb' => 'Free daily posts', 'features' => ['A ready post every day', 'Festival greetings', '1 language', '10 credits a month']],
            ['id' => 'dukaan', 'name' => 'Dukaan', 'price_paise' => 19900, 'credits' => 150, 'blurb' => '10 AI posts + 4 reels', 'features' => ['Everything in Shuruaat', 'AI posts and 4 reels a month', 'Monthly plan', '2 languages', '150 credits a month']],
            ['id' => 'badhao', 'name' => 'Badhao', 'price_paise' => 49900, 'credits' => 400, 'blurb' => 'Adds win-back + ads', 'features' => ['Everything in Dukaan', 'Win-back and birthday workflows', 'Meta ads from your wallet', '3 languages', '400 credits a month']],
            ['id' => 'dost', 'name' => 'Dost', 'price_paise' => 199900, 'credits' => 1000, 'blurb' => 'With a human marketer', 'features' => ['Everything in Badhao', 'A human marketer on WhatsApp', 'Monthly plan call', 'Print designs', '1,000 credits a month']],
        ];

        foreach ($packages as $sort => $package) {
            Package::query()->updateOrCreate(['id' => $package['id']], $package + ['sort' => $sort]);
        }
    }
}
