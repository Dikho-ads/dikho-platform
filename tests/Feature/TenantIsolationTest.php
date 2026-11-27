<?php

namespace Tests\Feature;

use App\Models\ContentItem;
use App\Models\Customer;
use App\Models\Shop;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_shop_cannot_read_or_change_another_shops_rows(): void
    {
        $this->seed(PackageSeeder::class);
        $mine = Shop::factory()->ready()->create();
        $theirs = Shop::factory()->ready()->create();
        $theirCustomer = Customer::factory()->for($theirs)->create();
        $theirItem = ContentItem::factory()->for($theirs)->create();
        Customer::factory()->for($mine)->create();

        Sanctum::actingAs($mine->owner);

        $this->getJson("/api/v1/customers/{$theirCustomer->id}")->assertNotFound();
        $this->patchJson("/api/v1/customers/{$theirCustomer->id}", ['name' => 'Changed'])->assertNotFound();
        $this->getJson("/api/v1/content/{$theirItem->id}")->assertNotFound();
        $this->postJson("/api/v1/content/{$theirItem->id}/skip")->assertNotFound();
        $this->postJson('/api/v1/content/approve-batch', ['ids' => [$theirItem->id]])->assertUnprocessable();

        $this->assertSame(1, $this->getJson('/api/v1/customers')->json('meta.total'));
        $this->assertSame(0, $this->getJson('/api/v1/customers?q=zzzz')->json('meta.total'));
        $this->assertSame($theirCustomer->name, $theirCustomer->refresh()->name);
        $this->assertSame('draft', $theirItem->refresh()->status);
    }
}
