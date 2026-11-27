<?php

namespace App\Services;

use App\Models\ContentItem;
use App\Models\Shop;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Reads results from daily_metrics (per channel per day) and content_items (per piece).
 */
class Metrics
{
    public const FIELDS = ['views', 'chats', 'calls', 'qr_scans', 'offer_codes', 'ad_spend_paise'];

    /**
     * @return array<string, int>
     */
    public function totals(Shop $shop, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $row = $shop->dailyMetrics()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw(implode(', ', array_map(fn (string $f): string => "COALESCE(SUM({$f}), 0) as {$f}", self::FIELDS)))
            ->first();

        return array_map('intval', array_intersect_key($row?->getAttributes() ?? [], array_flip(self::FIELDS)) + array_fill_keys(self::FIELDS, 0));
    }

    /**
     * @return list<array{date: string, views: int, chats: int, calls: int, qr_scans: int}>
     */
    public function perDay(Shop $shop, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = $shop->dailyMetrics()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn ($m) => $m->date->toDateString());

        $days = [];
        for ($d = $from; $d->lte($to); $d = $d->addDay()) {
            $group = $rows->get($d->toDateString(), collect());
            $days[] = [
                'date' => $d->toDateString(),
                'views' => (int) $group->sum('views'),
                'chats' => (int) $group->sum('chats'),
                'calls' => (int) $group->sum('calls'),
                'qr_scans' => (int) $group->sum('qr_scans'),
            ];
        }

        return $days;
    }

    /**
     * @return list<array<string, int|string>>
     */
    public function perChannel(Shop $shop, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $shop->dailyMetrics()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('channel, SUM(views) as views, SUM(chats) as chats, SUM(calls) as calls, SUM(qr_scans) as qr_scans, SUM(ad_spend_paise) as ad_spend_paise')
            ->groupBy('channel')
            ->orderByDesc('chats')
            ->get()
            ->map(fn ($r): array => [
                'channel' => $r->channel,
                'views' => (int) $r->views,
                'chats' => (int) $r->chats,
                'calls' => (int) $r->calls,
                'qr_scans' => (int) $r->qr_scans,
                'ad_spend_paise' => (int) $r->ad_spend_paise,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, ContentItem>
     */
    public function topContent(Shop $shop, CarbonImmutable $from, CarbonImmutable $to, int $limit = 5): Collection
    {
        return $shop->contentItems()
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$from->startOfDay(), $to->endOfDay()])
            ->orderByDesc('chats')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    /**
     * Best day, time, hook and cost per chat for the window, in words the owner can act on.
     *
     * @return array{best_day: string|null, best_time: string|null, best_hook: string|null, cost_per_chat_paise: int|null}
     */
    public function insights(Shop $shop, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = collect($this->perDay($shop, $from, $to));
        $bestDay = $days->sortByDesc('chats')->first();
        $top = $this->topContent($shop, $from, $to, 1)->first();

        $hours = $shop->contentItems()
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$from->startOfDay(), $to->endOfDay()])
            ->get(['posted_at', 'chats'])
            ->groupBy(fn ($i) => intdiv((int) $i->posted_at->format('G'), 2) * 2)
            ->map(fn ($g) => $g->sum('chats'))
            ->sortDesc();
        $hour = $hours->keys()->first();

        $ads = $shop->dailyMetrics()
            ->where('channel', 'meta_ad')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COALESCE(SUM(chats),0) as chats, COALESCE(SUM(ad_spend_paise),0) as spend')
            ->first();

        return [
            'best_day' => $bestDay && $bestDay['chats'] > 0 ? CarbonImmutable::parse($bestDay['date'])->format('D').($top ? ' · '.$top->title : '') : null,
            'best_time' => $hour !== null ? sprintf('%s–%s', $this->hourLabel($hour), $this->hourLabel($hour + 2)) : null,
            'best_hook' => $top?->hook,
            'cost_per_chat_paise' => $ads && (int) $ads->chats > 0 ? intdiv((int) $ads->spend, (int) $ads->chats) : null,
        ];
    }

    public function change(int $now, int $before): ?int
    {
        return $before > 0 ? (int) round(($now - $before) / $before * 100) : null;
    }

    private function hourLabel(int $hour): string
    {
        $h = $hour % 24;

        return ($h % 12 ?: 12).($h < 12 ? ' am' : ' pm');
    }
}
