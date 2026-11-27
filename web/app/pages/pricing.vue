<script setup lang="ts">
// Public pricing. Plans from GET packages; the signed-in owner's current plan is highlighted and
// changes go through Settings → Billing (POST billing/package lives there).
type Pkg = { id: string; name: string; price_paise: number; credits: number; blurb: string; features: string[] };

useHead({ title: 'Pricing' });

const auth = useAuth();
const { data: packages, pending, error, refresh } = useApiData<Pkg[]>('packages', 'packages');
const list = computed(() => packages.value ?? []);

const currentId = computed(() => auth.account?.package?.id ?? auth.shop?.package_id ?? null);
const isCurrent = (p: Pkg) => auth.signedIn && p.id === currentId.value;
const ctaTo = computed(() => (auth.signedIn ? (auth.setupDone ? '/app/settings?tab=billing' : '/setup') : '/signup'));
const homeTo = computed(() => (auth.signedIn ? (auth.setupDone ? '/app' : '/setup') : '/signup'));
const ctaLabel = (p: Pkg) => {
    if (!auth.signedIn) return p.price_paise ? 'Get started' : 'Start free';
    if (!auth.setupDone) return 'Pick in setup';
    return isCurrent(p) ? 'Your plan' : 'Choose in Settings';
};

const firstPaid = computed(() => list.value.filter((p) => p.price_paise > 0).sort((a, b) => a.price_paise - b.price_paise)[0] ?? null);
const perDay = computed(() => (firstPaid.value ? Math.ceil(firstPaid.value.price_paise / 100 / 30) : null));

// Credits each format costs (POST content charges these).
const credits = [
    { t: 'Reel', n: 15 },
    { t: 'Ad', n: 6 },
    { t: 'Carousel', n: 5 },
    { t: 'Print design', n: 4 },
    { t: 'WhatsApp message', n: 3 },
    { t: 'Post or Status', n: 2 },
    { t: 'SMS', n: 1 },
];
const example = 3 * 15 + 7 * 2;
</script>

<template>
    <div class="doc dots" style="min-height: 100vh; overflow-x: hidden">
        <header class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-4">
            <NuxtLink to="/" aria-label="Dikho home"><Logo /></NuxtLink>
            <span class="sp"></span>
            <NuxtLink v-if="!auth.signedIn" class="btn bg" to="/login">Sign in</NuxtLink>
            <NuxtLink class="btn bp" :to="homeTo">{{ auth.signedIn ? (auth.setupDone ? 'Open dashboard' : 'Finish setup') : 'Get started' }}</NuxtLink>
        </header>

        <main class="mx-auto max-w-6xl px-4 pt-6 pb-16">
            <div class="rise" style="display: flex; flex-direction: column; gap: 14px; max-width: 640px">
                <span class="eyb"><b>00</b>·<span>Pricing</span></span>
                <h1 class="h-xl" style="font-size: clamp(34px, 6vw, 52px); letter-spacing: -0.045em">{{ perDay ? `Less than ₹${perDay} a day` : 'Simple monthly plans' }}</h1>
                <p class="t dim">Pick a plan with monthly credits. Credits reset every month, and you can top up if you run short. Ads and WhatsApp messages are paid from a prepaid wallet at cost.</p>
            </div>

            <div class="mt-8">
                <LoadState :pending="pending" :error="error" :empty="!list.length" :rows="4" empty-text="Plans are not available right now." empty-hint="Please check back in a little while." @retry="refresh">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="(p, i) in list"
                            :key="p.id"
                            class="panel pb rise"
                            :class="'d' + (i + 1)"
                            :style="'display: flex; flex-direction: column; gap: 10px;' + (isCurrent(p) ? ' box-shadow: 0 0 0 2px #F2A007' : '')"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="lm">{{ p.name }}</span>
                                <span v-if="isCurrent(p)" class="tag acc">Current</span>
                            </div>
                            <div><span class="num">{{ inr(p.price_paise) }}</span><span class="muted"> /month</span></div>
                            <div class="mono">{{ num(p.credits) }} cr / month</div>
                            <p class="ts dim">{{ p.blurb }}</p>
                            <ul v-if="p.features?.length" class="tx muted" style="flex: 1; display: flex; flex-direction: column; gap: 4px; margin: 0; padding: 0; list-style: none">
                                <li v-for="f in p.features" :key="f" style="display: flex; gap: 6px; align-items: flex-start">
                                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true" style="flex-shrink: 0; margin-top: 2px"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg>
                                    <span>{{ f }}</span>
                                </li>
                            </ul>
                            <span v-else style="flex: 1"></span>
                            <NuxtLink class="btn sm" :class="isCurrent(p) ? 'bs' : 'bp'" :to="ctaTo">{{ ctaLabel(p) }}</NuxtLink>
                        </div>
                    </div>
                </LoadState>
            </div>

            <div class="mt-10 grid items-start gap-8 lg:grid-cols-2">
                <div>
                    <span class="eyb"><b>01</b>·<span>What credits buy</span></span>
                    <h2 class="h-lg mt-3">Every item shows its cost first</h2>
                    <p class="t dim mt-3" style="max-width: 460px">Each item in your plan shows its credit cost before you approve it. Skipped items give their credits back. Results then counts the chats, calls and QR scans that came from it.</p>
                </div>
                <div class="receipt panel pb">
                    <span class="lm" style="font-family: var(--f)">Credit costs</span>
                    <div v-for="c in credits" :key="c.t" class="ln" style="margin-top: 2px"><span>{{ c.t }}</span><span>{{ c.n }} cr</span></div>
                    <div class="ln tot"><span>Example: 3 reels + 7 posts</span><span>{{ example }} cr</span></div>
                </div>
            </div>

            <div class="mt-10 flex flex-wrap gap-3">
                <NuxtLink class="btn bp lg" :to="ctaTo">{{ auth.signedIn ? (auth.setupDone ? 'Manage plan in Settings' : 'Finish setup') : 'Get started' }}</NuxtLink>
                <NuxtLink class="btn bs lg" to="/">Back to home</NuxtLink>
            </div>
        </main>
    </div>
</template>
