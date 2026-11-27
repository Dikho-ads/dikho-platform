<?php

namespace App\Services;

use App\Models\Occasion;
use App\Models\Shop;

/**
 * The no-AI copy engine. Every piece follows the lead formula: hook, offer, trust, CTA with the
 * shop's WhatsApp number, deadline. An AI writer can later fill the same fields.
 */
class CopyWriter
{
    /**
     * @param  array{offer?: string|null, occasion?: Occasion|null, deadline?: string|null}  $context
     * @return array{angle: string, language: string, hook: string, subline: string, caption: string, cta: string, terms: string, why: string, trust_score: int, trust_checks: list<array{key: string, label: string, ok: bool, tip: string}>}
     */
    public function write(Shop $shop, string $angle, string $language, array $context = [], int $variant = 0): array
    {
        $language = array_key_exists($language, Playbooks::LANGUAGES) ? $language : 'en';
        $angle = array_key_exists($angle, Playbooks::ANGLES) ? $angle : 'offer';
        $book = Playbooks::category($shop->category);
        $occasion = $context['occasion'] ?? null;

        if ($angle === 'festival' && $occasion === null) {
            $angle = 'offer';
        }

        $offer = $context['offer'] ?? $book['offer'][$language];
        $vars = [
            '{shop}' => $shop->name,
            '{owner}' => $shop->owner?->firstName() ?? '',
            '{area}' => $shop->area ?: $shop->city ?: '',
            '{city}' => $shop->city ?: '',
            '{years}' => (string) max(1, now()->year - ($shop->since_year ?: now()->year - 1)),
            '{occasion}' => $occasion ? $this->occasionName($occasion, $language) : '',
            '{offer}' => $offer,
            '{local}' => $book['local'][$language],
            '{newness}' => $book['newness'][$language],
            '{tip}' => $book['tip'][$language],
        ];

        $templates = Playbooks::ANGLES[$angle][$language];
        $hook = strtr($templates[$variant % count($templates)], $vars);
        $subline = match ($angle) {
            'offer', 'newness' => $book['terms'][$language],
            'festival', 'trust', 'local' => $offer,
            default => $book['cta'][$language],
        };
        $deadline = $context['deadline'] ?? $this->defaultDeadline($language);
        $terms = $angle === 'tip' ? '' : $deadline;
        $cta = $book['cta'][$language];
        $caption = $this->caption($shop, $hook, $subline, $terms, $occasion);
        [$score, $checks] = $this->trustCheck($shop, $hook, $subline, $terms, $language, $angle);

        return [
            'angle' => $angle,
            'language' => $language,
            'hook' => $hook,
            'subline' => $subline,
            'caption' => $caption,
            'cta' => $cta,
            'terms' => $terms,
            'why' => Playbooks::ANGLE_WHY[$angle],
            'trust_score' => $score,
            'trust_checks' => $checks,
        ];
    }

    /**
     * Three variants for one brief: the asked angle plus two that usually work for this shop.
     *
     * @param  array{offer?: string|null, occasion?: Occasion|null, deadline?: string|null}  $context
     * @return list<array<string, mixed>>
     */
    public function variants(Shop $shop, string $language, array $context = [], ?string $firstAngle = null): array
    {
        $angles = array_values(array_unique(array_filter([
            $firstAngle ?? (isset($context['occasion']) ? 'festival' : 'offer'),
            'trust',
            'local',
            'offer',
        ])));

        return array_map(
            fn (string $angle, int $i): array => ['key' => chr(65 + $i)] + $this->write($shop, $angle, $language, $context),
            array_slice($angles, 0, 3),
            [0, 1, 2],
        );
    }

    public function occasionName(Occasion $occasion, string $language): string
    {
        return match ($language) {
            'hi' => $occasion->name_hi ?: $occasion->name,
            'mr' => $occasion->name_mr ?: $occasion->name,
            default => $occasion->name,
        };
    }

    private function defaultDeadline(string $language): string
    {
        $sunday = now()->next('Sunday');
        $day = $sunday->format('j M');

        return match ($language) {
            'hi' => "रविवार {$day} तक",
            'mr' => "रविवार {$day} पर्यंत",
            default => "Sunday {$day} tak",
        };
    }

    private function caption(Shop $shop, string $hook, string $subline, string $terms, ?Occasion $occasion): string
    {
        $place = trim(implode(', ', array_filter([$shop->area, $shop->city])));
        $lines = array_filter([$hook, $subline, $terms, $place ? "📍 {$shop->name}, {$place}" : null, $shop->whatsapp ? "WhatsApp: {$shop->whatsapp}" : null]);
        $tags = array_filter([
            $this->tag($shop->city ?? ''),
            $this->tag($shop->name),
            $occasion ? $this->tag($occasion->name) : null,
        ]);

        return implode("\n", $lines)."\n\n".implode(' ', $tags);
    }

    private function tag(string $text): ?string
    {
        $clean = preg_replace('/[^\p{L}\p{N}]+/u', '', $text);

        return $clean ? '#'.$clean : null;
    }

    /**
     * Content-quality check before anything goes out. It never promises a number of leads.
     *
     * @return array{0: int, 1: list<array{key: string, label: string, ok: bool, tip: string}>}
     */
    public function trustCheck(Shop $shop, string $hook, string $subline, string $terms, string $language, string $angle): array
    {
        $hasNumber = (bool) preg_match('/(₹|\d|free|फ्री|मोफत)/iu', $hook.' '.$subline);
        $checks = [
            ['key' => 'hook', 'label' => 'Short hook', 'ok' => mb_strlen($hook) <= 60, 'tip' => 'Keep the hook under about 8 words.'],
            ['key' => 'offer', 'label' => 'Clear offer or value', 'ok' => $hasNumber || in_array($angle, ['trust', 'tip', 'festival'], true), 'tip' => 'Say what they get: a price, a % off, or something free.'],
            ['key' => 'cta', 'label' => 'WhatsApp number', 'ok' => strlen(preg_replace('/\D/', '', (string) $shop->whatsapp)) >= 10, 'tip' => 'Add your WhatsApp number in Settings so people can message you.'],
            ['key' => 'deadline', 'label' => 'True deadline', 'ok' => $terms !== '' || $angle === 'tip', 'tip' => 'A last date gives people a reason to message today.'],
            ['key' => 'place', 'label' => 'Shop name and place', 'ok' => filled($shop->name) && filled($shop->area ?: $shop->city), 'tip' => 'Add your area in Settings; people buy from shops they can find.'],
            ['key' => 'language', 'label' => 'Customer language', 'ok' => in_array($language, $shop->languages ?? ['en'], true), 'tip' => 'Use a language your customers read.'],
            ['key' => 'readable', 'label' => 'Readable in 2 seconds', 'ok' => mb_strlen($subline) <= 90, 'tip' => 'Shorten the second line.'],
        ];
        $passed = count(array_filter($checks, fn (array $check): bool => $check['ok']));

        return [(int) round(70 + 30 * $passed / count($checks)), $checks];
    }
}
