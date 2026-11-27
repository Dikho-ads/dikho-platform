<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => fake()->firstName().' '.fake()->lastName(),
            'phone' => '9'.fake()->unique()->numerify('#########'),
            'stage' => 'regular',
            'whatsapp_consent' => true,
            'visits' => 3,
            'last_visit_on' => now()->subDays(10)->toDateString(),
        ];
    }

    public function lapsed(): static
    {
        return $this->state(fn (): array => ['stage' => 'lapsed', 'last_visit_on' => now()->subDays(60)->toDateString()]);
    }
}
