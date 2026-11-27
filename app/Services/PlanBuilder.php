<?php

namespace App\Services;

use App\Models\Occasion;
use App\Models\Plan;
use App\Models\Shop;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Turns an owner's brief into a month plan: a strategy (goal, funnel, channel mix, reasons) and
 * the dated content items that carry it out, kept inside the credit budget they chose.
 */
class PlanBuilder
{
    /** Funnel stages: share of the month's pieces and the angle each stage leans on. */
    private const FUNNEL = [
        ['key' => 'awareness', 'name' => 'Pehchaan', 'label' => 'Awareness', 'share' => 30, 'angles' => ['local', 'newness'], 'formats' => 'Reels, Google posts, Status'],
        ['key' => 'trust', 'name' => 'Bharosa', 'label' => 'Trust', 'share' => 25, 'angles' => ['trust', 'tip'], 'formats' => 'Customer stories, tips carousels'],
        ['key' => 'buying', 'name' => 'Kharidari', 'label' => 'Buying', 'share' => 30, 'angles' => ['offer'], 'formats' => 'Offer posts, WhatsApp, SMS, ads'],
        ['key' => 'return', 'name' => 'Wapsi', 'label' => 'Coming back', 'share' => 15, 'angles' => ['trust'], 'formats' => 'Check-ins, birthdays, reviews'],
    ];

    /** Weekly rhythm: day of week (1 = Mon) => format. Status every day is free. */
    private const WEEK = [1 => 'post', 2 => 'google_post', 3 => 'carousel', 4 => 'post', 5 => 'whatsapp', 6 => 'reel', 7 => 'post'];

    public function __construct(private CopyWriter $writer, private StarterContent $starter, private Metrics $metrics) {}

    /**
     * @param  array<string, mixed>  $brief
     */
    public function build(Shop $shop, array $brief): Plan
    {
        $month = CarbonImmutable::parse(($brief['month'] ?? now()->addMonthNoOverflow()->format('Y-m')).'-01')->startOfMonth();
        $budget = (int) ($brief['credits'] ?? min(140, $shop->creditBalance()));
        $wallet = (int) ($brief['wallet_paise'] ?? 0);
        $languages = array_values(array_intersect($brief['languages'] ?? $shop->languages ?? ['en'], array_keys(Playbooks::LANGUAGES))) ?: ['en'];
        $from = $month->isSameMonth(now()) ? CarbonImmutable::tomorrow() : $month;
        $occasions = $this->starter->relevantOccasions($shop, $from, $month->endOfMonth());

        return DB::transaction(function () use ($shop, $brief, $month, $budget, $wallet, $languages, $from, $occasions): Plan {
            $version = (int) $shop->plans()->whereDate('month', $month)->max('version') + 1;
            $plan = $shop->plans()->create([
                'month' => $month,
                'status' => 'review',
                'brief' => $brief,
                'credits_budget' => 0,
                'wallet_budget_paise' => $wallet,
                'version' => $version,
            ]);

            $spent = 0;
            $count = 0;
            for ($day = $from; $day->lte($month->endOfMonth()); $day = $day->addDay()) {
                if ($this->starter->isWeeklyOff($shop, $day)) {
                    continue;
                }
                $occasion = $occasions->first(fn (Occasion $o): bool => $o->starts_on->isSameDay($day));
                $type = $occasion ? 'reel' : self::WEEK[$day->dayOfWeekIso];
                $cost = Playbooks::FORMATS[$type]['credits'];
                if ($spent + $cost > $budget) {
                    $type = 'status';
                    $cost = 0;
                }
                $stage = self::FUNNEL[$count % 4 === 3 ? 3 : $count % 3];
                $angle = $occasion ? 'festival' : $stage['angles'][$day->day % count($stage['angles'])];
                $language = $languages[$day->day % count($languages)];
                $copy = $this->writer->write($shop, $angle, $language, ['occasion' => $occasion], $day->day % 2);
                $format = Playbooks::FORMATS[$type];

                $plan->items()->create([
                    'shop_id' => $shop->id,
                    'occasion_id' => $occasion?->id,
                    'type' => $type,
                    'angle' => $copy['angle'],
                    'stage' => $stage['key'],
                    'language' => $language,
                    'title' => $occasion?->name ?? ucfirst($stage['label']).' · '.$format['label'],
                    'hook' => $copy['hook'],
                    'subline' => $copy['subline'],
                    'caption' => $copy['caption'],
                    'cta' => $copy['cta'],
                    'terms' => $copy['terms'],
                    'ratio' => $format['ratio'],
                    'theme' => $occasion ? 'festive' : 'brand',
                    'photo_slot' => $occasion?->photo_slot ?? "{$shop->category}-product",
                    'status' => 'draft',
                    'source' => 'plan',
                    'scheduled_at' => $day->setTime(in_array($type, ['reel', 'post'], true) ? 19 : 9, $type === 'reel' ? 30 : 0),
                    'channels' => $type === 'whatsapp' ? ['broadcast'] : ['whatsapp_status', 'instagram', 'facebook'],
                    'credits' => $cost,
                    'trust_score' => $copy['trust_score'],
                    'trust_checks' => $copy['trust_checks'],
                ]);
                $spent += $cost;
                $count++;
            }

            $plan->update(['credits_budget' => $spent]);
            $plan->update(['strategy' => $this->strategy($shop, $brief, $plan, $occasions->all(), $wallet)]);

            return $plan->load('items');
        });
    }

    /**
     * @param  array<string, mixed>  $brief
     * @param  list<Occasion>  $occasions
     * @return array<string, mixed>
     */
    private function strategy(Shop $shop, array $brief, Plan $plan, array $occasions, int $walletPaise): array
    {
        $items = $plan->items()->get();
        $today = CarbonImmutable::today();
        $insights = $this->metrics->insights($shop, $today->subDays(59), $today);
        $now = (int) ($brief['current_enquiries'] ?? max(1, $this->metrics->totals($shop, $today->subDays(29), $today)['chats']));
        $target = (int) ($brief['target'] ?? round($now * 1.5));
        $lapsed = $shop->customers()->where('stage', 'lapsed')->count();
        $consent = $shop->customers()->where('whatsapp_consent', true)->count();
        $adPaise = (int) round($walletPaise * 0.8);
        $reach = $consent * 6 + $items->count() * 350 + intdiv($adPaise, 100) * 8;

        return [
            'goal' => ['label' => $brief['goal_label'] ?? 'WhatsApp enquiries', 'target' => $target, 'today' => $now],
            'kpis' => [
                ['label' => 'Goal', 'value' => $target, 'note' => "from ~{$now} today"],
                ['label' => 'People reached', 'value' => $reach, 'note' => 'Status, Instagram, ads'],
                ['label' => 'Pieces', 'value' => $items->count(), 'note' => $items->where('credits', 0)->count().' free'],
                ['label' => 'Credits', 'value' => $plan->credits_budget, 'note' => 'of '.((int) ($brief['credits'] ?? 0) ?: $plan->credits_budget).' set aside'],
                ['label' => 'Regulars back', 'value' => (int) round($lapsed * 0.6), 'note' => "of {$lapsed} lapsed"],
            ],
            'reasons' => array_values(array_filter([
                $insights['best_hook'] ? ['title' => 'Lead with what already worked', 'detail' => "Your best piece lately was “{$insights['best_hook']}”. The buying stage reuses that angle."] : null,
                $walletPaise > 0 ? ['title' => 'Pay for chats, not likes', 'detail' => 'Ad money goes to click-to-WhatsApp ads within '.($shop->delivery_km ?: 3).' km of the shop.'] : ['title' => 'No ad money this month', 'detail' => 'The plan leans on Status, Google and your customer list.'],
                $occasions ? ['title' => 'Peak on the big days', 'detail' => implode(', ', array_map(fn (Occasion $o): string => $o->name.' ('.$o->starts_on->format('j M').')', array_slice($occasions, 0, 3))).' get reels; quieter days get trust and tips.'] : null,
                $lapsed > 0 ? ['title' => "Win back the {$lapsed} regulars first", 'detail' => 'Personal offers to people who already trust you are the cheapest enquiries.'] : null,
            ])),
            'funnel' => array_map(fn (array $stage): array => [
                'key' => $stage['key'],
                'name' => $stage['name'],
                'label' => $stage['label'],
                'share' => $stage['share'],
                'count' => $items->where('stage', $stage['key'])->count(),
                'formats' => $stage['formats'],
            ], self::FUNNEL),
            'wallet_split' => $walletPaise > 0 ? [
                'meta_ads_paise' => $adPaise,
                'whatsapp_paise' => (int) round($walletPaise * 0.15),
                'sms_paise' => (int) round($walletPaise * 0.05),
            ] : null,
        ];
    }
}
