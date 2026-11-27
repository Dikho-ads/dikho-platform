<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'category' => 'mobile',
            'city' => 'Nashik',
            'area' => 'College Road',
            'whatsapp' => '+91 98100 00'.fake()->numerify('###'),
            'languages' => ['en', 'hi'],
            'since_year' => 2015,
            'package_id' => 'dukaan',
            'setup_step' => 1,
        ];
    }

    /**
     * A shop that has finished setup (no subscription or credits; use ShopSetup for those).
     */
    public function ready(): static
    {
        return $this->state(fn (): array => ['setup_step' => 10, 'setup_completed_at' => now()]);
    }
}
