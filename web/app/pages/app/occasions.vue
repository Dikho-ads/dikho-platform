<script setup lang="ts">
// Upcoming festivals, local events, seasons and moments for this shop's region, how well each fits
// the shop's category, and the pieces already planned around each day T. ?id= preselects one.
definePageMeta({ layout: 'app', title: 'Occasions' });

const route = useRoute();
const router = useRouter();
const auth = useAuth();

const WINDOWS = [
    { key: '60', label: '60 days', days: 60, head: 'Next 60 days' },
    { key: '120', label: '4 months', days: 120, head: 'Next 4 months' },
];
const win = computed(() => WINDOWS.find((w) => w.key === route.query.window) ?? WINDOWS[0]!);
const today = new Date();
const plus = (n: number) => {
    const d = new Date(today);
    d.setDate(d.getDate() + n);
    return isoDate(d);
};
const from = isoDate(today);
const to = computed(() => plus(win.value.days));

const occ = useApiData<{ data: any[] }>(() => `occasions-${win.value.key}`, 'occasions', { query: () => ({ from, to: to.value }) });
// Pieces planned around the occasions (a little before today for "tease" posts already made, a week past the window).
const content = useApiData<{ data: any[] }>(() => `occasions-content-${win.value.key}`, 'content', { query: () => ({ from: plus(-14), to: plus(win.value.days + 7), limit: 500 }) });

const all = computed<any[]>(() => occ.data.value?.data ?? []);
const shop = computed(() => auth.shop);
const langs = computed<string[]>(() => shop.value?.languages ?? []);

const KIND: Record<string, string> = { festival: 'Festival', local: 'Local event', season: 'Season', national: 'National day', moment: 'Moment' };
const FILTERS: [string, string[] | null][] = [
    ['All', null],
    ['Festivals', ['festival', 'national']],
    ['Seasons & local', ['season', 'local']],
    ['Moments', ['moment']],
];
const filter = ref(0);
const vis = computed(() => {
    const kinds = FILTERS[filter.value]![1];
    return all.value.filter((o) => !kinds || kinds.includes(o.kind));
});

const sel = ref<number | null>(Number(route.query.id) || null);
const c = computed(() => vis.value.find((o) => o.id === sel.value) ?? all.value.find((o) => o.id === sel.value) ?? vis.value[0] ?? null);
function pick(id: number) {
    sel.value = id;
    router.replace({ query: { ...route.query, id } });
}

const fit = (r: number) => (r >= 0.7 ? 'Strong fit' : r >= 0.45 ? 'Some fit' : 'Low fit');
const MON = (iso: string) => monthName(iso, 'short').toUpperCase();
const list = computed(() => vis.value.map((o) => {
    const started = o.starts_on <= from;
    const multiMonth = o.starts_on.slice(0, 7) !== o.ends_on.slice(0, 7);
    return {
        id: o.id,
        n: o.name,
        type: KIND[o.kind] ?? o.kind,
        sub: started ? (o.ends_on === from || o.starts_on === o.ends_on ? 'today' : `on now · till ${day(o.ends_on)}`) : relative(o.starts_on),
        day: started && o.starts_on !== from ? 'NOW' : String(Number(o.starts_on.slice(8, 10))),
        mon: multiMonth ? `${MON(o.starts_on)}–${MON(o.ends_on)}` : MON(o.starts_on),
        cls: 'oc' + (c.value?.id === o.id ? ' on' : ''),
        st: o.planned_items ? `In plan · ${o.planned_items}` : fit(o.relevance),
        sc: o.planned_items ? 'st ok' : o.relevance >= 0.7 ? 'st acc' : 'st',
    };
}));
const count = computed(() => `${vis.value.length} occasion${vis.value.length === 1 ? '' : 's'}`);

const localNames = computed(() => {
    if (!c.value) return [];
    const out: { lang: string; text: string }[] = [];
    if (langs.value.includes('hi') && c.value.name_hi) out.push({ lang: 'hi', text: c.value.name_hi });
    if (langs.value.includes('mr') && c.value.name_mr && c.value.name_mr !== c.value.name_hi) out.push({ lang: 'mr', text: c.value.name_mr });
    return out;
});
const regions = computed(() => [...new Set(all.value.map((o) => o.region).filter((r) => r && r !== 'india'))].map((r: string) => r.toUpperCase()));
const eyebrow = computed(() => [shop.value?.city?.toUpperCase(), ...regions.value].filter(Boolean).join(' · '));
const when = computed(() => (c.value ? (c.value.starts_on === c.value.ends_on ? eyebrowDate(c.value.starts_on) : `${day(c.value.starts_on)} – ${day(c.value.ends_on)}`) : ''));
const countdown = computed(() => {
    const o = c.value;
    if (!o) return '';
    if (o.starts_on > from) return relative(o.starts_on);
    if (o.ends_on > from) return `on now · ${relative(o.ends_on).replace('in ', '')} left`;
    return 'today';
});

// Planned pieces for the selected occasion, placed on a T−14 … T+7 axis around its first day.
const DAY = 86400000;
const offset = (iso: string, t: string) => Math.round((new Date(iso.slice(0, 10) + 'T00:00:00').getTime() - new Date(t + 'T00:00:00').getTime()) / DAY);
const planned = computed(() => (c.value ? (content.data.value?.data ?? []).filter((it) => it.occasion?.id === c.value.id && it.status !== 'skipped') : []));
const total = computed(() => planned.value.reduce((a, it) => a + (it.credits || 0), 0));
const axis = Array.from({ length: 22 }, (_, i) => {
    const d = i - 14;
    return { n: d === 0 ? 'T' : d % 2 === 0 ? (d > 0 ? '+' : '') + d : '', c: d === 0 ? '#0F172A' : '#94A3B8', b: d === 0 ? '#F2A007' : 'transparent' };
});
const tLabel = (d: number) => (d === 0 ? 'T' : d > 0 ? `T+${d}` : `T−${-d}`);
const marks = computed(() => {
    const seen: Record<number, number> = {};
    return planned.value.map((it, i) => {
        const d = Math.max(-14, Math.min(7, offset(it.scheduled_at, c.value.starts_on)));
        const stack = (seen[d] = (seen[d] ?? -1) + 1);
        return { id: it.id, ph: tLabel(offset(it.scheduled_at, c.value.starts_on)), k: it.credits ? typeDot(it.type) : 'free', x: (((d + 14 + 0.5) / 22) * 100).toFixed(2) + '%', top: 28 + stack * 26 + 'px', dl: i * 0.07 + 's' };
    });
});
const STAGE: Record<string, string> = { awareness: 'Awareness', trust: 'Trust', buying: 'Buying', return: 'Coming back' };
const STATUS: Record<string, string> = { draft: 'Draft', ready: 'Ready', approved: 'Approved', scheduled: 'Scheduled', posted: 'Posted' };
const rows = computed(() => planned.value.map((it) => ({
    id: it.id,
    ph: tLabel(offset(it.scheduled_at, c.value.starts_on)),
    stage: STAGE[it.stage] ?? it.stage,
    date: eyebrowDate(it.scheduled_at),
    t: it.hook || it.title,
    lang: it.language,
    ch: (it.channels ?? []).map((x: string) => CHANNEL_LABEL[x] ?? x).join(', ') || '—',
    cr: it.credits ? it.credits : 'free',
    st: STATUS[it.status] ?? it.status,
    k: it.credits ? typeDot(it.type) : 'free',
})));

function retry() {
    occ.refresh();
    content.refresh();
}
</script>

<template>
    <div class="wrap">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 360px">
                <span class="eyb"><b>OCCASIONS</b>·<span>{{ eyebrow }}</span></span>
                <h1 class="h-lg">What’s coming, and how to sell around it</h1>
                <p class="ts muted">Festivals, seasons and moments for {{ shop?.city || 'your city' }}, scored for a {{ (shop?.category_label || 'local').toLowerCase() }} shop. Plan pieces before, on and after each day T.</p>
            </div>
            <div class="seg"><button v-for="(f, i) in FILTERS" :key="f[0]" type="button" :class="filter === i ? 'on' : ''" @click="filter = i">{{ f[0] }}</button></div>
            <div class="seg"><NuxtLink v-for="w in WINDOWS" :key="w.key" :to="{ query: { ...route.query, window: w.key } }" :class="win.key === w.key ? 'on' : ''">{{ w.label }}</NuxtLink></div>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
            <section class="panel rise d1" style="flex: 1 1 340px; min-width: 0; overflow: hidden">
                <div class="ph"><span class="eyb"><b>01</b>·<span>{{ win.head }}</span></span><span class="sp"></span><span class="mono muted">{{ count }}</span></div>
                <LoadState :pending="occ.pending.value" :error="occ.error.value" :empty="!vis.length" :rows="6" empty-text="Nothing of this kind coming up" empty-hint="Try another filter or a longer window." @retry="retry">
                    <button v-for="o in list" :key="o.id" type="button" :class="o.cls" @click="pick(o.id)">
                        <span class="dt"><span style="font: 600 18px/1 var(--f); letter-spacing: -0.02em">{{ o.day }}</span><span class="mono" style="font-size: 10px; opacity: .7">{{ o.mon }}</span></span>
                        <span style="min-width: 0"><span class="h-sm" style="display: block">{{ o.n }}</span><span class="tx muted">{{ o.type }} · {{ o.sub }}</span></span>
                        <span :class="o.sc" style="white-space: nowrap">{{ o.st }}</span>
                    </button>
                </LoadState>
            </section>
            <section class="panel rise d2" style="flex: 2 1 560px; min-width: 0">
                <LoadState :pending="occ.pending.value" :error="occ.error.value" :empty="!c" :rows="8" empty-text="Pick an occasion" @retry="retry">
                    <template v-if="c">
                        <div class="ph" style="flex-wrap: wrap"><span class="eyb"><b>02</b>·<span>{{ KIND[c.kind] ?? c.kind }}</span></span><span class="tag">T = {{ when }}</span><span class="sp"></span><span class="tag cr">{{ total }} cr planned</span></div>
                        <div class="pb" style="display: flex; flex-direction: column; gap: 22px">
                            <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start">
                                <div style="flex: 0 0 112px; width: 112px; border-radius: 10px; overflow: hidden"><Photo :name="c.photo_slot" ratio="4:5" :label="false" /></div>
                                <div style="flex: 1 1 240px; min-width: 0; display: flex; flex-direction: column; gap: 8px">
                                    <h2 class="h-lg">{{ c.name }}</h2>
                                    <span v-if="localNames.length" style="display: flex; flex-wrap: wrap; gap: 6px"><span v-for="l in localNames" :key="l.lang" class="tag ghost" :lang="l.lang">{{ l.text }}</span></span>
                                    <p class="ts muted">{{ c.rule }}</p>
                                </div>
                                <div class="receipt" style="flex: 1 1 220px; min-width: 0">
                                    <div class="ln" style="border-bottom: 1px solid #E2E8F0"><span class="muted">when</span><span>{{ countdown }}</span></div>
                                    <div class="ln" style="border-bottom: 1px solid #E2E8F0"><span class="muted">fit for your shop</span><span>{{ fit(c.relevance) }} · {{ Math.round(c.relevance * 100) }}%</span></div>
                                    <div class="ln" style="border-bottom: 1px solid #E2E8F0"><span class="muted">calendar</span><span style="text-transform: capitalize">{{ c.region === 'india' ? 'All India' : c.region }}</span></div>
                                    <div class="ln"><span class="muted">pieces planned</span><span>{{ c.planned_items }}</span></div>
                                </div>
                            </div>
                            <div style="position: relative; overflow: hidden; height: 112px; border-top: 1px solid #E2E8F0; border-bottom: 1px solid #E2E8F0; background: #F8FAFC" class="dots" aria-label="Pieces around day T">
                                <div style="position: absolute; left: 0; right: 0; top: 0; display: grid; grid-template-columns: repeat(22, minmax(0, 1fr))">
                                    <span v-for="(a, i) in axis" :key="i" class="mono" :style="{ fontSize: '9.5px', color: a.c, textAlign: 'center', paddingTop: '6px', borderLeft: `1px solid ${a.b}`, height: '112px' }">{{ a.n }}</span>
                                </div>
                                <NuxtLink v-for="m in marks" :key="m.id" class="mk" :to="{ path: '/app/studio/editor', query: { id: m.id } }" :style="{ left: m.x, top: m.top, animation: 'rise .5s var(--ease) both', animationDelay: m.dl }">
                                    <span :class="['ty', m.k]" style="width: 14px; height: 14px"></span><span class="mono" style="font-size: 10.5px; white-space: nowrap; background: #fff; border: 1px solid #E2E8F0; border-radius: 5px; padding: 3px 6px; color: #0F172A">{{ m.ph }}</span>
                                </NuxtLink>
                                <span v-if="!content.pending.value && !marks.length" class="tx muted" style="position: absolute; left: 50%; top: 58%; transform: translate(-50%, -50%); background: #fff; border: 1px solid #E2E8F0; border-radius: 8px; padding: 6px 10px; white-space: nowrap">Nothing planned around this day yet</span>
                            </div>
                            <div v-if="rows.length" class="tscroll">
                                <table class="tbl" style="border: 1px solid #E2E8F0; border-radius: 10px; border-collapse: separate; border-spacing: 0; overflow: hidden">
                                    <thead><tr><th>Day</th><th>Date</th><th>Piece</th><th>Where</th><th style="text-align: right">Credits</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <tr v-for="r in rows" :key="r.id" style="cursor: pointer" @click="navigateTo({ path: '/app/studio/editor', query: { id: r.id } })">
                                            <td><span style="display: inline-flex; gap: 8px; align-items: center"><span :class="['ty', r.k]"></span><b style="font-weight: 500">{{ r.ph }}</b></span><span class="tx muted" style="display: block">{{ r.stage }}</span></td>
                                            <td class="mono">{{ r.date }}</td>
                                            <td :lang="r.lang">{{ r.t }}</td>
                                            <td class="muted">{{ r.ch }}</td>
                                            <td class="mono" style="text-align: right">{{ r.cr }}</td>
                                            <td>{{ r.st }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center">
                                <div class="note" style="flex: 1 1 360px"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7.5 3v5.5c0 4.7-3.2 8.3-7.5 9.5-4.3-1.2-7.5-4.8-7.5-9.5V6z"></path></svg><span>“Last call” runs only if the offer truly continues. No selling around sad news, no political or religious debate.</span></div>
                                <div style="display: flex; flex-wrap: wrap; gap: 8px">
                                    <NuxtLink class="btn bs lg" to="/app/studio/plan">Plan it</NuxtLink>
                                    <NuxtLink class="btn bp lg" :to="{ path: '/app/create', query: { occasion_id: c.id } }">Make a post</NuxtLink>
                                </div>
                            </div>
                        </div>
                    </template>
                </LoadState>
            </section>
        </div>
    </div>
</template>

<style scoped>
.oc { display: grid; grid-template-columns: 52px minmax(0, 1fr) auto; gap: 14px; align-items: center; width: 100%; padding: 12px 16px; border: 0; border-bottom: 1px solid #E2E8F0; background: none; text-align: left; cursor: pointer; transition: background .15s; }
.oc:hover { background: #F8FAFC; }
.oc.on { background: #F1F5F9; }
.dt { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 52px; border-radius: 10px; border: 1px solid #E2E8F0; background: #fff; }
.oc.on .dt { background: #0F172A; color: #fff; border-color: #0F172A; }
.mk { position: absolute; top: 28px; transform: translateX(-50%); display: flex; flex-direction: row; align-items: center; gap: 6px; transition: opacity .3s; text-decoration: none; }
</style>
