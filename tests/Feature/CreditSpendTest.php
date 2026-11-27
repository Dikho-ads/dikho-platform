<?php

namespace Tests\Feature;

use App\Models\ContentItem;
use App\Models\Shop;
use App\Services\ShopSetup;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreditSpendTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PackageSeeder::class);
        $this->shop = app(ShopSetup::class)->complete(Shop::factory()->create(['package_id' => 'shuruaat']));
        Sanctum::actingAs($this->shop->owner);
    }

    public function test_approving_spends_credits_once(): void
    {
        $item = ContentItem::factory()->for($this->shop)->create(['credits' => 4, 'type' => 'print']);

        $this->postJson("/api/v1/content/{$item->id}/approve")->assertOk()->assertJsonPath('account.credits.left', 6);
        $this->postJson("/api/v1/content/{$item->id}/publish")->assertOk()->assertJsonPath('account.credits.left', 6);
        $this->assertSame('posted', $item->refresh()->status);
    }

    public function test_cannot_spend_more_credits_than_left(): void
    {
        $item = ContentItem::factory()->for($this->shop)->create(['credits' => 15]);

        $this->postJson("/api/v1/content/{$item->id}/approve")
            ->assertStatus(402)
            ->assertJsonPath('code', 'insufficient_credits')
            ->assertJsonPath('available', 10);
        $this->assertSame('draft', $item->refresh()->status);
    }

    public function test_skipping_refunds_what_was_spent(): void
    {
        $item = ContentItem::factory()->for($this->shop)->create(['credits' => 6, 'type' => 'ad']);
        $this->postJson("/api/v1/content/{$item->id}/approve")->assertOk();

        $this->postJson("/api/v1/content/{$item->id}/skip")->assertOk()->assertJsonPath('account.credits.left', 10);
    }

    public function test_scheduling_with_a_utc_time_keeps_the_real_moment(): void
    {
        $item = ContentItem::factory()->for($this->shop)->create(['credits' => 0]);
        $utc = now()->utc()->addDay()->setTime(14, 0)->format('Y-m-d\TH:i:s\Z');

        $this->postJson("/api/v1/content/{$item->id}/schedule", ['scheduled_at' => $utc])->assertOk();

        $this->assertSame('19:30', $item->refresh()->scheduled_at->format('H:i'));
    }

    public function test_message_cost_comes_from_wallet(): void
    {
        $this->shop->customers()->createMany([
            ['name' => 'Priya Joshi', 'phone' => '9810000001', 'stage' => 'regular', 'whatsapp_consent' => true],
            ['name' => 'Amol More', 'phone' => '9810000002', 'stage' => 'regular', 'whatsapp_consent' => false],
        ]);
        $id = $this->postJson('/api/v1/messages', ['channel' => 'whatsapp', 'name' => 'Weekend', 'body' => 'Offer'])->assertCreated()->assertJsonPath('data.recipients', 1)->json('data.id');

        $this->postJson("/api/v1/messages/{$id}/schedule")->assertStatus(402)->assertJsonPath('code', 'insufficient_wallet');

        $this->postJson('/api/v1/wallet/topup', ['amount_paise' => 10000])->assertOk()->assertJsonPath('account.wallet_paise', 10000);
        $this->postJson("/api/v1/messages/{$id}/schedule")->assertOk()->assertJsonPath('account.wallet_paise', 10000 - 86);
    }
}
