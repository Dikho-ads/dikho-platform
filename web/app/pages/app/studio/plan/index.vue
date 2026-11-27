<script setup lang="ts">
// Plan mode brief: what the owner does today, what they want, for whom, what to sell, when, budget,
// channels and voice. "Make the plan" posts the brief and opens the generated plan.
import type { Ref } from 'vue';

definePageMeta({ layout: 'app', title: 'Plan mode' });

const auth = useAuth();
const api = useApi();
const toast = useToast();
const route = useRoute();

// ---------- months ----------
const ymKey = (d: Date) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
const addMonths = (n: number) => {
    const d = new Date();
    return new Date(d.getFullYear(), d.getMonth() + n, 1);
};
const MONTHS = [0, 1, 2].map((n) => ({ key: ymKey(addMonths(n)), label: monthName(addMonths(n)), short: monthName(addMonths(n), 'short') }));
const ALL_KEYS = [-1, 0, 1, 2].map((n) => ymKey(addMonths(n)));
const monthOf = (key: string) => new Date(Number(key.slice(0, 4)), Number(key.slice(5, 7)) - 1, 1);
const monthLabel = (key: string, style: 'long' | 'short' = 'long') => monthName(monthOf(key), style);

// ---------- data ----------
const fromId = computed(() => (route.query.from ? String(route.query.from) : ''));
const shopReq = useApiData<any>('plan-brief-shop', 'shop');
const sourceReq = useAsyncData<any>(
    () => `plan-brief-source-${fromId.value || 'none'}`,
    () => (fromId.value ? api<any>(`plans/id/${fromId.value}`).then((r) => r.data) : Promise.resolve(null)),
);
await Promise.all([shopReq, sourceReq]);

const shop = computed(() => shopReq.data.value?.data ?? auth.shop ?? {});
const source = computed(() => sourceReq.data.value);

const existingReq = useAsyncData<any[]>(
    'plan-brief-existing',
    () =>
        Promise.all(ALL_KEYS.map((m) => api<any>(`plans/${m}`).then((r) => r.plan).catch(() => null))).then((list) =>
            list.filter(Boolean).map((p: any) => ({ id: p.id, month: p.month, status: p.status, version: p.version, approved_at: p.approved_at, goal_label: p.brief?.goal_label || p.strategy?.goal?.label })),
        ),
    { lazy: true },
);
const resultsReq = useApiData<any>('plan-brief-results', 'results', { query: () => ({ range: 'month' }) });
// Not fatal when it fails: the enquiry count just starts empty.
await resultsReq;

// ---------- form ----------
const G = [
    ['footfall', 'FOOTFALL', 'More walk-ins', 'People at the counter'],
    ['leads', 'LEADS', 'WhatsApp enquiries', 'Chats and calls'],
    ['repeat', 'REPEAT', 'Old customers back', 'Win-back and wishes'],
    ['launch', 'LAUNCH', 'Launch something', 'New stock or service'],
    ['awareness', 'AWARENESS', 'Get known', 'New areas hear of you'],
];
const UNITS = ['walk-ins (QR scans) / month', 'enquiries / month', 'customers back / month', 'enquiries for the launch', 'people reached / month'];
const CH = [
    ['whatsapp_status', 'WhatsApp Status'],
    ['instagram', 'Instagram'],
    ['facebook', 'Facebook'],
    ['google', 'Google Business'],
    ['broadcast', 'WhatsApp to customers'],
    ['sms', 'SMS'],
    ['meta_ad', 'Meta ads'],
    ['print', 'Print - flex, pamphlet'],
];
const SCALES = [
    ['formal_apnapan', 'Formal', 'Apnapan'],
    ['serious_mazedaar', 'Serious', 'Mazedaar'],
    ['budget_premium', 'Budget', 'Premium'],
];
const CH_FREQ = ['Low', 'Med', 'High'];
const AUD_BASE = ['Families', 'College students', 'Young workers', 'Farmers', 'Senior citizens', 'Shopkeepers'];

const brief: any = source.value?.brief ?? {};
const shopAud: string[] = shop.value?.audience?.types ?? [];
const products: string[] = (shop.value?.products ?? []).map((p: any) => p.name);
const heroes: string[] = (shop.value?.products ?? []).filter((p: any) => p.is_hero).map((p: any) => p.name);
const left = computed(() => auth.account?.credits?.left ?? 0);
const walletBalance = computed(() => Math.floor((auth.account?.wallet_paise ?? 0) / 100));

// Notes carry the free-text parts of the brief; split them back when copying an earlier brief.
function splitNotes(text: string) {
    const out = { offer: '', floor: '', worked: '', didnt: '', rest: [] as string[] };
    for (const line of (text || '').split('\n')) {
        if (line.startsWith('Main offer: ')) out.offer = line.slice(12);
        else if (line.startsWith('Never advertise below ₹')) out.floor = line.slice(23);
        else if (line.startsWith('What worked: ')) out.worked = line.slice(13);
        else if (line.startsWith("What didn't: ")) out.didnt = line.slice(13);
        else if (line.trim()) out.rest.push(line);
    }
    return out;
}
const n0 = splitNotes(brief.notes ?? '');

const defaultMonth = brief.month && MONTHS.some((m) => m.key === brief.month) ? brief.month : MONTHS[1]!.key;
const month = ref<string>(defaultMonth);
const goal = ref(Math.max(0, G.findIndex((g) => g[0] === brief.goal)) || (brief.goal ? 0 : 1));
const nowEnq = ref<number | null>(brief.current_enquiries ?? null);
const target = ref<number | null>(brief.target ?? null);
const aud = ref<string[]>(brief.audience ?? [...shopAud]);
const areas = ref<string[]>(brief.areas ?? (shop.value?.area ? [shop.value.area] : []));
const langs = ref<string[]>(brief.languages ?? [...(shop.value?.languages ?? ['en'])]);
const prods = ref<string[]>(brief.products ?? (heroes.length ? heroes : products.slice(0, 3)));
const occ = ref<string[]>(brief.occasions ?? []);
const occTouched = ref(!!brief.occasions);
const credits = ref<number>(Math.min(brief.credits ?? 140, left.value));
const wallet = ref<number>(brief.wallet_paise != null ? Math.round(brief.wallet_paise / 100) : Math.min(3000, Math.floor(walletBalance.value / 500) * 500));
const chOff = ref<string[]>(
    Array.isArray(brief.channels) ? CH.map((c) => c[0]!).filter((k) => !brief.channels.some((x: any) => x.key === k)) : ['print'],
);
const ch = reactive<Record<string, number>>(
    Object.fromEntries(CH.map(([k]) => [k, Math.max(0, CH_FREQ.indexOf((brief.channels ?? []).find((x: any) => x.key === k)?.level ?? 'Med'))])),
);
const sc = ref<number[]>(SCALES.map(([k]) => brief.tone?.[k!] ?? 2));
const always = ref<string[]>(brief.tone?.always ?? []);
const never = ref<string[]>(brief.tone?.never ?? []);
const offer = ref(n0.offer);
const floor = ref(n0.floor);
const worked = ref(n0.worked);
const didnt = ref(n0.didnt);
const extraNotes = ref(n0.rest.join('\n'));
const newArea = ref('');
const newAlways = ref('');
const newNever = ref('');

// Defaults that come from the last 30 days once results load (only when the brief did not set them).
watch(
    () => resultsReq.data.value,
    (r) => {
        const chats = r?.totals?.chats?.value;
        if (nowEnq.value == null && chats != null) nowEnq.value = chats;
        if (target.value == null && nowEnq.value != null) target.value = Math.max(10, Math.round((nowEnq.value * 1.5) / 5) * 5);
    },
    { immediate: true },
);

// ---------- occasions for the chosen month ----------
const monthStart = computed(() => {
    const isNow = month.value === MONTHS[0]!.key;
    return isNow ? isoDate(new Date()) : `${month.value}-01`;
});
const monthEnd = computed(() => {
    const d = monthOf(month.value);
    return isoDate(new Date(d.getFullYear(), d.getMonth() + 1, 0));
});
const occReq = useApiData<any>(() => `plan-brief-occ-${month.value}`, 'occasions', {
    query: () => ({ from: monthStart.value, to: monthEnd.value }),
    lazy: true,
});
watch(month, () => (occTouched.value = false));
const occasions = computed<any[]>(() => occReq.data.value?.data ?? []);
// Until the owner touches the list, the most relevant occasions of the month are picked.
const occDefault = computed(() => occasions.value.filter((o) => (o.relevance ?? 0) >= 0.6).map((o) => o.slug));
const occSel = computed(() => (occTouched.value ? occ.value : occDefault.value));
const occDates = (o: any) => {
    const a = new Date(o.starts_on + 'T00:00:00');
    const b = new Date(o.ends_on + 'T00:00:00');
    const mon = (d: Date) => monthName(d, 'short').toUpperCase();
    if (o.starts_on === o.ends_on) return `${a.getDate()} ${mon(a)}`;
    return a.getMonth() === b.getMonth() ? `${a.getDate()} – ${b.getDate()} ${mon(b)}` : `${a.getDate()} ${mon(a)} – ${b.getDate()} ${mon(b)}`;
};

// ---------- options ----------
const AUD = computed(() => [...new Set([...shopAud, ...AUD_BASE, ...aud.value])]);
const AREAS = computed(() => [...new Set([shop.value?.area, ...areas.value].filter(Boolean))] as string[]);
const LANGS = computed(() => [...new Set([...(shop.value?.languages ?? []), ...Object.keys(LANG_LABEL)])]);
const PRODS = computed(() => [...new Set([...products, ...prods.value])]);

function tog(list: Ref<string[]>, n: string) {
    list.value = list.value.includes(n) ? list.value.filter((x) => x !== n) : list.value.concat(n);
}
function togOcc(slug: string) {
    if (!occTouched.value) occ.value = [...occDefault.value];
    occTouched.value = true;
    tog(occ, slug);
}
function addTo(list: Ref<string[]>, input: Ref<string>) {
    const v = input.value.trim();
    if (v && !list.value.includes(v)) list.value = list.value.concat(v);
    input.value = '';
}
const chanOn = (k: string) => !chOff.value.includes(k);
function setScale(i: number, e: Event) {
    const a = sc.value.slice();
    a[i] = +(e.target as HTMLInputElement).value;
    sc.value = a;
}

// ---------- derived ----------
const unit = computed(() => UNITS[goal.value]!);
const mult = computed(() => (nowEnq.value && target.value ? Math.round((target.value / nowEnq.value) * 10) / 10 : 0));
const stretch = computed(() => goal.value === 1 && mult.value >= 3);
const walletMax = computed(() => Math.max(10000, Math.ceil(walletBalance.value / 500) * 500, wallet.value));
const walletNote = computed(() =>
    wallet.value === 0
        ? 'No ads - the plan will rely on Status, Google and your customer list.'
        : 'Suggested split: ' + rupees(Math.round(wallet.value * 0.8)) + ' Meta ads · ' + rupees(Math.round(wallet.value * 0.15)) + ' WhatsApp · ' + rupees(Math.round(wallet.value * 0.05)) + ' SMS',
);
const ready = computed(() => {
    const filled = [nowEnq.value != null ? 1 : 0, target.value ? 1 : 0, aud.value.length ? 1 : 0, areas.value.length ? 1 : 0, langs.value.length ? 1 : 0, prods.value.length ? 1 : 0, occSel.value.length ? 1 : 0, credits.value > 0 ? 1 : 0];
    return Math.round((filled.reduce((a, b) => a + b, 0) / filled.length) * 100);
});
const rt = computed(() => Array.from({ length: 50 }, (_, i) => ({ c: i < ready.value / 2 ? 'on' : '', d: (i * 0.01).toFixed(2) + 's' })));
const monthLong = computed(() => monthLabel(month.value));
// The plan makes one piece a day: from tomorrow when it is this month, else the whole month.
const pieces = computed(() => {
    const end = new Date(monthEnd.value + 'T00:00:00').getDate();
    if (month.value !== MONTHS[0]!.key) return end;
    return Math.max(0, end - new Date().getDate());
});
const summary = computed(() => [
    { k: 'today', v: nowEnq.value != null ? num(nowEnq.value) + ' enquiries / mo' : '-' },
    { k: 'goal', v: target.value ? num(target.value) + ' ' + unit.value.split(' /')[0] : '-' },
    { k: 'for', v: aud.value.slice(0, 2).join(', ') || '-' },
    { k: 'where', v: areas.value.length + (areas.value.length === 1 ? ' area of ' : ' areas of ') + (shop.value?.city ?? '') },
    { k: 'when', v: monthLong.value },
    { k: 'credits', v: num(credits.value) + ' of ' + num(left.value) },
    { k: 'wallet', v: rupees(wallet.value) },
    { k: 'channels', v: CH.length - chOff.value.length + ' of ' + CH.length },
]);

const existing = computed<any[]>(() => existingReq.data.value ?? []);
const forMonth = computed(() => existing.value.find((p) => p.month === month.value));
const earlier = computed(() => [...existing.value].sort((a, b) => (a.month < b.month ? 1 : -1)));
const planLine = (p: any) =>
    `${monthLabel(p.month, 'short')} · v${p.version} · ` + (p.status === 'approved' && p.approved_at ? 'approved ' + day(p.approved_at) : 'waiting for you');

// ---------- make ----------
const stage = ref<'idle' | 'making'>('idle');
const gs = ref(0);
const errors = ref<Record<string, string>>({});
const GS = computed(() => [
    'Reading your brief',
    'Checking ' + (shop.value?.city ?? 'your city') + ' calendar',
    'Splitting budget across channels',
    'Writing the ' + monthLong.value + ' calendar',
    'Drafting content in ' + langs.value.length + (langs.value.length === 1 ? ' language' : ' languages'),
]);
const gsteps = computed(() => GS.value.map((n, i) => ({ n, sc: i < gs.value ? 'st ok' : i === gs.value ? 'st acc live' : 'st' })));
let iv: ReturnType<typeof setInterval> | null = null;
onBeforeUnmount(() => iv && clearInterval(iv));

function notes() {
    return [
        offer.value.trim() && `Main offer: ${offer.value.trim()}`,
        floor.value.trim() && `Never advertise below ₹${floor.value.trim()}`,
        worked.value.trim() && `What worked: ${worked.value.trim().replace(/\n+/g, ' ')}`,
        didnt.value.trim() && `What didn't: ${didnt.value.trim().replace(/\n+/g, ' ')}`,
        extraNotes.value.trim(),
    ]
        .filter(Boolean)
        .join('\n')
        .slice(0, 1000);
}

async function make() {
    if (!langs.value.length) {
        errors.value = { languages: 'Pick at least one language.' };
        return;
    }
    errors.value = {};
    stage.value = 'making';
    gs.value = 0;
    iv = setInterval(() => {
        if (gs.value < GS.value.length - 1) gs.value += 1;
    }, 550);
    try {
        const res = await api<any>('plans', {
            method: 'POST',
            body: {
                month: month.value,
                goal: G[goal.value]![0],
                goal_label: G[goal.value]![2],
                current_enquiries: nowEnq.value ?? undefined,
                target: target.value || undefined,
                audience: aud.value,
                areas: areas.value,
                languages: langs.value,
                products: prods.value,
                occasions: occSel.value,
                channels: CH.filter(([k]) => chanOn(k!)).map(([k]) => ({ key: k, level: CH_FREQ[ch[k!] ?? 1] })),
                tone: { ...Object.fromEntries(SCALES.map(([k], i) => [k, sc.value[i]])), always: always.value, never: never.value },
                credits: credits.value,
                wallet_paise: wallet.value * 100,
                notes: notes() || undefined,
            },
        });
        gs.value = GS.value.length;
        toast.ok(`Plan for ${monthLong.value} is ready · ${res.data.items?.length ?? 0} pieces to review`);
        await navigateTo({ path: '/app/studio/plan/result', query: { id: res.data.id } });
    } catch (e: any) {
        const err = toApiError(e);
        errors.value = Object.fromEntries(Object.keys(err.errors).map((k) => [k.split('.')[0], err.field(k)!]));
        toast.error(err.message);
        stage.value = 'idle';
    } finally {
        if (iv) clearInterval(iv);
        iv = null;
    }
}
</script>

<template>
    <div class="wrap" style="max-width: 1320px">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 460px">
                <span class="eyb"><b>PLAN MODE</b>·<span>अपना प्लान · BRIEF</span></span>
                <h1 class="h-lg">How do you market today - and what do you want next?</h1>
                <p class="ts muted">Fill what you know, skip the rest. Dikho writes the plan, the calendar and every piece of content from this.</p>
            </div>
            <div class="seg"><NuxtLink to="/app/studio/auto">Auto</NuxtLink><button type="button" class="on">Plan</button></div>
        </div>

        <LoadState :pending="false" :error="shopReq.error.value" @retry="shopReq.refresh()">
            <div class="panel rise d1 dots" style="padding: 18px 20px; display: flex; flex-wrap: wrap; gap: 18px; align-items: center; background-color: #f8fafc">
                <span class="lm">Plan for</span>
                <div class="seg">
                    <button v-for="m in MONTHS" :key="m.key" type="button" :class="month === m.key ? 'on' : ''" @click="month = m.key">{{ m.label }}</button>
                </div>
                <p v-if="forMonth" class="ts" style="flex: 1 1 320px; color: #334155">
                    You already have a {{ monthLong }} plan (v{{ forMonth.version }}, {{ forMonth.status === 'approved' ? 'approved' : 'waiting for your approval' }}). Making a new one adds v{{ forMonth.version + 1 }}.
                </p>
                <p v-else class="ts" style="flex: 1 1 320px; color: #334155">No plan for {{ monthLong }} yet. Making one costs nothing - credits are spent only when you approve it.</p>
                <NuxtLink v-if="forMonth" class="btn bs" :to="{ path: '/app/studio/plan/result', query: { id: forMonth.id } }">Review it</NuxtLink>
                <span v-else-if="source" class="st ok">Copied from {{ monthLabel(source.month, 'short') }} v{{ source.version }}</span>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start">
                <div style="flex: 2.2 1 620px; min-width: 0; display: flex; flex-direction: column; gap: 18px">
                    <section class="panel pb sec rise d2">
                        <div class="shead"><span class="eyb"><b>01</b>·<span>Abhi kya ho raha hai</span></span><span class="tx muted">Your marketing today</span></div>
                        <div class="fld">
                            <span class="lab">Enquiries per month now</span>
                            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap">
                                <button type="button" class="btn bs ib sm" aria-label="Fewer" @click="nowEnq = Math.max(0, (nowEnq ?? 0) - 5)">−</button>
                                <span class="num" style="font-size: 22px; min-width: 44px; text-align: center">{{ nowEnq ?? '–' }}</span>
                                <button type="button" class="btn bs ib sm" aria-label="More" @click="nowEnq = (nowEnq ?? 0) + 5">+</button>
                                <span v-if="resultsReq.data.value?.totals?.chats" class="tx muted">roughly · Dikho counted {{ num(resultsReq.data.value.totals.chats.value) }} chats in the last 30 days</span>
                                <span v-else class="tx muted">roughly</span>
                            </div>
                            <span v-if="errors.current_enquiries" class="err">{{ errors.current_enquiries }}</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(260px, 100%), 1fr)); gap: 12px">
                            <div class="fld">
                                <label class="lab" for="w1">What worked</label>
                                <textarea id="w1" v-model="worked" class="in" style="height: 64px; padding: 9px 11px; resize: none; line-height: 1.45" placeholder="e.g. Offers on Status bring calls"></textarea>
                            </div>
                            <div class="fld">
                                <label class="lab" for="w2">What didn’t</label>
                                <textarea id="w2" v-model="didnt" class="in" style="height: 64px; padding: 9px 11px; resize: none; line-height: 1.45" placeholder="e.g. Boosted posts gave likes, not customers"></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="panel pb sec rise d3">
                        <div class="shead"><span class="eyb"><b>02</b>·<span>Kya chahiye</span></span><span class="tx muted">One main goal</span></div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(160px, 100%), 1fr)); gap: 10px">
                            <button v-for="(g, i) in G" :key="g[0]" type="button" :class="'goal' + (goal === i ? ' on' : '')" @click="goal = i">
                                <span class="mono muted" style="font-size: 10.5px">{{ g[1] }}</span><span class="h-sm">{{ g[2] }}</span><span class="tx muted">{{ g[3] }}</span>
                            </button>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center">
                            <span class="ts">Target</span>
                            <div style="display: flex; gap: 8px; align-items: center">
                                <button type="button" class="btn bs ib sm" aria-label="Lower target" @click="target = Math.max(10, (target ?? 10) - 10)">−</button>
                                <span class="num" style="font-size: 26px; min-width: 56px; text-align: center">{{ target ? num(target) : '–' }}</span>
                                <button type="button" class="btn bs ib sm" aria-label="Higher target" @click="target = (target ?? 0) + 10">+</button>
                            </div>
                            <span class="ts muted">{{ unit }}</span>
                            <span class="sp"></span>
                            <span class="mono muted" style="font-size: 12px">{{ monthLong.toUpperCase() }}</span>
                        </div>
                        <span v-if="stretch" class="st warn">That’s {{ mult }}× today. Doable with ads, but the plan will lean on your wallet.</span>
                        <span v-if="errors.target" class="err">{{ errors.target }}</span>
                    </section>

                    <section class="panel pb sec rise d4">
                        <div class="shead"><span class="eyb"><b>03</b>·<span>Kisko</span></span><span class="tx muted">Who and where</span></div>
                        <div class="fld">
                            <span class="lab">People</span>
                            <div class="chips"><button v-for="n in AUD" :key="n" type="button" :class="'chip' + (aud.includes(n) ? ' on' : '')" @click="tog(aud, n)">{{ n }}</button></div>
                        </div>
                        <div class="fld">
                            <span class="lab">Areas of {{ shop.city }}</span>
                            <div class="chips">
                                <button v-for="n in AREAS" :key="n" type="button" :class="'chip' + (areas.includes(n) ? ' on' : '')" @click="tog(areas, n)">{{ n }}</button>
                                <form class="addf" @submit.prevent="addTo(areas, newArea)">
                                    <input v-model="newArea" class="in" maxlength="40" placeholder="+ add an area" aria-label="Add an area" />
                                </form>
                            </div>
                        </div>
                        <div class="fld">
                            <span class="lab">Languages</span>
                            <div class="chips"><button v-for="n in LANGS" :key="n" type="button" :class="'chip' + (langs.includes(n) ? ' on' : '')" @click="tog(langs, n)">{{ LANG_LABEL[n] || n }}</button></div>
                            <span v-if="errors.languages" class="err">{{ errors.languages }}</span>
                        </div>
                    </section>

                    <section class="panel pb sec rise d5">
                        <div class="shead"><span class="eyb"><b>04</b>·<span>Kya bechna hai</span></span><span class="tx muted">Products and offers</span></div>
                        <div v-if="PRODS.length" class="chips">
                            <button v-for="n in PRODS" :key="n" type="button" :class="'chip' + (prods.includes(n) ? ' on' : '')" @click="tog(prods, n)">{{ n }}</button>
                        </div>
                        <p v-else class="ts muted">No products saved yet. <NuxtLink class="lnk" to="/app/settings">Add them in Settings</NuxtLink> so the plan can name them.</p>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(240px, 100%), 1fr)); gap: 12px">
                            <div class="fld"><label class="lab" for="ho">Main offer</label><input id="ho" v-model="offer" class="in" maxlength="160" placeholder="e.g. 250 g free on a 1 kg box" /></div>
                            <div class="fld">
                                <label class="lab" for="fl">Never advertise below</label>
                                <div class="iw"><span class="pre">₹</span><input id="fl" v-model="floor" class="in" inputmode="numeric" maxlength="12" placeholder="optional" /></div>
                                <span class="hint">The plan won’t promise a price lower than this.</span>
                            </div>
                        </div>
                    </section>

                    <section class="panel pb sec rise d6">
                        <div class="shead"><span class="eyb"><b>05</b>·<span>Kab</span></span><span class="tx muted">Occasions in {{ monthLong }}</span></div>
                        <LoadState :pending="occReq.pending.value && !occasions.length" :error="occReq.error.value" :empty="!occasions.length" empty-text="No occasions on the calendar this month" empty-hint="The plan will use your weekly rhythm instead." :rows="2" @retry="occReq.refresh()">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(220px, 100%), 1fr)); gap: 8px">
                                <button v-for="o in occasions" :key="o.id" type="button" :class="'opt' + (occSel.includes(o.slug) ? ' on' : '')" style="padding: 10px 12px" @click="togOcc(o.slug)">
                                    <span class="ck"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg></span>
                                    <span class="grow"><span class="ts" style="font-weight: 500; display: block">{{ o.name }}</span><span class="mono muted" style="font-size: 11px">{{ occDates(o) }}</span></span>
                                </button>
                            </div>
                        </LoadState>
                    </section>

                    <section class="panel pb sec rise d7">
                        <div class="shead"><span class="eyb"><b>06</b>·<span>Budget</span></span><span class="tx muted">Credits and wallet</span></div>
                        <div class="fld">
                            <span style="display: flex; justify-content: space-between"><label class="lab" for="cr">Credits to use</label><span class="mono">{{ num(credits) }} of {{ num(left) }}</span></span>
                            <input id="cr" v-model.number="credits" type="range" min="0" :max="left" step="1" aria-label="Credits to use" :disabled="!left" />
                            <span v-if="!left" class="hint">You have no credits left. Status pieces are free; <NuxtLink class="lnk" to="/app/settings?tab=billing">top up</NuxtLink> for reels and posts.</span>
                            <span v-else class="hint">Spent only when you approve the plan. Daily Status pieces are free.</span>
                            <span v-if="errors.credits" class="err">{{ errors.credits }}</span>
                        </div>
                        <div class="fld">
                            <span style="display: flex; justify-content: space-between"><label class="lab" for="wl">Ad &amp; message wallet</label><span class="mono">{{ rupees(wallet) }}</span></span>
                            <input id="wl" v-model.number="wallet" type="range" min="0" :max="walletMax" step="500" aria-label="Wallet budget" />
                            <span class="hint">{{ walletNote }}</span>
                            <span class="hint">Wallet balance now: {{ rupees(walletBalance) }}<template v-if="wallet > walletBalance"> · top up before ads and messages go out</template></span>
                            <span v-if="errors.wallet_paise" class="err">{{ errors.wallet_paise }}</span>
                        </div>
                    </section>

                    <section class="panel pb sec rise d8">
                        <div class="shead"><span class="eyb"><b>07</b>·<span>Kahan</span></span><span class="tx muted">Channels and how often</span></div>
                        <div>
                            <div v-for="[k, n] in CH" :key="k" class="crow">
                                <span style="display: flex; gap: 10px; align-items: center">
                                    <button type="button" :class="'sw' + (chanOn(k!) ? ' on' : '')" :aria-label="'Use ' + n" @click="tog(chOff, k!)"></button>
                                    <span class="ts" :style="{ fontWeight: 500, opacity: chanOn(k!) ? 1 : 0.45 }">{{ n }}</span>
                                </span>
                                <span class="seg" :style="{ opacity: chanOn(k!) ? 1 : 0.45 }">
                                    <button v-for="(o, i) in CH_FREQ" :key="o" type="button" :class="ch[k!] === i ? 'on' : ''" @click="ch[k!] = i">{{ o }}</button>
                                </span>
                            </div>
                        </div>
                    </section>

                    <section class="panel pb sec rise d8">
                        <div class="shead"><span class="eyb"><b>08</b>·<span>Kaise bolna hai</span></span><span class="tx muted">Your voice</span></div>
                        <div v-for="(x, i) in SCALES" :key="x[0]" class="scale">
                            <span class="ts muted" style="text-align: right">{{ x[1] }}</span>
                            <input type="range" min="0" max="4" step="1" :value="sc[i]" :aria-label="x[1] + ' to ' + x[2]" @input="setScale(i, $event)" />
                            <span class="ts muted">{{ x[2] }}</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(240px, 100%), 1fr)); gap: 12px">
                            <div class="fld">
                                <span class="lab">Always say</span>
                                <div class="chips">
                                    <button v-for="w in always" :key="w" type="button" class="chip on" :title="'Remove ' + w" @click="tog(always, w)">{{ w }} ×</button>
                                    <form class="addf" @submit.prevent="addTo(always, newAlways)"><input v-model="newAlways" class="in" maxlength="40" placeholder="+ add" aria-label="Add a phrase to always say" /></form>
                                </div>
                            </div>
                            <div class="fld">
                                <span class="lab">Never say</span>
                                <div class="chips">
                                    <button v-for="w in never" :key="w" type="button" class="chip on" :title="'Remove ' + w" @click="tog(never, w)">{{ w }} ×</button>
                                    <form class="addf" @submit.prevent="addTo(never, newNever)"><input v-model="newNever" class="in" maxlength="40" placeholder="+ add" aria-label="Add a phrase to never say" /></form>
                                </div>
                            </div>
                        </div>
                        <div class="fld">
                            <label class="lab" for="xn">Anything else</label>
                            <textarea id="xn" v-model="extraNotes" class="in" style="height: 64px; padding: 9px 11px; resize: none; line-height: 1.45" maxlength="500" placeholder="Optional notes for the plan"></textarea>
                            <span v-if="errors.notes" class="err">{{ errors.notes }}</span>
                        </div>
                    </section>
                </div>

                <aside style="flex: 1 1 300px; min-width: 0; display: flex; flex-direction: column; gap: 16px" class="rise d3">
                    <section class="panel">
                        <div class="ph"><span class="eyb"><b>BRIEF</b>·<span>Summary</span></span><span class="sp"></span><span class="mono">{{ ready }}%</span></div>
                        <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
                            <div class="ticks sm"><i v-for="(k, i) in rt" :key="i" :class="k.c" :style="{ animationDelay: k.d }"></i></div>
                            <div class="receipt">
                                <div v-for="x in summary" :key="x.k" class="ln" style="border-bottom: 1px dashed #e2e8f0"><span class="muted">{{ x.k }}</span><span style="text-align: right">{{ x.v }}</span></div>
                            </div>
                            <div class="note">
                                <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6zM14 3v5h5"></path></svg>
                                <span>Dikho will write: a strategy, a day-by-day {{ monthLong }} calendar and {{ pieces }} pieces in {{ langs.length }} {{ langs.length === 1 ? 'language' : 'languages' }}. Nothing goes out until you approve.</span>
                            </div>
                            <button v-if="stage === 'idle'" type="button" class="btn bp lg full" :disabled="!pieces" @click="make">Make the plan</button>
                            <span v-if="!pieces" class="hint">{{ monthLong }} has no days left to plan. Pick next month.</span>
                            <div v-if="stage === 'making'" class="fade" style="display: flex; flex-direction: column; gap: 8px" aria-live="polite">
                                <span v-for="g in gsteps" :key="g.n" :class="g.sc" style="font-size: 12.5px">{{ g.n }}</span>
                            </div>
                        </div>
                    </section>
                    <section class="panel pb" style="display: flex; flex-direction: column; gap: 8px">
                        <span class="lm">Recent plans</span>
                        <LoadState :pending="existingReq.pending.value" :error="existingReq.error.value" :empty="!earlier.length" empty-text="No plans yet" empty-hint="Your first plan will show up here." :rows="2" @retry="existingReq.refresh()">
                            <NuxtLink v-for="p in earlier" :key="p.id" class="row" :to="{ path: '/app/studio/plan/result', query: { id: p.id } }" style="padding: 8px 0">
                                <span class="grow"><span class="ts" style="font-weight: 500; display: block">{{ p.goal_label || 'Month plan' }} · {{ monthLabel(p.month) }}</span><span class="tx muted">{{ planLine(p) }}</span></span>
                                <span :class="p.status === 'approved' ? 'st ok' : 'st warn'">{{ p.status === 'approved' ? 'Approved' : 'Review' }}</span>
                            </NuxtLink>
                        </LoadState>
                    </section>
                </aside>
            </div>
        </LoadState>
    </div>
</template>

<style scoped>
.sec{display:flex;flex-direction:column;gap:14px}
.shead{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap}
.goal{display:flex;flex-direction:column;gap:6px;padding:14px;border:1px solid #E2E8F0;border-radius:12px;background:#fff;text-align:left;cursor:pointer;transition:border-color .15s,box-shadow .15s,transform .2s var(--ease)}
.goal:hover{transform:translateY(-2px);border-color:#CBD5E1}
.goal.on{border-color:#0F172A;box-shadow:inset 0 0 0 1px #0F172A}
.crow{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;padding:9px 0;border-bottom:1px dashed #E2E8F0}
@media (max-width:599px){.crow{grid-template-columns:minmax(0,1fr);gap:8px}.crow .seg{display:flex;width:100%}.crow .seg button{flex:1}}
input[type=range]{accent-color:#0F172A;width:100%}
.scale{display:grid;grid-template-columns:110px minmax(0,1fr) 110px;gap:12px;align-items:center}
@media (max-width:420px){.scale{grid-template-columns:70px minmax(0,1fr) 70px}}
.addf{display:inline-flex}
.addf .in{height:32px;width:150px;border-radius:999px;font-size:13px;padding:0 12px}
</style>
