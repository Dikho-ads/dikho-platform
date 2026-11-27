<script setup>
import { computed } from 'vue';
import { useApp, PACKAGES, inr } from '@/stores/app';
import Logo from '@/components/ui/Logo.vue';

const app = useApp();
const ctaTo = computed(() => (app.authed ? { name: 'home' } : { name: 'signup' }));

const steps = [
    { n: '01', t: 'Tell us about your shop', d: 'Category, town, language, WhatsApp number and your usual offers. That is your brand kit, set once.' },
    { n: '02', t: 'We plan your month', d: 'Festivals in your region, salary week, wedding season, offers, reels and win-back messages, laid out on a calendar with a credit receipt.' },
    { n: '03', t: 'Approve and publish', d: 'Check three versions, see the trust check, then post to WhatsApp, Instagram and Meta from one place.' },
    { n: '04', t: 'See who walked in', d: 'Results counts chats, calls and QR scans, not likes.' },
];

const credits = [
    { t: 'Reel', cr: 15 },
    { t: 'Post', cr: 6 },
    { t: 'Ad', cr: 20 },
    { t: 'Win-back message', cr: 10 },
];

const places = [
    { t: 'Your festivals, your dates', d: 'Tulsi Vivah opens the Maharashtra wedding season. Sankranti comes in black and gold. Each month is planned around what your town actually celebrates.' },
    { t: 'Your language, set properly', d: 'मराठी, हिंदी and English written natively, never broken letters. More Indian languages are coming.' },
    { t: 'The right rules', d: 'No deity images next to prices. No national flag in ads. A trust check flags a post before it goes out.' },
];

const faqs = [
    { q: 'Do you guarantee customers?', a: 'No, and nobody honest can. Every post is built the way local shops that get messages build theirs: a hook, a clear offer, your number and a reason to act today. The trust check flags anything that is missing one.' },
    { q: 'How do credits work?', a: 'Each plan gives you credits every month. A post costs 6, a reel 15, an ad 20. Credits reset on the same date each month, and you can top up your wallet any time you run short.' },
    { q: 'What if I am busy and do not approve?', a: 'Nothing goes out without you. Your plan waits on the Needs you list, and approving a day takes one tap.' },
    { q: 'Can I change things?', a: 'Words, offer, price, date, language and design are all editable before you publish.' },
    { q: 'How do I know it works?', a: 'The Results screen counts chats, calls and QR scans that came from your posts, so you see people, not likes.' },
    { q: 'Which languages?', a: 'Marathi, Hindi and English now, with more on the way.' },
];
</script>

<template>
    <div class="doc dots" style="min-height: 100vh; overflow-x: hidden">
        <header class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-4">
            <RouterLink :to="{ name: 'landing' }" aria-label="Dikho home"><Logo /></RouterLink>
            <span class="sp"></span>
            <RouterLink class="btn bg" :to="{ name: 'pricing' }">Pricing</RouterLink>
            <RouterLink v-if="!app.authed" class="btn bg" :to="{ name: 'login' }">Sign in</RouterLink>
            <RouterLink class="btn bp" :to="ctaTo">{{ app.authed ? 'Open dashboard' : 'Get started' }}</RouterLink>
        </header>

        <section class="mx-auto grid max-w-6xl items-center gap-10 px-4 pt-6 pb-16 lg:grid-cols-[1fr_1.1fr] lg:pt-12">
            <div class="rise" style="display: flex; flex-direction: column; gap: 22px">
                <span class="eyb"><b>00</b>·<span>For shops in Bharat's towns and cities</span></span>
                <h1 class="h-xl" style="font-size: clamp(38px, 7vw, 60px); line-height: 1; letter-spacing: -0.045em">The World Should See You</h1>
                <p class="t dim" style="max-width: 460px">Plan the month, make posts and reels in your language, run WhatsApp and Meta from one place, and see who actually walked in.</p>
                <div class="flex flex-wrap gap-3">
                    <RouterLink class="btn bp lg" :to="ctaTo">
                        Start free
                        <svg class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                    </RouterLink>
                    <RouterLink class="btn bs lg" :to="{ name: 'pricing' }">See pricing</RouterLink>
                </div>
                <p class="lm">Free plan forever · paid from {{ inr(PACKAGES[1].price) }} a month</p>
            </div>

            <div class="panel rise d2" style="overflow: hidden; box-shadow: 0 30px 60px -30px rgba(15, 23, 42, 0.25)">
                <div style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-bottom: 1px solid #e2e8f0; background: #f8fafc">
                    <Logo :size="16" :name="false" />
                    <span class="ts" style="font-weight: 500">Dikho - {{ app.shop.name }}</span>
                    <span class="sp"></span>
                    <span class="st ok live mono">Synced</span>
                </div>
                <div style="padding: 16px; display: flex; flex-direction: column; gap: 14px">
                    <div style="display: flex; justify-content: space-between; align-items: baseline">
                        <span class="h-sm">Namaste, {{ app.user.name }} ji</span>
                        <span class="mono muted">{{ app.creditsLeft }} / {{ app.creditsTotal }} cr</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2.5">
                        <div class="pst r45 y rise d3" style="font-size: 6.4px">
                            <div class="pad" style="flex: 1; justify-content: flex-end"><span class="hk">Purana phone laao, ₹2,000 extra pao</span><span class="pr">Sunday tak</span></div>
                            <div class="ft"><b>{{ app.shop.short }}</b><span class="wa">Chat</span></div>
                        </div>
                        <div class="pst r45 rise d4" style="font-size: 6.4px">
                            <div class="pi">phone photo</div>
                            <div class="pad"><span class="hk">5G phone ₹9,999</span><span class="terms">MRP ₹12,499 · till Sunday</span></div>
                            <div class="ft"><b>{{ app.shop.short }}</b><span>★ 4.6</span></div>
                        </div>
                        <div class="pst r45 w rise d5" style="font-size: 6.4px">
                            <div class="pad"><span class="hk">College Road waalon, screen toota?</span></div>
                            <div class="pi">repair photo</div>
                            <div class="ft"><b>1 ghante</b><span>mein</span></div>
                        </div>
                    </div>
                    <div style="border: 1px solid #e2e8f0; border-radius: 10px">
                        <div class="row" style="padding: 10px 12px"><span class="ty camp"></span><span class="grow ts">December plan · 14 items</span><span class="st warn">Needs you</span></div>
                        <div class="row" style="padding: 10px 12px"><span class="ty video"></span><span class="grow ts">Shaadi exchange reel</span><span class="mono muted">15 cr</span></div>
                        <div class="row" style="padding: 10px 12px"><span class="ty post"></span><span class="grow ts">14 regulars stopped coming</span><span class="mono">12 chats</span></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-12">
            <span class="eyb"><b>01</b>·<span>How it works</span></span>
            <h2 class="h-lg mt-3">Your marketing desk, four steps</h2>
            <div class="mt-6 grid gap-x-10 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="(s, i) in steps" :key="s.n" class="rise" :class="'d' + (i + 1)" style="border-top: 1px solid #e2e8f0; padding: 16px 0">
                    <span class="mono muted">{{ s.n }}</span>
                    <div class="h-sm mt-2">{{ s.t }}</div>
                    <p class="tx muted mt-1">{{ s.d }}</p>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-12">
            <div class="grid items-start gap-8 lg:grid-cols-2">
                <div>
                    <span class="eyb"><b>02</b>·<span>Credits and results</span></span>
                    <h2 class="h-lg mt-3">Pay for what you make, see what it brought</h2>
                    <p class="t dim mt-3" style="max-width: 460px">Every plan comes with credits that reset each month. A wallet tops you up when you need more. Then Results counts the chats, calls and QR scans that came back, not likes.</p>
                </div>
                <div class="receipt panel pb">
                    <span class="lm" style="font-family: var(--f)">What credits buy</span>
                    <div v-for="c in credits" :key="c.t" class="ln" style="margin-top: 2px"><span>{{ c.t }}</span><span>{{ c.cr }} cr</span></div>
                    <div class="ln tot"><span>{{ PACKAGES[1].name }} plan per month</span><span>{{ PACKAGES[1].credits }} cr</span></div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-12">
            <span class="eyb"><b>03</b>·<span>Regional and language</span></span>
            <h2 class="h-lg mt-3">Made for your town, not for "India"</h2>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div v-for="p in places" :key="p.t" class="panel pb">
                    <div class="h-sm">{{ p.t }}</div>
                    <p class="ts dim mt-1">{{ p.d }}</p>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-12">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <span class="eyb"><b>04</b>·<span>Pricing</span></span>
                    <h2 class="h-lg mt-3">Less than ₹17 a day</h2>
                </div>
                <RouterLink class="btn bs" :to="{ name: 'pricing' }">Compare plans</RouterLink>
            </div>
            <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="p in PACKAGES" :key="p.id" class="panel pb">
                    <span class="lm">{{ p.name }}</span>
                    <div class="mt-2"><span class="num">{{ inr(p.price) }}</span><span class="muted"> /month</span></div>
                    <div class="mono muted mt-1">{{ p.credits }} cr / month</div>
                    <div class="ts dim mt-2">{{ p.blurb }}</div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-3xl px-4 py-12">
            <span class="eyb"><b>05</b>·<span>Questions</span></span>
            <h2 class="h-lg mt-3">Questions</h2>
            <div class="mt-5">
                <details v-for="f in faqs" :key="f.q" style="border-top: 1px solid #e2e8f0; padding: 14px 4px">
                    <summary class="h-sm" style="cursor: pointer">{{ f.q }}</summary>
                    <p class="ts dim mt-2">{{ f.a }}</p>
                </details>
            </div>
        </section>

        <footer style="border-top: 1px solid #e2e8f0">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-4 py-8">
                <Logo />
                <span class="ts muted">The World Should See You</span>
                <span class="sp"></span>
                <RouterLink class="ts dim" :to="{ name: 'pricing' }">Pricing</RouterLink>
                <RouterLink v-if="!app.authed" class="ts dim" :to="{ name: 'login' }">Sign in</RouterLink>
                <span class="lm">Demo build · 2026</span>
            </div>
        </footer>
    </div>
</template>
