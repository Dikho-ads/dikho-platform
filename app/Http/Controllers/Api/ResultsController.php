<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentItemResource;
use App\Services\Metrics;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResultsController extends Controller
{
    public function __invoke(Request $request, Metrics $metrics): JsonResponse
    {
        $range = $request->validate(['range' => ['nullable', Rule::in(['week', 'last_week', 'month'])]])['range'] ?? 'week';
        $shop = $this->shop($request);
        $today = CarbonImmutable::today();

        [$from, $to] = match ($range) {
            'last_week' => [$today->subDays(13), $today->subDays(7)],
            'month' => [$today->subDays(29), $today],
            default => [$today->subDays(6), $today],
        };
        $length = (int) $from->diffInDays($to) + 1;
        $totals = $metrics->totals($shop, $from, $to);
        $previous = $metrics->totals($shop, $from->subDays($length), $from->subDay());

        $winBack = $shop->messages()->where('template', 'win_back')->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])->get();

        return response()->json([
            'range' => ['key' => $range, 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'days' => $length],
            'totals' => collect($totals)->map(fn (int $v, string $k): array => [
                'value' => $v,
                'previous' => $previous[$k],
                'change' => $metrics->change($v, $previous[$k]),
            ]),
            'per_day' => $metrics->perDay($shop, $from, $to),
            'per_channel' => $metrics->perChannel($shop, $from, $to),
            'top' => ContentItemResource::collection($metrics->topContent($shop, $from, $to, 5)),
            'insights' => $metrics->insights($shop, $from, $to),
            'ads' => [
                'spend_paise' => $totals['ad_spend_paise'],
                'chats' => (int) $shop->dailyMetrics()->where('channel', 'meta_ad')->whereBetween('date', [$from->toDateString(), $to->toDateString()])->sum('chats'),
            ],
            'win_back' => [
                'sent' => (int) $winBack->sum('recipients'),
                'replies' => (int) $winBack->sum('replies'),
                'cost_paise' => (int) $winBack->sum('cost_paise'),
            ],
            'posted' => $shop->contentItems()->where('status', 'posted')->whereBetween('posted_at', [$from->startOfDay(), $to->endOfDay()])->count(),
        ]);
    }
}
