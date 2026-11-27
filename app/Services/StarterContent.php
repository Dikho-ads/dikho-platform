<?php

namespace App\Services;

use App\Models\ContentItem;
use App\Models\Occasion;
use App\Models\Shop;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Makes a shop's next days of content from its category playbook and the occasions around it.
 * Daily posts and statuses are free; formats that cost credits are charged when approved.
 */
class StarterContent
{
    private const ROTATION = [
        ['angle' => 'offer', 'type' => 'status', 'time' => '09:00'],
        ['angle' => 'trust', 'type' => 'post', 'time' => '18:30'],
        ['angle' => 'local', 'type' => 'status', 'time' => '09:00'],
        ['angle' => 'newness', 'type' => 'post', 'time' => '19:00'],
        ['angle' => 'tip', 'type' => 'carousel', 'time' => '18:00'],
        ['angle' => 'offer', 'type' => 'reel', 'time' => '19:30'],
        ['angle' => 'local', 'type' => 'google_post', 'time' => '10:00'],
    ];

    public function __construct(private CopyWriter $writer) {}

    /**
     * @return Collection<int, ContentItem>
     */
    public function generate(Shop $shop, int $days = 14, ?CarbonImmutable $from = null): Collection
    {
        $from = ($from ?? CarbonImmutable::now())->startOfDay();
        $languages = $shop->languages ?: ['en'];
        $occasions = $this->relevantOccasions($shop, $from, $from->addDays($days));
        $items = collect();

        for ($day = 0; $day < $days; $day++) {
            $date = $from->addDays($day);
            // Nothing goes out on the shop's weekly off, except the first day's ready posts.
            if ($day > 0 && $this->isWeeklyOff($shop, $date)) {
                continue;
            }
            $occasion = $occasions->first(fn (Occasion $o): bool => $o->starts_on->isSameDay($date));
            $slot = self::ROTATION[($date->dayOfYear) % count(self::ROTATION)];
            $language = $languages[$day % count($languages)];
            $angle = $occasion ? 'festival' : $slot['angle'];

            $items->push($this->make($shop, $date, $slot['time'], $slot['type'], $angle, $language, $occasion, $day === 0 ? 'ready' : 'draft'));

            if ($day === 0) {
                // Three ready posts on the first day, so a new shop has something to share at once.
                $items->push($this->make($shop, $date, '13:00', 'post', 'trust', $languages[0], null, 'ready', 1));
                $items->push($this->make($shop, $date, '19:30', 'status', 'local', $languages[0], null, 'ready', 1));
            }
        }

        return $items;
    }

    /**
     * Occasions in the window that matter for this shop's category and region.
     *
     * @return Collection<int, Occasion>
     */
    public function relevantOccasions(Shop $shop, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Occasion::query()
            ->whereDate('starts_on', '<=', $to)
            ->whereDate('ends_on', '>=', $from)
            ->whereIn('region', $this->regions($shop))
            ->orderBy('starts_on')
            ->get()
            ->filter(fn (Occasion $o): bool => ($o->weights[$shop->category] ?? 0.5) >= 0.5)
            ->values();
    }

    public function isWeeklyOff(Shop $shop, CarbonImmutable $date): bool
    {
        return $shop->weekly_off !== null && strtolower($date->format('D')) === $shop->weekly_off;
    }

    /**
     * @return list<string>
     */
    public function regions(Shop $shop): array
    {
        return array_values(array_filter(['india', 'maharashtra', $shop->city ? str($shop->city)->slug()->toString() : null]));
    }

    private function make(Shop $shop, CarbonImmutable $date, string $time, string $type, string $angle, string $language, ?Occasion $occasion, string $status, int $variant = 0): ContentItem
    {
        $copy = $this->writer->write($shop, $angle, $language, ['occasion' => $occasion], $variant);
        $format = Playbooks::FORMATS[$type];
        $free = in_array($type, ['post', 'status', 'google_post'], true);

        return $shop->contentItems()->create([
            'occasion_id' => $occasion?->id,
            'type' => $type,
            'angle' => $copy['angle'],
            'stage' => match ($copy['angle']) {
                'trust', 'tip' => 'trust',
                'offer' => 'buying',
                default => 'awareness',
            },
            'language' => $language,
            'title' => $occasion ? $occasion->name : ucfirst($copy['angle']).' · '.$format['label'],
            'hook' => $copy['hook'],
            'subline' => $copy['subline'],
            'caption' => $copy['caption'],
            'cta' => $copy['cta'],
            'terms' => $copy['terms'],
            'ratio' => $format['ratio'],
            'theme' => $occasion ? 'festive' : 'brand',
            'photo_slot' => $occasion?->photo_slot ?? "{$shop->category}-".($copy['angle'] === 'trust' ? 'owner' : 'product'),
            'status' => $status,
            'source' => 'auto',
            'scheduled_at' => $date->setTimeFromTimeString($time),
            'channels' => match ($type) {
                'status' => ['whatsapp_status'],
                'google_post' => ['google'],
                'reel' => ['instagram', 'whatsapp_status'],
                default => ['whatsapp_status', 'instagram', 'facebook'],
            },
            'credits' => $free ? 0 : $format['credits'],
            'trust_score' => $copy['trust_score'],
            'trust_checks' => $copy['trust_checks'],
        ]);
    }
}
