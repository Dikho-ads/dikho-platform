<?php

namespace Database\Factories;

use App\Models\ContentItem;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentItem>
 */
class ContentItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'type' => 'reel',
            'angle' => 'offer',
            'language' => 'en',
            'title' => 'Offer reel',
            'hook' => 'Purana phone laao, ₹2,000 extra pao',
            'status' => 'draft',
            'source' => 'auto',
            'scheduled_at' => now()->addDays(2),
            'credits' => 15,
        ];
    }
}
