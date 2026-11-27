<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\AccountSummary;
use App\Services\CreditService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Billing. No payment gateway is connected yet: top-ups are recorded with a generated UPI
 * reference so the ledgers, balances and screens behave as they will with real payments.
 */
class BillingController extends Controller
{
    /** Credit packs: credits => price in paise. */
    public const CREDIT_PACKS = [50 => 9900, 150 => 24900, 400 => 59900];

    public function __construct(private AccountSummary $summary) {}

    public function show(Request $request): JsonResponse
    {
        $shop = $this->shop($request);
        $subscription = $shop->subscription;

        return response()->json([
            'account' => $this->summary->for($request->user()),
            'subscription' => $subscription ? [
                'package_id' => $subscription->package_id,
                'status' => $subscription->status,
                'autopay' => $subscription->autopay,
                'autopay_upi' => $subscription->autopay_upi,
                'period_start' => $subscription->period_start->toDateString(),
                'period_end' => $subscription->period_end->toDateString(),
                'next_package_id' => $subscription->next_package_id,
            ] : null,
            'packages' => Package::query()->orderBy('sort')->get(['id', 'name', 'price_paise', 'credits', 'blurb', 'features']),
            'credit_packs' => collect(self::CREDIT_PACKS)->map(fn (int $price, int $credits): array => ['credits' => $credits, 'price_paise' => $price])->values(),
            'credit_history' => $shop->creditTransactions()->latest('id')->limit(30)->get(['id', 'delta', 'balance_after', 'reason', 'description', 'created_at']),
            'wallet_history' => $shop->walletTransactions()->latest('id')->limit(30)->get(['id', 'amount_paise', 'balance_after_paise', 'reason', 'description', 'reference', 'created_at']),
        ]);
    }

    /**
     * Upgrades apply now and add the difference in credits; downgrades apply at renewal.
     */
    public function changePackage(Request $request, CreditService $credits): JsonResponse
    {
        $shop = $this->shop($request);
        $package = Package::query()->findOrFail($request->validate(['package_id' => ['required', Rule::exists('packages', 'id')]])['package_id']);
        $current = $shop->package;
        abort_if($current->id === $package->id, 422, "You are already on {$package->name}.");

        DB::transaction(function () use ($shop, $package, $current, $credits): void {
            $subscription = $shop->subscription;
            if ($package->price_paise > $current->price_paise) {
                $shop->update(['package_id' => $package->id]);
                $subscription?->update(['package_id' => $package->id, 'next_package_id' => null]);
                $credits->grant($shop, $package->credits - $current->credits, 'plan_grant', "Upgrade to {$package->name} · extra credits this month");
            } else {
                $subscription?->update(['next_package_id' => $package->id]);
            }
        });

        return response()->json($this->show($request)->getData(true));
    }

    public function topUpCredits(Request $request, CreditService $credits, WalletService $wallet): JsonResponse
    {
        $data = $request->validate(['credits' => ['required', Rule::in(array_keys(self::CREDIT_PACKS))]]);
        $shop = $this->shop($request);
        $credits->grant($shop, (int) $data['credits'], 'topup', "{$data['credits']} credit pack · ₹".number_format(self::CREDIT_PACKS[$data['credits']] / 100).' paid by UPI');

        return response()->json(['account' => $this->summary->for($request->user())]);
    }

    public function topUpWallet(Request $request, WalletService $wallet): JsonResponse
    {
        $data = $request->validate(['amount_paise' => ['required', 'integer', 'min:10000', 'max:5000000']], [
            'amount_paise.min' => 'The smallest top-up is ₹100.',
            'amount_paise.max' => 'The largest top-up is ₹50,000 at a time.',
        ]);
        $wallet->topUp($this->shop($request), (int) $data['amount_paise'], 'UPI-'.Str::upper(Str::random(10)));

        return response()->json(['account' => $this->summary->for($request->user())]);
    }
}
