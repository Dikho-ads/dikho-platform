<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Shop;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The wallet pays for things Dikho passes through at cost: WhatsApp and SMS messages and Meta ads.
 * Amounts are in paise; the ledger is append-only like credits.
 */
class WalletService
{
    /** Meta's marketing-conversation rate for India, per recipient. */
    public const WHATSAPP_PAISE = 86;

    /** Transactional SMS rate per recipient. */
    public const SMS_PAISE = 25;

    public function balancePaise(Shop $shop): int
    {
        return $shop->walletBalancePaise();
    }

    public function costPaise(string $channel, int $recipients): int
    {
        return $recipients * ($channel === 'sms' ? self::SMS_PAISE : self::WHATSAPP_PAISE);
    }

    public function topUp(Shop $shop, int $amountPaise, string $reference, string $description = 'Wallet top-up (UPI)'): WalletTransaction
    {
        return $this->write($shop, abs($amountPaise), 'topup', $description, $reference, null);
    }

    public function charge(Shop $shop, int $amountPaise, string $reason, string $description, ?Model $subject = null): WalletTransaction
    {
        return $this->write($shop, -abs($amountPaise), $reason, $description, null, $subject);
    }

    public function refund(Shop $shop, int $amountPaise, string $description, ?Model $subject = null): WalletTransaction
    {
        return $this->write($shop, abs($amountPaise), 'refund', $description, null, $subject);
    }

    private function write(Shop $shop, int $amount, string $reason, string $description, ?string $reference, ?Model $subject): WalletTransaction
    {
        return DB::transaction(function () use ($shop, $amount, $reason, $description, $reference, $subject): WalletTransaction {
            Shop::query()->whereKey($shop->id)->lockForUpdate()->first();
            $balance = $shop->walletBalancePaise();

            if ($amount < 0 && $balance + $amount < 0) {
                throw new InsufficientBalanceException('wallet', -$amount, $balance);
            }

            return $shop->walletTransactions()->create([
                'amount_paise' => $amount,
                'balance_after_paise' => $balance + $amount,
                'reason' => $reason,
                'description' => $description,
                'reference' => $reference,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'created_at' => now(),
            ]);
        });
    }
}
