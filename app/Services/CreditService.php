<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\CreditTransaction;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Credits are an append-only ledger. The balance is the newest row's balance_after; every write
 * locks the shop row first so two spends cannot both pass the balance check.
 */
class CreditService
{
    public function balance(Shop $shop): int
    {
        return $shop->creditBalance();
    }

    public function grant(Shop $shop, int $credits, string $reason, string $description, ?Model $subject = null): CreditTransaction
    {
        return $this->write($shop, abs($credits), $reason, $description, $subject);
    }

    public function spend(Shop $shop, int $credits, string $description, ?Model $subject = null): CreditTransaction
    {
        return $this->write($shop, -abs($credits), 'spend', $description, $subject);
    }

    public function refund(Shop $shop, int $credits, string $description, ?Model $subject = null): CreditTransaction
    {
        return $this->write($shop, abs($credits), 'refund', $description, $subject);
    }

    /**
     * Credits used since the start of the current billing period.
     */
    public function usedThisPeriod(Shop $shop): int
    {
        $start = $shop->subscription?->period_start ?? now()->startOfMonth();

        return (int) -$shop->creditTransactions()
            ->where('created_at', '>=', $start)
            ->whereIn('reason', ['spend', 'refund'])
            ->sum('delta');
    }

    private function write(Shop $shop, int $delta, string $reason, string $description, ?Model $subject): CreditTransaction
    {
        return DB::transaction(function () use ($shop, $delta, $reason, $description, $subject): CreditTransaction {
            Shop::query()->whereKey($shop->id)->lockForUpdate()->first();
            $balance = $shop->creditBalance();

            if ($delta < 0 && $balance + $delta < 0) {
                throw new InsufficientBalanceException('credits', -$delta, $balance);
            }

            return $shop->creditTransactions()->create([
                'delta' => $delta,
                'balance_after' => $balance + $delta,
                'reason' => $reason,
                'description' => $description,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'created_at' => now(),
            ]);
        });
    }
}
