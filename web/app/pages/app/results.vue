<script setup lang="ts">
// Results: GET results?range=week|last_week|month (synced with ?range=). Totals with change,
// chats and calls per day, chats by channel, ad money, win-back and the top pieces.
definePageMeta({ layout: 'app', title: 'Results' });

const route = useRoute();
const router = useRouter();

const RANGES = [
    { key: 'week', n: 'This week', vs: 'vs the week before' },
    { key: 'last_week', n: 'Last week', vs: 'vs the week before' },
    { key: 'month', n: 'Last 30 days', vs: 'vs the 30 days before' },
] as const;
const L: [string, string][] = [
    ['views', 'Saw it'],
    ['chats', 'Chats'],
    ['calls', 'Calls'],
    ['qr_scans', 'QR used'],
    ['offer_codes', 'Offer codes'],
    ['ad_spend_paise', 'Ad spend'],
];
const ANGLE: Record<string, string> = { offer: 'Offer', trust: 'Trust', local: 'Local', newness: 'Newness', festival: 'Festival', tip: 'Tip' };
const shade = ['#F1F5F9', '#CBD5E1', '#94A3B8', '#475569', '#0F172A'];

const rangeKey = computed(() => {
    const r = route.query.range;
    return RANGES.some((x) => x.key === r) ? (r as string) : 'week';
});
const range = computed(() => RANGES.find((x) => x.key === rangeKey.value)!);

const { data, pending, error, refresh } = useApiData<any>('results', 'results', { query: () => ({ range: rangeKey.value }), watch: [rangeKey] });

function pick(key: string) {
    if (key === rangeKey.value) return;
    router.replace({ query: { ...route.query, range: key === 'week' ? undefined : key } });
}

// Count-up on load and on each range change.
const p = ref(1);
let raf = 0;
function animate() {
    if (!import.meta.client) return;
    cancelAnimationFrame(raf);
    const t0 = performance.now();
    p.value = 0;
    const tick = (t: number) => {
        p.value = Math.min(1, (t - t0) / 900);
        if (p.value < 1) raf = requestAnimationFrame(tick);
    };
    raf = requestAnimationFrame(tick);
}
onMounted(animate);
watch(data, animate);
onBeforeUnmount(() => cancelAnimationFrame(raf));
const e = computed(() => 1 - Math.pow(1 - p.value, 3));

const rangeLabel = computed(() => {
    const r = data.value?.range;
    if (!r) return '';
    const a = new Date(r.from + 'T00:00:00');
    const b = new Date(r.to + 'T00:00:00');
    const sameMonth = a.getMonth() === b.getMonth();
    return (sameMonth ? a.getDate() + ' – ' + day(b) : day(a) + ' – ' + day(b)).toUpperCase();
});

const kpis = computed(() =>
    L.map(([k, l]) => {
        const t = data.value?.totals?.[k];
        const v = t ? Math.round(t.value * e.value) : null;
        const s = t && t.change != null ? pct(t.change) + ' ' + range.value.vs : t && t.previous === 0 ? 'none in the period before' : '—';
        return { l, v: v == null ? '—' : k === 'ad_spend_paise' ? inr(v) : num(v), s };
    }),
);

// ---- chart: chats (solid) and calls (dashed) per day ----
const days = computed<any[]>(() => data.value?.per_day ?? []);
const n = computed(() => days.value.length);
const dense = computed(() => n.value > 10);
const lab = (d: string, i: number) => (dense.value ? (i % 5 === 0 || i === n.value - 1 ? day(d) : '') : weekday(d));
const max = computed(() => Math.max(3, ...days.value.map((x) => Math.max(x.chats, x.calls))) * 1.15);
const X = (i: number) => (n.value > 1 ? 70 + i * (660 / (n.value - 1)) : 400);
const Y = (v: number) => 220 - (v / max.value) * 196;
const path = (arr: number[]) => arr.map((v, i) => (i ? 'L' : 'M') + X(i).toFixed(1) + ' ' + Y(v).toFixed(1)).join(' ');
const hov = ref(-1);
const hv = computed(() => (hov.value >= 0 && hov.value < n.value ? hov.value : Math.max(0, n.value - 1)));
const step = computed(() => (n.value > 1 ? 660 / (n.value - 1) : 660));
const grid = computed(() =>
    [0, 1, 2, 3].map((i) => {
        const v = Math.round((max.value / 3) * i);
        return { y: Y(v), ty: Y(v) + 4, v };
    }),
);
const chatPath = computed(() => path(days.value.map((x) => x.chats)));
const callPath = computed(() => path(days.value.map((x) => x.calls)));
const pts = computed(() =>
    days.value.map((x, i) => ({
        k: x.date,
        x: X(i),
        y: Y(x.chats),
        n: lab(x.date, i),
        r: i === hv.value ? 6 : dense.value ? 2.5 : 4,
        f: i === hv.value ? '#F2A007' : '#fff',
        gl: i === hv.value ? '#CBD5E1' : 'transparent',
        dl: 0.9 + Math.min(i, 10) * 0.08 + 's',
        rx: X(i) - step.value / 2,
        rw: step.value,
    })),
);
const tipX = computed(() => {
    const frac = X(hv.value) / 760;
    return frac > 0.7 ? `calc(${(frac * 100).toFixed(1)}% - 150px)` : `calc(${(frac * 100).toFixed(1)}% + 20px)`;
});
const tip = computed(() => {
    const x = days.value[hv.value];
    return x ? { n: eyebrowDate(x.date), c: x.chats, k: x.calls, v: x.views } : null;
});
const aria = computed(() => 'Chats per day: ' + days.value.map((x) => day(x.date) + ' ' + x.chats).join(', '));

// ---- chats by weekday (stands in for the old reply heat map; the API has no hour-of-day data) ----
const byWeekday = computed(() => {
    const names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const sums = names.map(() => 0);
    days.value.forEach((x) => {
        const w = (new Date(x.date + 'T00:00:00').getDay() + 6) % 7;
        sums[w] += x.chats;
    });
    const top = Math.max(1, ...sums);
    return names.map((nm, i) => ({ nm, v: sums[i], bg: sums[i] === top && top > 0 ? '#F2A007' : shade[Math.min(4, Math.ceil((sums[i] / top) * 4))] }));
});

// ---- channels ----
const chans = computed(() => {
    const rows = (data.value?.per_channel ?? []).filter((c: any) => c.chats || c.views || c.qr_scans);
    const top = Math.max(1, ...rows.map((c: any) => c.chats));
    return rows
        .slice()
        .sort((a: any, b: any) => b.chats - a.chats || b.views - a.views)
        .map((c: any, i: number) => ({ n: CHANNEL_LABEL[c.channel] || c.channel, v: c.chats, views: c.views, w: (c.chats / top) * 100 + '%', dl: 0.3 + i * 0.08 + 's', qr: c.qr_scans, calls: c.calls }));
});

// ---- ad money, win-back, top ----
const ads = computed(() => data.value?.ads);
const costPerChat = computed(() => data.value?.insights?.cost_per_chat_paise ?? (ads.value?.chats ? Math.round(ads.value.spend_paise / ads.value.chats) : null));
const adChange = computed(() => data.value?.totals?.ad_spend_paise?.change);
const wb = computed(() => data.value?.win_back ?? { sent: 0, replies: 0, cost_paise: 0 });
const wbCells = computed(() => {
    const sent = wb.value.sent;
    const cells = Math.min(sent, 30);
    if (!cells) return [];
    const on = Math.round((wb.value.replies / sent) * cells);
    return Array.from({ length: cells }, (_, i) => ({ c: i < on ? '#0F172A' : '#E2E8F0', d: 0.4 + Math.min(i, 14) * 0.04 + 's' }));
});
const top = computed<any[]>(() => data.value?.top ?? []);
const where = (t: any) => (t.channels || []).map((c: string) => CHANNEL_LABEL[c] || c).join(', ') || '—';
const insights = computed(() => data.value?.insights ?? {});

function exportCsv() {
    const rows = [['date', 'views', 'chats', 'calls', 'qr_scans'], ...days.value.map((x) => [x.date, x.views, x.chats, x.calls, x.qr_scans])];
    const blob = new Blob([rows.map((r) => r.join(',')).join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `results-${data.value?.range?.from}-to-${data.value?.range?.to}.csv`;
    a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
}
</script>

<template>
    <div class="wrap">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 360px">
                <span class="eyb"><b>RESULTS</b>·<span>{{ rangeLabel || range.n.toUpperCase() }}</span></span>
                <h1 class="h-lg">What brought people to the shop</h1>
                <p class="ts muted">Chats, calls and QR codes shown at the counter — not likes.</p>
            </div>
            <div class="seg" role="group" aria-label="Period">
                <button v-for="x in RANGES" :key="x.key" type="button" :class="rangeKey === x.key ? 'on' : ''" :aria-pressed="rangeKey === x.key" @click="pick(x.key)">{{ x.n }}</button>
            </div>
            <button type="button" class="btn bs" :disabled="!days.length" @click="exportCsv">
                <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v12M7 11l5 5 5-5M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"></path></svg>Export
            </button>
        </div>

        <div v-if="!data" class="panel">
            <LoadState :pending="pending" :error="error" :rows="6" @retry="refresh" />
        </div>

        <template v-else>
            <p v-if="error" class="err">Could not refresh: {{ error.message }} <button type="button" class="btn bg sm" @click="() => refresh()">Try again</button></p>
            <div class="strip rise d1" :style="{ opacity: pending ? 0.6 : 1, transition: 'opacity .15s' }">
                <div v-for="k in kpis" :key="k.l"><span class="lm">{{ k.l }}</span><span class="num">{{ k.v }}</span><span class="tx muted">{{ k.s }}</span></div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: stretch">
                <section class="panel rise d2" style="flex: 2 1 560px; min-width: 0">
                    <div class="ph">
                        <span class="eyb"><b>01</b>·<span>Chats and calls per day</span></span><span class="sp"></span>
                        <span style="display: inline-flex; gap: 14px" class="tx muted">
                            <span style="display: inline-flex; gap: 6px; align-items: center"><span style="width: 16px; height: 2px; background: #0f172a"></span>Chats</span>
                            <span style="display: inline-flex; gap: 6px; align-items: center"><span style="width: 16px; border-top: 2px dashed #94a3b8"></span>Calls</span>
                        </span>
                    </div>
                    <div v-if="!days.length" class="pb tx muted">No days in this period yet.</div>
                    <div v-else class="pb" style="position: relative" @mouseleave="hov = -1">
                        <svg :key="rangeKey" viewBox="0 0 760 250" width="100%" role="img" :aria-label="aria" style="display: block; overflow: visible">
                            <g v-for="g in grid" :key="g.v"><line x1="40" x2="750" :y1="g.y" :y2="g.y" stroke="#E2E8F0"></line><text x="30" :y="g.ty" text-anchor="end" font-family="Geist Mono" font-size="11" fill="#94A3B8">{{ g.v }}</text></g>
                            <path class="draw" style="--len: 1400" :d="callPath" fill="none" stroke="#94A3B8" stroke-width="1.8" stroke-dasharray="5 5"></path>
                            <path class="draw" style="--len: 1400" :d="chatPath" fill="none" stroke="#0F172A" stroke-width="2.2" stroke-linejoin="round"></path>
                            <g v-for="(pt, i) in pts" :key="pt.k">
                                <line :x1="pt.x" :x2="pt.x" y1="16" y2="220" :stroke="pt.gl"></line>
                                <circle :cx="pt.x" :cy="pt.y" :r="pt.r" :fill="pt.f" stroke="#0F172A" stroke-width="2" class="fade" :style="`animation-delay: ${pt.dl}`"></circle>
                                <text :x="pt.x" y="242" text-anchor="middle" font-family="Geist Mono" font-size="11" fill="#64748B">{{ pt.n }}</text>
                                <rect :x="pt.rx" y="10" :width="pt.rw" height="215" fill="transparent" @mouseenter="hov = i" @click="hov = i"></rect>
                            </g>
                        </svg>
                        <div
                            v-if="tip"
                            class="panel"
                            :style="`position: absolute; top: 18px; left: ${tipX}; padding: 8px 12px; box-shadow: 0 8px 20px rgba(15,23,42,.08); transition: left .25s var(--ease); pointer-events: none; white-space: nowrap`"
                        >
                            <span class="mono" style="display: block; color: #64748b">{{ tip.n }}</span><span class="ts"><b>{{ tip.c }}</b> chats · <b>{{ tip.k }}</b> calls</span>
                        </div>
                    </div>
                </section>
                <section style="flex: 1 1 300px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                    <div class="panel rise d3">
                        <div class="ph"><span class="eyb"><b>02</b>·<span>Best hook</span></span><span class="sp"></span><span v-if="data.posted != null" class="mono muted">{{ num(data.posted) }} posted</span></div>
                        <div class="pb" style="display: flex; flex-direction: column; gap: 12px">
                            <template v-if="insights.best_hook">
                                <p class="h-md">“{{ insights.best_hook }}”</p>
                                <p class="tx muted">{{ insights.best_day ? 'Best day: ' + insights.best_day : '' }}{{ insights.best_time ? ' · best time ' + insights.best_time : '' }}</p>
                                <NuxtLink class="btn bp sm" to="/app/create" style="align-self: flex-start"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"></path></svg>Repeat what worked</NuxtLink>
                            </template>
                            <template v-else>
                                <p class="tx muted">Not enough posts with chats in this period to pick a winner.</p>
                                <NuxtLink class="btn bs sm" to="/app/create" style="align-self: flex-start">Make a post</NuxtLink>
                            </template>
                        </div>
                    </div>
                    <div class="panel rise d4">
                        <div class="ph"><span class="eyb"><b>03</b>·<span>When people reply</span></span><span class="sp"></span><span v-if="insights.best_time" class="mono muted">best {{ insights.best_time }}</span></div>
                        <div class="pb" style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; align-items: center">
                            <span v-for="w in byWeekday" :key="w.nm" class="mono" style="font-size: 10.5px; color: #94a3b8; text-align: center">{{ w.nm }}</span>
                            <span v-for="w in byWeekday" :key="'c' + w.nm" class="hm" :style="`background: ${w.bg}`" :title="`${w.nm}: ${w.v} chats`"></span>
                            <span v-for="w in byWeekday" :key="'v' + w.nm" class="mono" style="font-size: 11px; text-align: center">{{ w.v }}</span>
                        </div>
                        <p class="tx muted" style="padding: 0 18px 16px">Chats by day of the week in this period.</p>
                    </div>
                </section>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr)); gap: 20px">
                <section class="panel rise d4">
                    <div class="ph"><span class="eyb"><b>04</b>·<span>Chats by channel</span></span><span class="sp"></span><span class="mono muted" style="font-size: 11px">chats · views</span></div>
                    <div class="pb" style="display: flex; flex-direction: column; gap: 12px">
                        <p v-if="!chans.length" class="tx muted">Nothing posted on a channel in this period.</p>
                        <div v-for="x in chans" :key="x.n" class="chrow" :title="`${x.n}: ${x.v} chats, ${x.calls} calls, ${x.qr} QR scans, ${x.views} views`">
                            <span class="ts">{{ x.n }}</span>
                            <span class="bar-t" style="height: 8px"><i :style="`width: ${x.w}; animation-delay: ${x.dl}`"></i></span>
                            <span class="mono" style="text-align: right">{{ x.v }}</span>
                            <span class="mono muted" style="text-align: right; font-size: 11px">{{ num(x.views) }}</span>
                        </div>
                    </div>
                </section>
                <section class="panel rise d5">
                    <div class="ph">
                        <span class="eyb"><b>05</b>·<span>Ad money</span></span><span class="sp"></span>
                        <span :class="ads?.spend_paise ? 'st ok' : 'st'">{{ ads?.spend_paise ? 'Spent' : 'No ads' }}</span>
                    </div>
                    <div class="pb receipt">
                        <div class="ln"><span class="muted">spent in this period</span><span>{{ inr(ads?.spend_paise ?? 0) }}</span></div>
                        <div class="ln"><span class="muted">chats from the ad</span><span>{{ num(ads?.chats ?? 0) }}</span></div>
                        <div class="ln"><span class="muted">spend {{ range.vs }}</span><span>{{ adChange == null ? '—' : pct(adChange) }}</span></div>
                        <div class="ln tot"><span>cost per chat</span><span>{{ costPerChat ? inr(costPerChat) : '—' }}</span></div>
                    </div>
                </section>
                <section class="panel rise d6">
                    <div class="ph"><span class="eyb"><b>06</b>·<span>Win-back</span></span><span class="sp"></span><span v-if="wb.cost_paise" class="mono muted">{{ inr(wb.cost_paise, { paise: true }) }}</span></div>
                    <div v-if="wb.sent" class="pb" style="display: flex; flex-direction: column; gap: 10px">
                        <p><span class="num">{{ num(wb.replies) }}</span><span class="muted"> of {{ num(wb.sent) }} replied</span></p>
                        <div :style="`display: grid; grid-template-columns: repeat(${wbCells.length}, minmax(0, 1fr)); gap: 3px`">
                            <span v-for="(w, i) in wbCells" :key="i" class="gy" :style="`height: 22px; border-radius: 3px; background: ${w.c}; animation-delay: ${w.d}`"></span>
                        </div>
                        <p class="tx muted">{{ Math.round((wb.replies / wb.sent) * 100) }}% replied to a personal offer{{ wb.sent > 30 ? ' (each block is about ' + Math.round(wb.sent / 30) + ' people)' : '' }}.</p>
                    </div>
                    <div v-else class="pb" style="display: flex; flex-direction: column; gap: 10px; align-items: flex-start">
                        <p class="tx muted">No win-back offers went out in this period.</p>
                        <NuxtLink class="btn bs sm" to="/app/customers?stage=lapsed">See who stopped coming</NuxtLink>
                    </div>
                </section>
            </div>

            <section class="panel rise d6" style="overflow: hidden">
                <div class="ph"><span class="eyb"><b>07</b>·<span>Top pieces · {{ range.n.toLowerCase() }}</span></span></div>
                <div v-if="!top.length" class="pb" style="display: flex; flex-direction: column; gap: 10px; align-items: flex-start">
                    <p class="tx muted">Nothing was posted in this period yet.</p>
                    <NuxtLink class="btn bs sm" to="/app/studio/auto">See today’s ready posts</NuxtLink>
                </div>
                <div v-else class="tscroll">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Piece</th>
                                <th>Where</th>
                                <th>Hook angle</th>
                                <th style="text-align: right">Views</th>
                                <th style="text-align: right">Chats</th>
                                <th style="text-align: right">Calls</th>
                                <th style="text-align: right">QR</th>
                                <th style="text-align: right">Trust</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in top" :key="t.id" style="cursor: default">
                                <td>
                                    <span style="display: inline-flex; gap: 8px; align-items: center">
                                        <span :class="'ty ' + typeDot(t.type)" :title="t.type_label"></span>
                                        <span style="display: flex; flex-direction: column; gap: 2px; min-width: 0">
                                            <b style="font-weight: 500">{{ t.hook || t.title }}</b><span class="mono muted" style="font-size: 11px">{{ t.type_label }}{{ t.posted_at ? ' · ' + day(t.posted_at) : '' }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td class="muted">{{ where(t) }}</td>
                                <td>{{ ANGLE[t.angle] || t.angle || '—' }}</td>
                                <td class="mono" style="text-align: right">{{ num(t.metrics?.views) }}</td>
                                <td class="mono" style="text-align: right">{{ num(t.metrics?.chats) }}</td>
                                <td class="mono" style="text-align: right">{{ num(t.metrics?.calls) }}</td>
                                <td class="mono" style="text-align: right">{{ num(t.metrics?.qr_scans) }}</td>
                                <td class="mono" style="text-align: right">{{ t.trust_score ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </div>
</template>

<style scoped>
.hm {
    aspect-ratio: 1.6;
    border-radius: 5px;
    transition: transform 0.2s var(--ease);
}
.hm:hover {
    transform: scale(1.08);
}
.chrow {
    display: grid;
    grid-template-columns: 130px minmax(0, 1fr) 30px 44px;
    gap: 10px;
    align-items: center;
}
@media (max-width: 420px) {
    .chrow {
        grid-template-columns: 104px minmax(0, 1fr) 26px 40px;
        gap: 8px;
    }
}
</style>
