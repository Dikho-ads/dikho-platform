<script setup>
import { computed } from 'vue';
import { useApp, PACKAGES, inr } from '@/stores/app';
import Logo from '@/components/ui/Logo.vue';

const app = useApp();
const ctaTo = computed(() => (app.authed ? { name: 'settings' } : { name: 'signup' }));
const isCurrent = (p) => app.authed && p.id === app.packageId;
const ctaLabel = (p) => {
    if (!app.authed) return p.price ? 'Get started' : 'Start free';
    return isCurrent(p) ? 'Your plan' : 'Choose in Settings';
};

const credits = [
    { t: 'Reel', n: 15 },
    { t: 'Post', n: 6 },
    { t: 'Ad', n: 20 },
    { t: 'Win-back message', n: 10 },
    { t: 'Customer story', n: 2 },
];
const example = 3 * 15 + 7 * 6;
</script>

<template>
    <div class="doc dots" style="min-height: 100vh; overflow-x: hidden">
        <header class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-4">
            <RouterLink :to="{ name: 'landing' }" aria-label="Dikho home"><Logo /></RouterLink>
            <span class="sp"></span>
            <RouterLink v-if="!app.authed" class="btn bg" :to="{ name: 'login' }">Sign in</RouterLink>
            <RouterLink class="btn bp" :to="app.authed ? { name: 'home' } : { name: 'signup' }">{{ app.authed ? 'Open dashboard' : 'Get started' }}</RouterLink>
        </header>

        <main class="mx-auto max-w-6xl px-4 pt-6 pb-16">
            <div class="rise" style="display: flex; flex-direction: column; gap: 14px; max-width: 640px">
                <span class="eyb"><b>00</b>·<span>Pricing</span></span>
                <h1 class="h-xl" style="font-size: clamp(34px, 6vw, 52px); letter-spacing: -0.045em">Less than ₹17 a day</h1>
                <p class="t dim">Pick a plan with monthly credits. Credits reset every month, and your wallet tops you up if you run short.</p>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="(p, i) in PACKAGES"
                    :key="p.id"
                    class="panel pb rise"
                    :class="'d' + (i + 1)"
                    :style="'display: flex; flex-direction: column; gap: 10px;' + (isCurrent(p) ? ' box-shadow: 0 0 0 2px #F2A007' : '')"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="lm">{{ p.name }}</span>
                        <span v-if="isCurrent(p)" class="tag acc">Current</span>
                    </div>
                    <div><span class="num">{{ inr(p.price) }}</span><span class="muted"> /month</span></div>
                    <div class="mono">{{ p.credits }} cr / month</div>
                    <p class="ts dim" style="flex: 1">{{ p.blurb }}</p>
                    <RouterLink class="btn sm" :class="isCurrent(p) ? 'bs' : 'bp'" :to="ctaTo">{{ ctaLabel(p) }}</RouterLink>
                </div>
            </div>

            <div class="mt-10 grid items-start gap-8 lg:grid-cols-2">
                <div>
                    <span class="eyb"><b>01</b>·<span>What credits buy</span></span>
                    <h2 class="h-lg mt-3">Every item shows its cost first</h2>
                    <p class="t dim mt-3" style="max-width: 460px">Each item in your plan shows its credit cost before you approve it. Results then counts the chats, calls and QR scans that came from it.</p>
                </div>
                <div class="receipt panel pb">
                    <span class="lm" style="font-family: var(--f)">Credit costs</span>
                    <div v-for="c in credits" :key="c.t" class="ln" style="margin-top: 2px"><span>{{ c.t }}</span><span>{{ c.n }} cr</span></div>
                    <div class="ln tot"><span>Example: 3 reels + 7 posts</span><span>{{ example }} cr</span></div>
                </div>
            </div>

            <div class="mt-10 flex flex-wrap gap-3">
                <RouterLink class="btn bp lg" :to="ctaTo">{{ app.authed ? 'Manage plan in Settings' : 'Get started' }}</RouterLink>
                <RouterLink class="btn bs lg" :to="{ name: 'landing' }">Back to home</RouterLink>
            </div>
        </main>
    </div>
</template>
