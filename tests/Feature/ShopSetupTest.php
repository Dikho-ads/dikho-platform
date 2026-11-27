<?php

namespace Tests\Feature;

use App\Models\Shop;
use Database\Seeders\OccasionSeeder;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShopSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_finishing_setup_grants_credits_and_makes_personal_starter_content(): void
    {
        $this->seed([PackageSeeder::class, OccasionSeeder::class]);
        $shop = Shop::factory()->create(['name' => 'Laxmi Kirana', 'category' => 'kirana', 'city' => 'Pune', 'area' => 'Kothrud']);
        Sanctum::actingAs($shop->owner);

        $this->getJson('/api/v1/dashboard')->assertStatus(409)->assertJsonPath('code', 'setup_incomplete');

        $this->postJson('/api/v1/shop/setup/1', ['name' => 'Laxmi Kirana Stores', 'category' => 'kirana', 'since_year' => 1990])
            ->assertOk()
            ->assertJsonPath('shop.setup_step', 2);
        $this->postJson('/api/v1/shop/setup/4', ['products' => [['name' => 'Ration kit', 'price' => '₹2,499']]])->assertOk();

        $this->postJson('/api/v1/shop/setup/complete', ['package_id' => 'dukaan'])
            ->assertOk()
            ->assertJsonPath('shop.name', 'Laxmi Kirana Stores')
            ->assertJsonPath('shop.setup_complete', true)
            ->assertJsonPath('credits.left', 150);

        $ready = $this->getJson('/api/v1/dashboard')->assertOk()->json('ready');
        $this->assertCount(3, $ready);
        $this->assertStringContainsString('Laxmi Kirana Stores', implode(' ', array_column($ready, 'caption')));
        $this->assertStringNotContainsString('Sai Mobile', json_encode($ready));
    }
}
