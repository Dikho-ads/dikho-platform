<?php

namespace Database\Seeders;

use App\Models\Occasion;
use Illuminate\Database\Seeder;

class OccasionSeeder extends Seeder
{
    /**
     * Occasions from database/seeders/data/occasions.json. Dates were checked against panchang
     * sources; regional tithi and moon sighting can move a day.
     */
    public function run(): void
    {
        $rows = json_decode(file_get_contents(database_path('seeders/data/occasions.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            Occasion::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
