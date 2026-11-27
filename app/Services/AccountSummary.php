<?php

namespace App\Services;

use App\Http\Resources\ShopResource;
use App\Http\Resources\UserResource;
use App\Models\User;

/**
 * What every screen's shell needs: who is signed in, their shop, package, balances and nav counts.
 */
class AccountSummary
{
    public function __construct(private CreditService $credits) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $user): array
    {
        $shop = $user->currentShop();

        if ($shop === null) {
            return ['user' => new UserResource($user), 'shop' => null, 'package' => null, 'credits' => null, 'wallet_paise' => 0, 'counts' => null];
        }

        $shop->loadMissing('package', 'subscription');
        $package = $shop->package;
        $left = $shop->creditBalance();
        $used = $this->credits->usedThisPeriod($shop);
        $total = max($left + $used, $package?->credits ?? 0);
        $resetsOn = $shop->subscription?->period_end ?? now()->addMonth()->startOfMonth();

        return [
            'user' => new UserResource($user),
            'shop' => new ShopResource($shop),
            'package' => $package ? [
                'id' => $package->id,
                'name' => $package->name,
                'price_paise' => $package->price_paise,
                'credits' => $package->credits,
            ] : null,
            'credits' => [
                'left' => $left,
                'used' => $used,
                'total' => $total,
                'percent_left' => $total ? (int) round($left / $total * 100) : 0,
                'resets_on' => $resetsOn->toDateString(),
            ],
            'wallet_paise' => $shop->walletBalancePaise(),
            'counts' => [
                'auto_pending' => $shop->contentItems()->where('source', 'auto')->whereIn('status', ['draft', 'ready'])->where('scheduled_at', '>=', now()->startOfDay())->count(),
                'workflows_active' => $shop->workflows()->where('active', true)->count(),
                'campaigns_live' => $shop->campaigns()->whereIn('status', ['live', 'scheduled'])->count(),
                'notifications_unread' => $shop->feed()->whereNull('read_at')->count(),
            ],
        ];
    }
}
