<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates a shop for a new account and finishes setup: subscription, first credit grant,
 * channel rows, default workflows and the first two weeks of content.
 */
class ShopSetup
{
    public function __construct(private CreditService $credits, private StarterContent $starter) {}

    public function createFor(User $user, string $name, ?string $whatsapp = null): Shop
    {
        $shop = Shop::query()->create([
            'owner_id' => $user->id,
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'category' => 'kirana',
            'whatsapp' => $whatsapp,
            'languages' => ['en'],
            'package_id' => 'shuruaat',
            'setup_step' => 1,
        ]);

        $shop->members()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return $shop;
    }

    public function complete(Shop $shop): Shop
    {
        if ($shop->isSetupComplete()) {
            return $shop;
        }

        DB::transaction(function () use ($shop): void {
            $package = Package::query()->findOrFail($shop->package_id);

            $shop->subscription()->create([
                'package_id' => $package->id,
                'status' => 'active',
                'period_start' => now()->startOfDay(),
                'period_end' => now()->addMonth()->startOfDay(),
            ]);
            $this->credits->grant($shop, $package->credits, 'plan_grant', "{$package->name} plan · monthly credits");

            foreach (['whatsapp', 'instagram', 'facebook', 'google', 'meta_ads', 'upi'] as $provider) {
                $shop->connections()->firstOrCreate(['provider' => $provider], [
                    'status' => $provider === 'whatsapp' && $shop->whatsapp ? 'pending' : 'not_linked',
                    'account' => $provider === 'whatsapp' ? $shop->whatsapp : null,
                ]);
            }

            foreach (self::defaultWorkflows() as $workflow) {
                $shop->workflows()->create($workflow + ['active' => false]);
            }

            $this->starter->generate($shop);

            $shop->feed()->create([
                'kind' => 'welcome',
                'title' => 'Your first posts are ready',
                'body' => 'Three posts for today and a draft for the next two weeks. Share one now from Home.',
                'link' => '/app',
                'created_at' => now(),
            ]);

            $shop->update(['setup_completed_at' => now(), 'setup_step' => 10]);
        });

        return $shop->refresh();
    }

    /**
     * @return list<array{name: string, trigger: string, description: string, steps: list<array{after: string, action: string, text: string}>}>
     */
    public static function defaultWorkflows(): array
    {
        return [
            ['name' => 'Welcome new customer', 'trigger' => 'new_customer', 'description' => 'Thank-you message the same day, then your best offer after 3 days.', 'steps' => [
                ['after' => '0d', 'action' => 'whatsapp', 'text' => 'Thank you for visiting {shop}! Save this number for offers.'],
                ['after' => '3d', 'action' => 'whatsapp', 'text' => 'A small welcome gift for you: show this message for 5% off.'],
            ]],
            ['name' => 'Birthday wish', 'trigger' => 'birthday', 'description' => 'A wish on the morning of the birthday with a small gift.', 'steps' => [
                ['after' => '0d', 'action' => 'whatsapp', 'text' => 'Happy birthday {name}! A gift from {shop} is waiting for you this week.'],
            ]],
            ['name' => 'Win back lapsed regulars', 'trigger' => 'lapsed', 'description' => 'When a regular has not come for 45 days, send a personal offer.', 'steps' => [
                ['after' => '45d', 'action' => 'whatsapp', 'text' => 'We missed you, {name}! Here is something just for you.'],
                ['after' => '52d', 'action' => 'sms', 'text' => 'Your offer at {shop} ends Sunday.'],
            ]],
            ['name' => 'Ask for a Google review', 'trigger' => 'after_purchase', 'description' => 'Two days after a purchase, ask happy customers for a review.', 'steps' => [
                ['after' => '2d', 'action' => 'whatsapp', 'text' => 'Hope you are happy with your purchase! A Google review helps us a lot: {review_link}'],
            ]],
            ['name' => 'Missed call reply', 'trigger' => 'missed_call', 'description' => 'If you miss a call, reply on WhatsApp within a minute.', 'steps' => [
                ['after' => '1m', 'action' => 'whatsapp', 'text' => 'Sorry we missed your call! How can we help? Reply here.'],
            ]],
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'shop';
        $slug = $base;
        $n = 2;
        while (Shop::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
