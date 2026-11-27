<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use App\Services\CopyWriter;
use App\Services\CreditService;
use App\Services\PlanBuilder;
use App\Services\Playbooks;
use App\Services\ShopSetup;
use App\Services\StarterContent;
use App\Services\WalletService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Two working demo shops with 90 days of history. Every number on Home, Results and Customers is
 * derived from these rows, so the screens agree with each other. Sign in with the email below
 * and the code shown on the sign-in page (local only).
 *
 * Phone numbers follow 9X00000YYY so they cannot belong to a real person; emails use .test.
 */
class DemoShopSeeder extends Seeder
{
    private const FIRST_F = ['Priya', 'Sneha', 'Pooja', 'Aarti', 'Snehal', 'Kavita', 'Manisha', 'Swati', 'Rupali', 'Ashwini', 'Pallavi', 'Vaishali', 'Neha', 'Shraddha', 'Komal', 'Madhuri', 'Sunanda', 'Jyoti', 'Rohini', 'Archana'];

    private const FIRST_M = ['Amol', 'Sachin', 'Rahul', 'Prashant', 'Nilesh', 'Mahesh', 'Sandeep', 'Ganesh', 'Tushar', 'Vikas', 'Akshay', 'Omkar', 'Swapnil', 'Yogesh', 'Kiran', 'Sagar', 'Nitin', 'Rohan', 'Pratik', 'Aniket'];

    private const LAST = ['Patil', 'Jadhav', 'Pawar', 'Deshmukh', 'Kulkarni', 'Shinde', 'More', 'Gaikwad', 'Joshi', 'Kale', 'Bhosale', 'Chavan', 'Wagh', 'Sonawane', 'Deshpande', 'Thorat', 'Salunkhe', 'Mahajan', 'Gokhale', 'Nikam'];

    private const CHANNEL_SHARE = [
        // channel => [views share, chats share, calls share, qr share]
        'whatsapp_status' => [0.34, 0.30, 0.20, 0.0],
        'instagram' => [0.26, 0.14, 0.05, 0.0],
        'facebook' => [0.10, 0.04, 0.05, 0.0],
        'meta_ad' => [0.20, 0.30, 0.20, 0.0],
        'google' => [0.08, 0.08, 0.35, 0.0],
        'broadcast' => [0.02, 0.14, 0.05, 0.0],
        'walk_in' => [0.0, 0.0, 0.10, 1.0],
    ];

    public function __construct(
        private CreditService $credits,
        private WalletService $wallet,
        private ShopSetup $setup,
        private StarterContent $starter,
        private CopyWriter $writer,
        private PlanBuilder $plans,
    ) {}

    public function run(): void
    {
        mt_srand(2014);

        $this->seedShop([
            'owner' => ['name' => 'Ramesh Pawar', 'email' => 'ramesh@saimobile.test', 'phone' => '9820000147'],
            'shop' => [
                'name' => 'Sai Mobile Point', 'name_local' => 'साई मोबाईल पॉइंट', 'category' => 'mobile', 'city' => 'Nashik', 'area' => 'College Road',
                'address' => 'Shop 4, Patil Plaza, College Road, Nashik 422005', 'landmark' => 'Near BYK College bus stop', 'pincode' => '422005',
                'whatsapp' => '+91 98200 00147', 'phone' => '+91 98200 00148', 'opens_at' => '10:00', 'closes_at' => '21:30', 'weekly_off' => 'tue',
                'languages' => ['en', 'hi', 'mr'], 'since_year' => 2014, 'delivery_km' => 3, 'rating' => 4.6, 'reviews_count' => 312,
                'goal' => 'More WhatsApp orders', 'audience' => ['types' => ['College students', 'Families'], 'hubs' => ['College', 'Bus stand']],
                'brand' => ['colors' => ['#1D3A8A', '#FFD23F', '#FFFFFF'], 'tagline' => 'Sai Mobile - bharose ka naam', 'feel' => 'Bold'],
                'package_id' => 'dukaan',
            ],
            'products' => [['Vivo 5G phones', 1000000, '₹10,000 – 20,000'], ['Screen replacement', 120000, '₹1,200 – 4,500'], ['Earbuds', 99900, '₹999 – 2,499'], ['Covers & glass', 14900, '₹149 – 499'], ['Power banks', 80000, '₹800 – 2,000']],
            'staff' => [['Amol Shinde', '9820000215', 'staff']],
            'customers' => ['total' => 212, 'consent' => 148, 'at_risk' => 27, 'lapsed' => 14, 'new' => 21, 'prefix' => '98'],
            'purchases' => ['5G phone', 'Screen repair', 'Earbuds', 'Phone cover', 'Fast charger', 'Exchange'],
            'credits_used' => 54,
            'wallet' => [[300000, 'UPI top-up'], [-60000, 'meta_ad'], [-48000, 'meta_ad'], [-12728, 'whatsapp'], [-55272, 'meta_ad']],
            'chats_per_week' => 38,
            'campaigns' => [
                ['name' => 'Shaadi Exchange Mela', 'goal' => 'Exchange enquiries in wedding season', 'offer' => 'Purana phone laao, ₹2,000 extra pao', 'status' => 'live', 'start' => -6, 'end' => 18, 'budget' => 300000, 'spent' => 124000, 'reach' => 8400, 'enquiries' => 46, 'visits' => 19, 'sales' => 11, 'occasion' => 'wedding-season'],
                ['name' => 'Diwali Dhamaka', 'goal' => 'Festival sales', 'offer' => '0% EMI on 5G phones', 'status' => 'ended', 'start' => -45, 'end' => -30, 'budget' => 250000, 'spent' => 250000, 'reach' => 14200, 'enquiries' => 81, 'visits' => 37, 'sales' => 24, 'occasion' => null],
            ],
            'workflows_active' => [0, 1, 2],
            'connected' => ['whatsapp' => '+91 98200 00147', 'instagram' => '@saimobilepoint', 'facebook' => 'Sai Mobile Point', 'google' => 'Sai Mobile Point, College Road', 'meta_ads' => 'Sai Mobile Point ads'],
        ]);

        $this->seedShop([
            'owner' => ['name' => 'Sunita Kale', 'email' => 'sunita@kalesweets.test', 'phone' => '9970000311'],
            'shop' => [
                'name' => 'Kale Sweets', 'name_local' => 'काळे स्वीट्स', 'category' => 'sweets', 'city' => 'Pune', 'area' => 'Sadashiv Peth',
                'address' => '612 Sadashiv Peth, near Bhikardas Maruti, Pune 411030', 'landmark' => 'Opposite Bhikardas Maruti temple', 'pincode' => '411030',
                'whatsapp' => '+91 99700 00311', 'phone' => '+91 99700 00312', 'opens_at' => '08:00', 'closes_at' => '21:00', 'weekly_off' => null,
                'languages' => ['mr', 'en'], 'since_year' => 1998, 'delivery_km' => 5, 'rating' => 4.7, 'reviews_count' => 864,
                'goal' => 'More walk-ins', 'audience' => ['types' => ['Families', 'Senior citizens'], 'hubs' => ['Temple', 'Market']],
                'brand' => ['colors' => ['#7A1414', '#F2A007', '#FFF8E7'], 'tagline' => 'Kale Sweets - 1998 pasun godva', 'feel' => 'Classic'],
                'package_id' => 'badhao',
            ],
            'products' => [['Ukadiche modak', 3000, '₹30 each'], ['Kaju katli', 120000, '₹1,200 / kg'], ['Puran poli', 4000, '₹40 each'], ['Bakarwadi', 48000, '₹480 / kg'], ['Festival gift box', 60000, '₹600 – 1,500']],
            'staff' => [['Meera Kale', '9970000318', 'manager'], ['Datta More', '9970000325', 'staff']],
            'customers' => ['total' => 340, 'consent' => 261, 'at_risk' => 31, 'lapsed' => 22, 'new' => 36, 'prefix' => '99'],
            'purchases' => ['Modak box', 'Kaju katli 1 kg', 'Puran poli', 'Bakarwadi', 'Gift box', 'Pedha'],
            'credits_used' => 162,
            'wallet' => [[500000, 'UPI top-up'], [-90000, 'meta_ad'], [-22446, 'whatsapp'], [-70000, 'meta_ad']],
            'chats_per_week' => 64,
            'campaigns' => [
                ['name' => 'Winter dink laddoo', 'goal' => 'Pre-orders for winter laddoos', 'offer' => '1 kg box par 250 g free', 'status' => 'live', 'start' => -3, 'end' => 25, 'budget' => 200000, 'spent' => 70000, 'reach' => 6100, 'enquiries' => 58, 'visits' => 31, 'sales' => 26, 'occasion' => null],
            ],
            'workflows_active' => [0, 1, 3],
            'connected' => ['whatsapp' => '+91 99700 00311', 'instagram' => '@kalesweetspune', 'google' => 'Kale Sweets, Sadashiv Peth'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function seedShop(array $c): void
    {
        if (User::query()->where('email', $c['owner']['email'])->exists()) {
            $this->command?->warn("{$c['owner']['email']} already exists; skipped.");

            return;
        }

        DB::transaction(function () use ($c): void {
            $today = CarbonImmutable::today();
            $periodStart = $today->startOfMonth();

            $user = User::query()->create($c['owner'] + ['locale' => 'en', 'email_verified_at' => now()]);
            $shop = $this->setup->createFor($user, $c['shop']['name'], $c['shop']['whatsapp']);
            $shop->update($c['shop'] + ['setup_step' => 10]);

            foreach ($c['products'] as $i => [$name, $price, $label]) {
                $shop->products()->create(['name' => $name, 'price_paise' => $price, 'offer' => $label, 'photo_slot' => "{$shop->category}-product-".($i + 1), 'is_hero' => $i === 0]);
            }
            foreach ($c['staff'] as [$name, $phone, $role]) {
                $shop->members()->create(['name' => $name, 'phone' => $phone, 'role' => $role, 'invited_at' => $today->subDays(40), 'joined_at' => $today->subDays(39), 'permissions' => $role === 'manager' ? ['approve', 'post', 'customers'] : ['post']]);
            }

            // History first (posted content, metrics, customers), then setup completion makes the
            // subscription, credit grant, workflows and the next 14 days.
            $this->history($shop, $c, $today);
            $this->customers($shop, $c, $today);

            $this->travel($periodStart->subMonth()->setTime(9, 0), function () use ($shop): void {
                $this->setup->complete($shop);
            });
            // complete() made 14 days from last month; replace them with the real upcoming two weeks.
            $shop->contentItems()->where('source', 'auto')->whereIn('status', ['draft', 'ready'])->delete();
            $shop->subscription()->update(['period_start' => $periodStart, 'period_end' => $periodStart->addMonth()]);
            $shop->creditTransactions()->delete();
            $package = $shop->package;
            $this->travel($periodStart->setTime(0, 5), fn () => $this->credits->grant($shop, $package->credits, 'plan_grant', "{$package->name} plan · monthly credits"));
            $this->spendHistory($shop, $c['credits_used'], $periodStart, $today);
            $this->walletHistory($shop, $c['wallet'], $today);

            $this->starter->generate($shop, 14, $today);
            $shop->workflows()->get()->each(function ($workflow, int $i) use ($c, $today): void {
                $active = in_array($i, $c['workflows_active'], true);
                $sent = $active ? mt_rand(30, 140) : 0;
                $workflow->update(['active' => $active, 'sent_count' => $sent, 'reply_count' => (int) round($sent * mt_rand(18, 34) / 100), 'last_run_at' => $active ? $today->subHours(mt_rand(2, 40)) : null]);
            });
            foreach ($c['connected'] as $provider => $account) {
                $shop->connections()->where('provider', $provider)->update(['status' => 'connected', 'account' => $account, 'connected_at' => $today->subDays(60)]);
            }

            $this->campaignsAndMessages($shop, $c, $today);
            $this->plans->build($shop, ['month' => $today->addMonthNoOverflow()->format('Y-m'), 'goal' => 'leads', 'goal_label' => 'WhatsApp enquiries', 'credits' => min(90, $shop->creditBalance()), 'wallet_paise' => 300000]);

            $shop->feed()->delete();
            $feed = [
                ['ready', 'Today’s 3 posts are ready', 'Share them on Status in one tap.', '/app', 1],
                ['plan', $today->addMonthNoOverflow()->format('F').' plan is drafted', 'Review it before '.$today->endOfMonth()->format('j M').'.', '/app/studio/plan', 5],
                ['customers', $c['customers']['lapsed'].' regulars stopped coming', 'Send each a personal offer.', '/app/customers?stage=lapsed', 26],
                ['results', 'Last week: '.$c['chats_per_week'].' chats', 'Best day was Saturday.', '/app/results', 50],
            ];
            foreach ($feed as [$kind, $title, $body, $link, $hoursAgo]) {
                $shop->feed()->create(['kind' => $kind, 'title' => $title, 'body' => $body, 'link' => $link, 'created_at' => now()->subHours($hoursAgo)]);
            }
        });
    }

    /**
     * 90 days of per-channel metrics and about one posted piece a day for 60 days.
     *
     * @param  array<string, mixed>  $c
     */
    private function history(Shop $shop, array $c, CarbonImmutable $today): void
    {
        $weekly = $c['chats_per_week'];
        $weekdayFactor = [1 => 0.8, 2 => 0.6, 3 => 0.8, 4 => 0.9, 5 => 1.0, 6 => 1.9, 7 => 1.1]; // Sat peaks
        $rows = [];

        for ($back = 89; $back >= 0; $back--) {
            $date = $today->subDays($back);
            // Growth: 90 days ago the shop got about 55% of today's chats.
            $trend = 0.55 + 0.45 * (89 - $back) / 89;
            $chats = max(1, (int) round($weekly / 7.1 * $weekdayFactor[$date->dayOfWeekIso] * $trend * mt_rand(85, 115) / 100));
            $views = (int) round($chats * mt_rand(55, 68));
            $calls = (int) round($chats * mt_rand(26, 36) / 100);
            $qr = (int) round($chats * mt_rand(18, 30) / 100);

            $channels = array_keys(self::CHANNEL_SHARE);
            $split = fn (int $total, int $column): array => $this->split($total, array_column(self::CHANNEL_SHARE, $column));
            [$v, $ch, $ca, $q] = [$split($views, 0), $split($chats, 1), $split($calls, 2), $split($qr, 3)];

            foreach ($channels as $n => $channel) {
                $rows[] = [
                    'shop_id' => $shop->id,
                    'date' => $date->toDateString(),
                    'channel' => $channel,
                    'views' => $v[$n],
                    'chats' => $ch[$n],
                    'calls' => $ca[$n],
                    'qr_scans' => $q[$n],
                    'offer_codes' => $channel === 'walk_in' ? (int) round($qr * 0.4) : 0,
                    'ad_spend_paise' => $channel === 'meta_ad' ? $ch[$n] * mt_rand(2800, 3600) : 0,
                ];
            }
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('daily_metrics')->insert($chunk);
        }

        $types = ['status', 'post', 'status', 'reel', 'post', 'carousel', 'status'];
        $angles = ['offer', 'trust', 'local', 'offer', 'newness', 'tip', 'offer'];
        $languages = $shop->languages;
        $metrics = $shop->dailyMetrics()->get()->groupBy(fn ($m) => $m->date->toDateString());

        for ($back = 60; $back >= 1; $back--) {
            $date = $today->subDays($back);
            $k = $back % 7;
            $copy = $this->writer->write($shop, $angles[$k], $languages[$back % count($languages)], [], $back % 2);
            $day = $metrics->get($date->toDateString(), collect());
            // A posted piece carries the organic share of its day's chats (ads are counted apart).
            $share = in_array($types[$k], ['reel', 'post'], true) ? 0.5 : 0.3;
            $type = $types[$k];

            $shop->contentItems()->create([
                'type' => $type,
                'angle' => $copy['angle'],
                'stage' => $copy['angle'] === 'offer' ? 'buying' : ($copy['angle'] === 'trust' ? 'trust' : 'awareness'),
                'language' => $copy['language'],
                'title' => ucfirst($copy['angle']).' · '.Playbooks::FORMATS[$type]['label'],
                'hook' => $copy['hook'],
                'subline' => $copy['subline'],
                'caption' => $copy['caption'],
                'cta' => $copy['cta'],
                'terms' => $copy['terms'],
                'ratio' => Playbooks::FORMATS[$type]['ratio'],
                'theme' => 'brand',
                'photo_slot' => "{$shop->category}-".($copy['angle'] === 'trust' ? 'owner' : 'product'),
                'status' => 'posted',
                'source' => $back % 3 === 0 ? 'manual' : 'auto',
                'scheduled_at' => $date->setTime($type === 'status' ? 9 : 19, $type === 'reel' ? 30 : 0),
                'posted_at' => $date->setTime($type === 'status' ? 9 : 19, $type === 'reel' ? 31 : 2),
                'channels' => $type === 'status' ? ['whatsapp_status'] : ['whatsapp_status', 'instagram', 'facebook'],
                'credits' => 0,
                'trust_score' => $copy['trust_score'],
                'trust_checks' => $copy['trust_checks'],
                'views' => (int) round($day->whereNotIn('channel', ['meta_ad', 'walk_in'])->sum('views') * $share),
                'chats' => (int) round($day->whereNotIn('channel', ['meta_ad', 'walk_in'])->sum('chats') * $share * ($copy['angle'] === 'offer' ? 1.6 : 1)),
                'calls' => (int) round($day->sum('calls') * $share),
                'qr_scans' => (int) round($day->sum('qr_scans') * $share),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function customers(Shop $shop, array $c, CarbonImmutable $today): void
    {
        $spec = $c['customers'];
        $areas = $shop->city === 'Pune'
            ? ['Sadashiv Peth', 'Narayan Peth', 'Shaniwar Peth', 'Kothrud', 'Deccan', 'Tilak Road']
            : ['College Road', 'Gangapur Road', 'Panchavati', 'CIDCO', 'Indira Nagar', 'Satpur'];
        $sources = ['walk_in', 'walk_in', 'walk_in', 'whatsapp', 'instagram', 'meta_ad', 'referral', 'google'];
        $stages = array_merge(
            array_fill(0, $spec['at_risk'], 'at_risk'),
            array_fill(0, $spec['lapsed'], 'lapsed'),
            array_fill(0, $spec['new'], 'new'),
            array_fill(0, $spec['total'] - $spec['at_risk'] - $spec['lapsed'] - $spec['new'], 'regular'),
        );
        // Lapsed regulars all agreed to WhatsApp, so win-back works on them; the rest is spread.
        $consentLeft = $spec['consent'] - $spec['lapsed'];
        $others = $spec['total'] - $spec['lapsed'];
        $rows = [];

        foreach ($stages as $i => $stage) {
            $female = $i % 2 === 0;
            $first = ($female ? self::FIRST_F : self::FIRST_M)[mt_rand(0, 19)];
            $daysSince = match ($stage) {
                'new' => mt_rand(0, 25),
                'regular' => mt_rand(1, 30),
                'at_risk' => mt_rand(31, 44),
                'lapsed' => mt_rand(46, 120),
            };
            $visits = $stage === 'new' ? 1 : mt_rand(3, 18);
            $consent = $stage === 'lapsed' || ($consentLeft > 0 && mt_rand(1, $others) <= $consentLeft);
            if ($consent && $stage !== 'lapsed') {
                $consentLeft--;
            }
            if ($stage !== 'lapsed') {
                $others--;
            }
            $last = $today->subDays($daysSince);

            $rows[] = [
                'shop_id' => $shop->id,
                'name' => $first.' '.self::LAST[mt_rand(0, 19)],
                'phone' => '9'.substr($spec['prefix'], 1, 1).'00000'.str_pad((string) (100 + $i), 3, '0', STR_PAD_LEFT),
                'area' => $areas[mt_rand(0, 5)],
                'gender' => $female ? 'f' : 'm',
                'birthday' => mt_rand(1, 10) <= 4 ? $today->subYears(mt_rand(18, 62))->subDays(mt_rand(0, 364))->toDateString() : null,
                'whatsapp_consent' => $consent,
                'consent_at' => $consent ? $last->subDays(mt_rand(0, 200)) : null,
                'stage' => $stage,
                'visits' => $visits,
                'spend_paise' => $visits * mt_rand(300, 2600) * 100,
                'first_visit_on' => $last->subDays($stage === 'new' ? 0 : mt_rand(60, 900))->toDateString(),
                'last_visit_on' => $last->toDateString(),
                'last_purchase' => $c['purchases'][mt_rand(0, count($c['purchases']) - 1)],
                'source' => $sources[mt_rand(0, 7)],
                'photo_slot' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('customers')->insert($chunk);
        }
    }

    private function spendHistory(Shop $shop, int $used, CarbonImmutable $from, CarbonImmutable $today): void
    {
        $items = [[15, '15 s reel · Shaadi offer'], [6, 'AI-scene ad · Exchange'], [5, 'Carousel · 5 checks'], [2, 'Post · Customer story'], [3, 'WhatsApp offer · Weekend'], [15, '15 s reel · Inside the shop'], [6, 'AI-scene ad · Gift'], [2, 'Post · New arrival']];
        $days = max(1, (int) $from->diffInDays($today));
        $i = 0;
        while ($used > 0) {
            [$cost, $label] = $items[$i % count($items)];
            $cost = min($cost, $used);
            $at = $from->addDays((int) floor($days * ($i + 1) / (count($items) + 2)))->setTime(11 + $i % 8, 15);
            $this->travel($at, fn () => $this->credits->spend($shop, $cost, $label));
            $used -= $cost;
            $i++;
        }
    }

    /**
     * @param  list<array{0: int, 1: string}>  $entries
     */
    private function walletHistory(Shop $shop, array $entries, CarbonImmutable $today): void
    {
        foreach ($entries as $i => [$amount, $what]) {
            $at = $today->subDays(28 - $i * 6)->setTime(12, 0);
            $this->travel($at, fn () => $amount > 0
                ? $this->wallet->topUp($shop, $amount, 'UPI-DEMO'.str_pad((string) ($shop->id * 100 + $i), 6, '0', STR_PAD_LEFT), $what)
                : $this->wallet->charge($shop, -$amount, $what, $what === 'meta_ad' ? 'Meta ad · click-to-WhatsApp, 3 km' : 'WhatsApp broadcast'));
        }
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function campaignsAndMessages(Shop $shop, array $c, CarbonImmutable $today): void
    {
        foreach ($c['campaigns'] as $spec) {
            $occasionId = $spec['occasion'] ? DB::table('occasions')->where('slug', $spec['occasion'])->value('id') : null;
            $campaign = $shop->campaigns()->create([
                'occasion_id' => $occasionId, 'name' => $spec['name'], 'goal' => $spec['goal'], 'offer' => $spec['offer'], 'status' => $spec['status'],
                'starts_on' => $today->addDays($spec['start']), 'ends_on' => $today->addDays($spec['end']),
                'budget_paise' => $spec['budget'], 'spent_paise' => $spec['spent'],
                'reach' => $spec['reach'], 'enquiries' => $spec['enquiries'], 'visits' => $spec['visits'], 'sales' => $spec['sales'],
            ]);
            if ($spec['status'] === 'live') {
                $shop->contentItems()->where('status', 'posted')->where('angle', 'offer')->where('posted_at', '>=', $campaign->starts_on)->update(['campaign_id' => $campaign->id]);
                $shop->contentItems()->where('status', 'draft')->where('angle', 'offer')->where('scheduled_at', '<=', $campaign->ends_on->endOfDay())->update(['campaign_id' => $campaign->id]);
            }
        }

        $live = $shop->campaigns()->where('status', 'live')->first();
        $consent = $shop->customers()->where('whatsapp_consent', true)->count();
        $regulars = $shop->customers()->where('whatsapp_consent', true)->where('stage', 'regular')->count();
        $book = Playbooks::category($shop->category);
        $messages = [
            ['whatsapp', 'Weekend offer · all customers', $book['offer']['en'].'. '.$book['cta']['en'].'.', 'sent', -5, $consent, ['stage' => 'all']],
            ['whatsapp', 'Thank-you to regulars', 'Thank you for trusting '.$shop->name.'! A small gift is waiting for you this week.', 'sent', -2, $regulars, ['stage' => 'regular']],
            ['sms', 'Last day reminder', $book['offer']['en'].' — last day Sunday. '.$shop->name.', '.$shop->area.'.', 'scheduled', 2, $shop->customers()->count(), ['stage' => 'all']],
            ['whatsapp', 'New stock announcement', $book['newness']['en'].'. Aaj hi dekhne aayein!', 'draft', null, $consent, ['stage' => 'all']],
        ];
        foreach ($messages as [$channel, $name, $body, $status, $day, $recipients, $audience]) {
            $sent = $status === 'sent';
            $shop->messages()->create([
                'campaign_id' => $live?->id, 'channel' => $channel, 'name' => $name, 'template' => 'marketing', 'language' => 'en', 'body' => $body,
                'buttons' => $channel === 'whatsapp' ? ['Call shop', 'Get directions'] : null, 'audience' => $audience, 'status' => $status,
                'scheduled_at' => $day !== null ? $today->addDays($day)->setTime(18, 0) : null, 'sent_at' => $sent ? $today->addDays($day)->setTime(18, 1) : null,
                'recipients' => $recipients, 'delivered' => $sent ? (int) round($recipients * 0.96) : 0, 'read' => $sent ? (int) round($recipients * 0.78) : 0,
                'replies' => $sent ? (int) round($recipients * 0.09) : 0, 'cost_paise' => $this->wallet->costPaise($channel, $recipients),
            ]);
        }
    }

    /**
     * Splits a whole number by shares so the parts add back to the total (largest remainder).
     *
     * @param  list<float>  $shares
     * @return list<int>
     */
    private function split(int $total, array $shares): array
    {
        $raw = array_map(fn (float $s): float => $total * $s, $shares);
        $parts = array_map(fn (float $r): int => (int) floor($r), $raw);
        $rest = $total - array_sum($parts);
        $order = array_keys($raw);
        usort($order, fn (int $a, int $b): int => ($raw[$b] - floor($raw[$b])) <=> ($raw[$a] - floor($raw[$a])));
        foreach (array_slice($order, 0, max(0, $rest)) as $i) {
            $parts[$i]++;
        }

        return $parts;
    }

    /**
     * Runs a ledger write as if it happened at the given moment.
     */
    private function travel(CarbonImmutable $at, callable $callback): mixed
    {
        CarbonImmutable::setTestNow($at);
        Carbon::setTestNow($at);
        try {
            return $callback();
        } finally {
            CarbonImmutable::setTestNow();
            Carbon::setTestNow();
        }
    }
}
