<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentItemResource;
use App\Http\Resources\OccasionResource;
use App\Models\Occasion;
use App\Services\Metrics;
use App\Services\StarterContent;
use App\Services\WalletService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Metrics $metrics, StarterContent $starter): JsonResponse
    {
        $shop = $this->shop($request);
        $user = $request->user();
        $today = CarbonImmutable::today();
        $weekFrom = $today->subDays(6);
        $now = $metrics->totals($shop, $weekFrom, $today);
        $before = $metrics->totals($shop, $weekFrom->subDays(7), $weekFrom->subDay());

        $ready = $shop->contentItems()
            ->where('status', 'ready')
            ->whereBetween('scheduled_at', [$today->startOfDay(), $today->endOfDay()])
            ->orderBy('scheduled_at')
            ->limit(6)
            ->get();
        $postedToday = $shop->contentItems()->where('status', 'posted')->whereBetween('posted_at', [$today->startOfDay(), $today->endOfDay()])->count();

        $batch = $shop->contentItems()
            ->where('status', 'draft')
            ->whereBetween('scheduled_at', [$today->addDay()->startOfDay(), $today->addDays(7)->endOfDay()])
            ->orderBy('scheduled_at')
            ->get();

        $nextMonth = $today->addMonthNoOverflow()->startOfMonth();
        $plan = $shop->plans()->whereDate('month', $nextMonth)->latest('version')->withCount('items')->first();
        $lapsed = $shop->customers()->where('stage', 'lapsed')->where('whatsapp_consent', true)->count();
        $productsWithoutPhoto = max(0, 2 - $shop->products()->whereNotNull('photo_slot')->count());

        $needs = [];
        if ($plan && in_array($plan->status, ['draft', 'review'], true)) {
            $needs[] = ['kind' => 'plan', 'title' => $nextMonth->format('F').' plan', 'detail' => "{$plan->items_count} items · {$plan->credits_budget} credits", 'badge' => 'by '.$today->endOfMonth()->format('j M'), 'link' => '/app/studio/plan/result?id='.$plan->id];
        } elseif (! $plan) {
            $needs[] = ['kind' => 'plan', 'title' => 'Plan '.$nextMonth->format('F'), 'detail' => 'Tell us the goal; we draft the month', 'badge' => 'by '.$today->endOfMonth()->format('j M'), 'link' => '/app/studio/plan'];
        }
        if ($batch->isNotEmpty()) {
            $needs[] = ['kind' => 'batch', 'title' => 'This week’s batch', 'detail' => $this->describeBatch($batch), 'badge' => null, 'link' => null, 'credits' => (int) $batch->sum('credits'), 'items' => ContentItemResource::collection($batch)];
        }
        if ($lapsed > 0) {
            $needs[] = ['kind' => 'winback', 'title' => "{$lapsed} regulars stopped coming", 'detail' => 'Personal offer to each · ≈ ₹'.number_format($lapsed * WalletService::WHATSAPP_PAISE / 100), 'badge' => null, 'link' => '/app/customers?stage=lapsed'];
        }
        if ($productsWithoutPhoto > 0) {
            $needs[] = ['kind' => 'photos', 'title' => "Add {$productsWithoutPhoto} product photo".($productsWithoutPhoto > 1 ? 's' : ''), 'detail' => 'Real photos make reels and ads possible', 'badge' => null, 'link' => '/app/settings'];
        }

        $occasions = $starter->relevantOccasions($shop, $today, $today->addDays(75))->take(5);
        $planned = $shop->contentItems()->whereIn('occasion_id', $occasions->pluck('id'))->selectRaw('occasion_id, count(*) as n')->groupBy('occasion_id')->pluck('n', 'occasion_id');

        return response()->json([
            'greeting' => [
                'name' => $user->firstName(),
                'date' => $today->toDateString(),
                'city' => $shop->city,
                'summary' => $this->summary($ready->count(), count($needs), $plan?->status, $nextMonth),
            ],
            'kpis' => collect(['views', 'chats', 'calls', 'qr_scans'])->map(fn (string $k): array => [
                'key' => $k,
                'value' => $now[$k],
                'previous' => $before[$k],
                'change' => $metrics->change($now[$k], $before[$k]),
            ])->values(),
            'ready' => ContentItemResource::collection($ready),
            'posted_today' => $postedToday,
            'chats_per_day' => $metrics->perDay($shop, $weekFrom, $today),
            'insights' => $metrics->insights($shop, $today->subDays(27), $today),
            'needs_you' => $needs,
            'coming_up' => OccasionResource::collection($occasions->each(fn (Occasion $o) => $o->setAttribute('planned_items', (int) ($planned[$o->id] ?? 0)))->values()),
        ]);
    }

    private function describeBatch($batch): string
    {
        $counts = $batch->countBy(fn ($i) => in_array($i->type, ['reel'], true) ? 'reel' : (in_array($i->type, ['whatsapp', 'sms'], true) ? 'message' : 'post'));

        return collect(['reel' => 'reel', 'post' => 'post', 'message' => 'WhatsApp offer'])
            ->filter(fn ($label, $k) => isset($counts[$k]))
            ->map(fn ($label, $k) => $counts[$k].' '.$label.($counts[$k] > 1 ? 's' : ''))
            ->implode(', ');
    }

    private function summary(int $ready, int $needs, ?string $planStatus, CarbonImmutable $nextMonth): string
    {
        $parts = [];
        $parts[] = $ready === 0 ? 'No posts are waiting today' : ($ready === 1 ? 'One post is ready' : "{$ready} posts are ready");
        if ($needs > 0) {
            $parts[] = $needs === 1 ? 'one thing needs a look' : "{$needs} things need a look";
        }
        $parts[] = $planStatus ? $nextMonth->format('F').'’s plan is '.($planStatus === 'approved' ? 'approved' : 'drafted') : $nextMonth->format('F').' is not planned yet';

        return implode(', ', array_slice($parts, 0, -1)).(count($parts) > 1 ? ', and ' : '').end($parts).'.';
    }
}
