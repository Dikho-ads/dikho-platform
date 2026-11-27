<script setup lang="ts">
// Shop setup: 10 steps, each saved with POST shop/setup/{n} when the owner presses Continue.
// Everything is prefilled from GET shop; the last step finishes with POST shop/setup/complete.
// A shop that already finished setup can still review its answers; the plan step then only
// shows the current plan (changes go through Settings → Billing) and the last button opens /app.
type Pkg = { id: string; name: string; price_paise: number; credits: number; blurb: string; features: string[] };
type Item = { name: string; price: string; price_paise: number | null };

useHead({ title: 'Shop setup' });

const auth = useAuth();
const api = useApi();
const toast = useToast();
const { confirm } = useConfirm();

const S = [
    ['Shop basics', 'Shop', 'Tell us about your shop', 'Picks your templates, tone and category playbook.'],
    ['Location', 'Where', 'Where is the shop?', 'Sets your local festivals, language and how far ads reach.'],
    ['Contact & hours', 'Reach', 'How customers reach you', 'Becomes the button on every post. No posts when you’re closed.'],
    ['What you sell', 'Catalogue', 'What do you sell most?', 'Real products and real prices go into your ads - never stock photos.'],
    ['Who buys', 'Audience', 'Who buys from you?', 'Shapes the hooks, the languages and who sees your ads.'],
    ['Look', 'Brand', 'Your shop’s look', 'Colours come from your signboard, so every post looks like your shop.'],
    ['Photos', 'Proof', 'Five photos of the real shop', 'These are the photos your posts use. Real faces build trust.'],
    ['Goal', 'Focus', 'What do you want most right now?', 'Pick 1 or 2. Your monthly plan leans towards these.'],
    ['Customers', 'Optional', 'Bring your regular customers', 'So we can thank them - and notice when someone stops coming.'],
    ['Plan', 'Price', 'Pick a plan', 'One fixed price. Mix anything inside your credits.'],
] as const;
const CATS: [string, string, string][] = [
    ['kirana', 'Kirana', 'M4 9h16l-1.5 10.5a1 1 0 0 1-1 .5h-11a1 1 0 0 1-1-.5zM8 9l4-5 4 5'],
    ['mobile', 'Mobile & electronics', 'M8.5 2.5h7A2.5 2.5 0 0 1 18 5v14a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 6 19V5a2.5 2.5 0 0 1 2.5-2.5zM11 18.5h2'],
    ['salon', 'Salon', 'M8.5 6a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0zM8.5 18a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0zM8 7.5L20 18M8 16.5L20 6'],
    ['sweets', 'Sweet shop', 'M3 9h18v11H3zM3 9l3-5h12l3 5M12 9v11'],
    ['clinic', 'Clinic', 'M6.5 3.5h11a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3h-11a3 3 0 0 1-3-3v-11a3 3 0 0 1 3-3zM12 8v8M8 12h8'],
    ['coaching', 'Coaching centre', 'M4 5a2 2 0 0 1 2-2h13v15H6a2 2 0 0 0-2 2zM4 20a2 2 0 0 0 2 1h13'],
];
// [label, km, map radius]
const RAD: [string, number, number][] = [['None', 0, 0], ['1 km', 1, 30], ['2 km', 2, 52], ['3 km', 3, 74], ['5 km', 5, 104]];
const WEEK: [string, string][] = [['mon', 'Mon'], ['tue', 'Tue'], ['wed', 'Wed'], ['thu', 'Thu'], ['fri', 'Fri'], ['sat', 'Sat'], ['sun', 'Sun']];
const TYPES = ['College students', 'Young workers', 'Families', 'Farmers', 'Senior citizens', 'Other shopkeepers'];
const SPOKEN: [string, string][] = [['hi', 'हिन्दी'], ['mr', 'मराठी'], ['en', 'Hinglish / English']];
const HUBS = ['College', 'Bus stand', 'Mandi', 'Hospital', 'Temple', 'Market'];
const FEELS = ['Bold', 'Classic', 'Friendly'];
const STRETCH: Record<string, string> = { Bold: '80%', Classic: '100%', Friendly: '112%' };
const G: [string, string, string][] = [
    ['walk', 'More walk-ins', 'Offer posts and 3 km ads'],
    ['chat', 'More WhatsApp orders', 'Catalogue and order buttons'],
    ['back', 'Bring back old customers', 'Personal win-back offers'],
    ['new', 'Launch something new', 'Tease, launch, build'],
];
const SRCS: [string, string][] = [
    ['Phone contacts', 'Tagged as customers'],
    ['Billing / khata app', 'Knows last visits'],
    ['Excel or CSV', 'Name, phone, last visit'],
];
const DEFAULT_COLORS = ['#1D3A8A', '#FFD23F', '#FFFFFF'];
const CAT_WORD: Record<string, string> = { mobile: 'mobile ghar', kirana: 'kirana store', sweets: 'mithai ghar', salon: 'salon', clinic: 'clinic', coaching: 'classes' };
const MAX_ITEMS = 8;
const YEAR = new Date().getFullYear();

// Half-hour options from 5:00 am to 11:30 pm.
const HOURS = Array.from({ length: 38 }, (_, i) => {
    const m = 300 + i * 30;
    const v = `${String(Math.floor(m / 60)).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`;
    return [v, label12(v)] as [string, string];
});
function label12(v: string) {
    const [h, m] = v.split(':').map(Number) as [number, number];
    return `${((h + 11) % 12) + 1}:${String(m).padStart(2, '0')} ${h < 12 ? 'am' : 'pm'}`;
}

const [{ data: shopRes, pending, error, refresh }, { data: packages }] = await Promise.all([
    useApiData<{ data: any }>('setup-shop', 'shop'),
    useApiData<Pkg[]>('packages', 'packages'),
]);
const shop = computed(() => shopRes.value?.data ?? auth.shop);
const complete = computed(() => !!shop.value?.setup_complete);

// ---- form state, filled from the shop -------------------------------------------------------
const f = reactive({
    name: '',
    name_local: '',
    category: 'kirana',
    years: 0,
    city: '',
    area: '',
    address: '',
    landmark: '',
    pincode: '',
    delivery_km: 0,
    whatsapp: '',
    same: true,
    phone: '',
    opens_at: '10:00',
    closes_at: '21:00',
    weekly_off: '' as string,
    items: [] as Item[],
    types: [] as string[],
    hubs: [] as string[],
    languages: [] as string[],
    colors: [...DEFAULT_COLORS],
    feel: 'Bold',
    tagline: '',
    goals: [] as string[],
    package_id: 'dukaan',
});
const newItem = reactive({ name: '', price: '' });
let savedItems = '[]';

const step = ref(0);
const saving = ref(false);
const errs = ref<Record<string, string>>({});
const filled = ref(false);

function priceText(p: any) {
    if (p.offer) return p.offer;
    if (p.price_paise != null) return inr(p.price_paise);
    return '';
}
const itemsKey = (items: Item[]) => JSON.stringify(items.map((i) => [i.name.trim(), i.price.trim()]));

function fill(s: any) {
    if (!s) return;
    f.name = s.name ?? '';
    f.name_local = s.name_local ?? '';
    f.category = s.category ?? 'kirana';
    f.years = s.since_year ? Math.max(0, YEAR - s.since_year) : 0;
    f.city = s.city ?? '';
    f.area = s.area ?? '';
    f.address = s.address ?? '';
    f.landmark = s.landmark ?? '';
    f.pincode = s.pincode ?? '';
    // Nearest radius option to what is stored.
    const km = s.delivery_km ?? 0;
    f.delivery_km = RAD.reduce((best, r) => (Math.abs(r[1] - km) < Math.abs(best - km) ? r[1] : best), 0);
    f.whatsapp = s.whatsapp ?? auth.user?.phone ?? '';
    f.same = !s.phone || s.phone === s.whatsapp;
    f.phone = s.phone && s.phone !== s.whatsapp ? s.phone : '';
    f.opens_at = s.opens_at ?? '10:00';
    f.closes_at = s.closes_at ?? '21:00';
    f.weekly_off = s.weekly_off ?? '';
    f.items = (s.products ?? []).map((p: any) => ({ name: p.name, price: priceText(p), price_paise: p.price_paise ?? null }));
    savedItems = itemsKey(f.items);
    f.types = [...(s.audience?.types ?? [])];
    f.hubs = [...(s.audience?.hubs ?? [])];
    f.languages = [...new Set<string>(s.languages?.length ? s.languages : ['en'])];
    f.colors = s.brand?.colors?.length ? [...s.brand.colors, ...DEFAULT_COLORS].slice(0, 3) : [...DEFAULT_COLORS];
    f.feel = FEELS.includes(s.brand?.feel) ? s.brand.feel : 'Bold';
    f.tagline = s.brand?.tagline ?? '';
    f.goals = (s.goal ?? '')
        .split(/\s*,\s*/)
        .map((label: string) => G.find((g) => g[1].toLowerCase() === label.toLowerCase())?.[0])
        .filter(Boolean) as string[];
    f.package_id = s.package_id ?? 'dukaan';
    // ?step=N (1-10) opens that step, e.g. Settings → "Edit products" uses ?step=4.
    const asked = Number(useRoute().query.step);
    if (!filled.value) {
        step.value = asked >= 1 && asked <= 10 ? asked - 1 : s.setup_complete ? 0 : Math.min(9, Math.max(0, (s.setup_step ?? 1) - 1));
    }
    filled.value = true;
}
watch(() => shopRes.value?.data, (s) => s && !filled.value && fill(s), { immediate: true });

// ---- derived --------------------------------------------------------------------------------
const st = computed(() => step.value);
const cur = computed(() => ({ e: S[st.value]![1], t: S[st.value]![2], d: S[st.value]![3] }));
const stepNum = computed(() => String(st.value + 1).padStart(2, '0'));
const since = computed(() => YEAR - f.years);
const reached = computed(() => (complete.value ? 10 : Math.max(0, (shop.value?.setup_step ?? 1) - 1)));
const bars = computed(() => Array.from({ length: 10 }, (_, i) => (i === st.value ? '#F2A007' : i < reached.value ? '#0F172A' : '#E2E8F0')));
const progress = computed(() => (complete.value ? 'DONE' : 'STEP ' + (st.value + 1) + ' / 10'));
const steps = computed(() =>
    S.map(([n], i) => {
        const isDone = i < reached.value && i !== st.value;
        return { n, opt: i === 8, mark: isDone ? '✓' : String(i + 1).padStart(2, '0'), cls: 'stp' + (isDone ? ' done' : '') + (i === st.value ? ' on' : '') };
    }),
);
const pkgs = computed(() => packages.value ?? []);
const plan = computed(() => pkgs.value.find((p) => p.id === f.package_id) ?? null);
const currentPlan = computed(() => pkgs.value.find((p) => p.id === (auth.account?.package?.id ?? shop.value?.package_id)) ?? null);
const planPer = (p: Pkg) => (p.price_paise === 0 ? '' : '/mo');
const nextLabel = computed(() => {
    if (saving.value) return 'Saving…';
    if (st.value !== 9) return 'Continue';
    if (complete.value) return 'Open dashboard';
    return !plan.value || plan.value.price_paise === 0 ? 'Start free' : `Start ${plan.value.name}`;
});
const catLabel = computed(() => CATS.find((c) => c[0] === f.category)?.[1] ?? '');
const rad = computed(() => RAD.find((r) => r[1] === f.delivery_km) ?? RAD[0]!);
const photoSlots = computed(() =>
    [
        ['front', 'Shop front'],
        ['inside', 'Inside'],
        ['owner', 'You at the counter'],
        ['product', 'Best seller'],
        ['product-2', 'Second product'],
    ].map(([k, n]) => {
        const slot = `${f.category}-${k}`;
        return { slot, n, d: (PHOTO_SLOTS[slot] ?? describeSlot(slot)).description };
    }),
);
const taglines = computed(() => {
    const name = f.name.trim() || 'Hamari dukaan';
    const place = f.area.trim() || f.city.trim();
    const list: [string, string][] = [[`${name} - bharose ka naam`, 'Name + promise']];
    if (place) list.push([`${place} ka apna ${CAT_WORD[f.category] ?? 'dukaan'}`, 'Place pride']);
    if (f.years >= 2) list.push([`${f.years} saal se aapki seva mein`, 'Years promise']);
    else list.push([`${name} - naya, par apna`, 'New shop promise']);
    return list;
});
const tagline = computed(() => f.tagline || taglines.value[0]![0]);
const mainLight = computed(() => isLight(f.colors[0] ?? '#1D3A8A'));
const previewStyle = computed(() => ({ fontSize: '10.5px', width: '100%', maxWidth: '300px', '--shop': f.colors[0], '--shop-2': f.colors[1], color: mainLight.value ? '#0F172A' : '#fff' }));
const facts = computed(() => [
    { k: 'kind', v: catLabel.value },
    { k: 'where', v: [f.area, f.city].filter((x) => x.trim()).join(', ') || '—' },
    { k: 'delivery', v: f.delivery_km ? `${f.delivery_km} km` : 'no delivery' },
    { k: 'hours', v: `${label12(f.opens_at)} – ${label12(f.closes_at)}` },
    { k: 'weekly off', v: WEEK.find((w) => w[0] === f.weekly_off)?.[1] ?? 'open all week' },
    { k: 'products', v: f.items.length ? `${f.items.length} listed` : 'none yet' },
    { k: 'posts in', v: f.languages.map((l) => LANG_LABEL[l] ?? l).join(' + ') || '—' },
    { k: 'goal', v: G.filter((g) => f.goals.includes(g[0])).map((g) => g[1]).join(', ') || '—' },
    { k: 'plan', v: plan.value ? plan.value.name + ' · ' + inr(plan.value.price_paise) + planPer(plan.value) : '—' },
]);

function isLight(hex: string) {
    const m = /^#?([0-9a-f]{6})$/i.exec(hex.trim());
    if (!m) return false;
    const n = parseInt(m[1]!, 16);
    const [r, g, b] = [(n >> 16) & 255, (n >> 8) & 255, n & 255];
    return 0.299 * r + 0.587 * g + 0.114 * b > 170;
}

// ---- actions --------------------------------------------------------------------------------
const tog = (list: string[], v: string) => {
    const i = list.indexOf(v);
    if (i >= 0) list.splice(i, 1);
    else list.push(v);
};
const toggleGoal = (id: string) => {
    f.goals = f.goals.includes(id) ? f.goals.filter((x) => x !== id) : f.goals.concat(id).slice(-2);
};
function addItem() {
    const name = newItem.name.trim();
    if (!name) {
        errs.value = { ...errs.value, newItem: 'Type what you sell, e.g. the product or service name.' };
        return;
    }
    if (f.items.length >= MAX_ITEMS) return;
    f.items.push({ name, price: newItem.price.trim(), price_paise: null });
    newItem.name = '';
    newItem.price = '';
    delete errs.value.newItem;
}
function swapColours() {
    f.colors = [f.colors[1]!, f.colors[0]!, f.colors[2]!];
}

function payloadFor(i: number): Record<string, any> {
    switch (i) {
        case 0:
            return { name: f.name.trim(), name_local: f.name_local.trim() || null, category: f.category, since_year: since.value };
        case 1:
            return { city: f.city.trim() || null, area: f.area.trim() || null, address: f.address.trim() || null, landmark: f.landmark.trim() || null, pincode: f.pincode.trim() || null, delivery_km: f.delivery_km };
        case 2:
            return { whatsapp: f.whatsapp.trim() || null, phone: (f.same ? f.whatsapp : f.phone).trim() || null, opens_at: f.opens_at, closes_at: f.closes_at, weekly_off: f.weekly_off || null };
        case 3:
            // Products are replaced as a whole, so only send them when the list changed.
            return itemsKey(f.items) === savedItems ? {} : { products: f.items.map((it) => ({ name: it.name.trim(), price: it.price.trim() || null, ...(it.price_paise != null && !it.price ? { price_paise: it.price_paise } : {}) })) };
        case 4:
            return { audience: { types: f.types, hubs: f.hubs }, languages: f.languages };
        case 5:
            return { brand: { colors: f.colors, tagline: tagline.value, feel: f.feel } };
        case 7:
            return { goal: G.filter((g) => f.goals.includes(g[0])).map((g) => g[1]).join(', ') || null };
        case 9:
            return complete.value ? {} : { package_id: f.package_id };
        default:
            return {};
    }
}

function check(i: number): Record<string, string> {
    const e: Record<string, string> = {};
    if (i === 0 && f.name.trim().length < 2) e.name = 'Enter the name on your signboard.';
    if (i === 1 && f.pincode.trim() && !/^\d{6}$/.test(f.pincode.trim())) e.pincode = 'Pincode has 6 digits.';
    if (i === 2 && f.whatsapp.trim() && !/^\+?[\d\s-]{10,15}$/.test(f.whatsapp.trim())) e.whatsapp = 'Enter a 10-digit mobile number.';
    if (i === 2 && !f.same && f.phone.trim() && !/^\+?[\d\s-]{6,15}$/.test(f.phone.trim())) e.phone = 'Enter a valid phone number.';
    if (i === 4 && !f.languages.length) e.languages = 'Pick at least one language.';
    if (i === 9 && !complete.value && !f.package_id) e.package_id = 'Pick a plan.';
    return e;
}

/** Saves step i. Returns true when saved. */
async function save(i: number): Promise<boolean> {
    const e = check(i);
    errs.value = e;
    if (Object.keys(e).length) {
        toast.error(Object.values(e)[0]!);
        return false;
    }
    saving.value = true;
    try {
        const res = await api<{ shop: any }>(`shop/setup/${i + 1}`, { method: 'POST', body: payloadFor(i) });
        shopRes.value = { data: res.shop };
        if (auth.account) auth.set({ ...auth.account, shop: { ...auth.account.shop, ...res.shop } });
        if (i === 3) {
            f.items = (res.shop.products ?? []).map((p: any) => ({ name: p.name, price: priceText(p), price_paise: p.price_paise ?? null }));
            savedItems = itemsKey(f.items);
        }
        return true;
    } catch (err) {
        const ae = toApiError(err);
        const fe: Record<string, string> = {};
        for (const [k, v] of Object.entries(ae.errors)) fe[k.split('.')[0]!] = v[0]!;
        errs.value = fe;
        toast.error(Object.values(fe)[0] ?? ae.message);
        return false;
    } finally {
        saving.value = false;
    }
}

async function finish() {
    if (complete.value) return navigateTo('/app');
    const p = plan.value;
    if (p && p.price_paise > 0) {
        const ok = await confirm({
            title: `Start the ${p.name} plan?`,
            body: `${num(p.credits)} credits are added today and reset every month. You can change plans any time in Settings → Billing.`,
            cost: `${inr(p.price_paise)} / month`,
            confirm: `Start ${p.name}`,
        });
        if (!ok) return;
    }
    if (!(await save(9))) return;
    saving.value = true;
    try {
        const account = await api<any>('shop/setup/complete', { method: 'POST', body: { package_id: f.package_id } });
        auth.set(account);
        toast.ok('Setup done. Your first posts are ready on Home.');
        await navigateTo('/app');
    } catch (err) {
        toast.error(toApiError(err).message);
    } finally {
        saving.value = false;
    }
}

async function next() {
    if (saving.value) return;
    if (st.value === 9) return finish();
    if (await save(st.value)) {
        step.value = st.value + 1;
        scrollTop();
    }
}
async function skip() {
    if (saving.value) return;
    if (await save(st.value)) step.value = st.value + 1;
}
const prev = () => {
    errs.value = {};
    step.value = Math.max(0, st.value - 1);
    scrollTop();
};
const goStep = (i: number) => {
    errs.value = {};
    step.value = i;
    scrollTop();
};
function scrollTop() {
    if (!import.meta.client) return;
    window.scrollTo({ top: 0, behavior: 'smooth' });
    // On phones the step list is a horizontal strip: keep the current step in view.
    nextTick(() => document.querySelector('.setup-steps .stp.on')?.scrollIntoView({ block: 'nearest', inline: 'center' }));
}
async function finishLater() {
    if (complete.value) return navigateTo('/app');
    if (await save(st.value)) {
        toast.ok('Saved. Pick up where you left off any time.');
        await navigateTo('/');
    }
}
</script>

<template>
<div class="app" style="flex-direction: column; flex-wrap: nowrap">
<header class="bar" style="padding: 10px 28px">
<NuxtLink class="brand" style="padding: 0" to="/" aria-label="Dikho home"><Logo /></NuxtLink>
<span class="crumb" style="margin-left: 8px">/ <b>Shop setup</b></span>
<span class="sp"></span>
<div class="setup-bars" style="display: grid; grid-template-columns: repeat(10, 22px); gap: 3px" aria-hidden="true"><span v-for="(b, i) in bars" :key="i" :style="{ height: '4px', borderRadius: '2px', background: b, transition: 'background .4s' }"></span></div>
<span class="mono muted">{{ progress }}</span>
<button class="btn bg sm" type="button" :disabled="saving || pending" @click="finishLater">{{ complete ? 'Back to dashboard' : 'Finish later' }}</button>
</header>
<div class="setup-body" style="display: flex; flex-wrap: wrap; flex: 1">
<aside class="setup-steps" style="flex: 1 1 230px; max-width: 280px; padding: 22px 14px; border-right: 1px solid #E2E8F0; background: #F8FAFC; display: flex; flex-direction: column; gap: 2px">
<span class="lm" style="padding: 0 10px 10px">10 steps · about 3 min</span>
<button v-for="(s, i) in steps" :key="i" type="button" :class="s.cls" :disabled="saving" @click="goStep(i)"><span class="sq">{{ s.mark }}</span><span>{{ s.n }}</span><span v-if="s.opt" class="mono" style="font-size: 10px; color: #94A3B8">opt</span></button>
</aside>
<main class="setup-main" style="flex: 3 1 480px; min-width: 0; padding: 32px 40px; display: flex; flex-direction: column; gap: 24px">
<div style="display: flex; flex-direction: column; gap: 8px"><span class="eyb"><b>{{ stepNum }}</b>·<span>{{ cur.e }}</span></span><h1 class="h-lg">{{ cur.t }}</h1><p class="ts muted">{{ cur.d }}</p></div>

<LoadState :pending="pending && !filled" :error="!filled ? error : null" :rows="5" @retry="refresh">

<div v-if="st === 0" class="rise" style="display: flex; flex-direction: column; gap: 18px">
<div class="g2"><div class="fld"><label class="lab" for="n1">Name on the signboard</label><input id="n1" v-model="f.name" class="in" :class="{ bad: errs.name }" maxlength="80" autocomplete="organization"><span v-if="errs.name" class="err">{{ errs.name }}</span></div><div class="fld"><label class="lab" for="n2">In your script</label><input id="n2" v-model="f.name_local" class="in" lang="hi" maxlength="80" placeholder="दुकान का नाम"><span v-if="errs.name_local" class="err">{{ errs.name_local }}</span></div></div>
<div class="fld"><span class="lab">Kind of shop</span><div class="g3"><button v-for="c in CATS" :key="c[0]" type="button" :class="'opt' + (f.category === c[0] ? ' on' : '')" @click="f.category = c[0]"><span class="mt sm lt"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path :d="c[2]"></path></svg></span><span class="ts" style="font-weight: 500">{{ c[1] }}</span></button></div><span v-if="errs.category" class="err">{{ errs.category }}</span></div>
<div class="panel" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px"><span class="grow"><span class="h-sm" style="display: block">Years running</span><span class="tx muted">{{ f.years ? `Becomes a “since ${since}” trust badge` : 'New this year? Your posts say “newly opened”.' }}</span></span><button type="button" class="btn bs ib sm" aria-label="Fewer years" @click="f.years = Math.max(0, f.years - 1)">−</button><span class="num" style="font-size: 22px; width: 32px; text-align: center">{{ f.years }}</span><button type="button" class="btn bs ib sm" aria-label="More years" @click="f.years = Math.min(YEAR - 1900, f.years + 1)">+</button></div>
<span v-if="errs.since_year" class="err">{{ errs.since_year }}</span>
</div>

<div v-if="st === 1" class="rise" style="display: flex; flex-direction: column; gap: 18px">
<div class="panel" style="overflow: hidden"><svg viewBox="0 0 640 220" width="100%" style="display: block; background: #F1F5F9" role="img" :aria-label="'Map, ' + rad[0] + ' around the shop'"><path d="M-10 140 C 160 120, 330 160, 660 110" stroke="#fff" stroke-width="18" fill="none"></path><path d="M250 -10 L 300 240" stroke="#fff" stroke-width="12" fill="none"></path><path d="M470 -10 C 450 80, 500 160, 440 240" stroke="#fff" stroke-width="8" fill="none"></path><path d="M-10 46 L 660 70" stroke="#fff" stroke-width="6" fill="none"></path><circle cx="300" cy="132" :r="rad[2]" fill="rgba(15,23,42,.06)" stroke="#0F172A" stroke-dasharray="4 5" style="transition: r .5s cubic-bezier(.2,.7,.2,1)"></circle><circle cx="300" cy="132" r="7" fill="#F2A007" stroke="#0F172A" stroke-width="2.5"></circle><text v-if="f.area" x="16" y="128" font-family="Geist Mono" font-size="11" fill="#64748B">{{ f.area.toUpperCase().slice(0, 22) }}</text><text v-if="f.landmark" x="312" y="208" font-family="Geist Mono" font-size="11" fill="#64748B">{{ f.landmark.toUpperCase().slice(0, 30) }}</text></svg>
<div class="row"><span class="grow"><span class="h-sm" style="display: block">{{ [f.area, f.city].filter((x) => x.trim()).join(', ') || 'Add your area and town below' }}</span><span class="tx muted">{{ f.pincode ? 'PIN ' + f.pincode : 'Posts and ads are aimed at people around here' }}</span></span></div></div>
<div class="g2">
<div class="fld"><label class="lab" for="ar">Area or road</label><input id="ar" v-model="f.area" class="in" :class="{ bad: errs.area }" maxlength="80" placeholder="Main road, market or locality"><span v-if="errs.area" class="err">{{ errs.area }}</span></div>
<div class="fld"><label class="lab" for="ct">Town or city</label><input id="ct" v-model="f.city" class="in" :class="{ bad: errs.city }" maxlength="60" autocomplete="address-level2"><span v-if="errs.city" class="err">{{ errs.city }}</span></div>
<div class="fld"><label class="lab" for="lm">Landmark customers know</label><input id="lm" v-model="f.landmark" class="in" :class="{ bad: errs.landmark }" maxlength="120" placeholder="Near bus stand, opposite temple…"><span v-if="errs.landmark" class="err">{{ errs.landmark }}</span></div>
<div class="fld"><label class="lab" for="pc">Pincode</label><input id="pc" v-model="f.pincode" class="in" :class="{ bad: errs.pincode }" inputmode="numeric" maxlength="6" autocomplete="postal-code"><span v-if="errs.pincode" class="err">{{ errs.pincode }}</span></div>
</div>
<div class="fld"><label class="lab" for="ad">Full address <span class="muted" style="font-weight: 400">(optional)</span></label><input id="ad" v-model="f.address" class="in" :class="{ bad: errs.address }" maxlength="190" autocomplete="street-address"><span v-if="errs.address" class="err">{{ errs.address }}</span></div>
<div class="fld"><span class="lab">Delivery</span><div class="seg full"><button v-for="x in RAD" :key="x[0]" type="button" :class="f.delivery_km === x[1] ? 'on' : ''" @click="f.delivery_km = x[1]">{{ x[0] }}</button></div><span v-if="errs.delivery_km" class="err">{{ errs.delivery_km }}</span></div>
</div>

<div v-if="st === 2" class="rise" style="display: flex; flex-direction: column; gap: 18px">
<div class="panel"><div class="row" style="cursor: default"><span class="mt">WA</span><span class="grow" style="display: flex; flex-direction: column; gap: 6px"><label class="h-sm" for="wa">WhatsApp</label><input id="wa" v-model="f.whatsapp" class="in" :class="{ bad: errs.whatsapp }" type="tel" inputmode="numeric" maxlength="20" placeholder="10-digit mobile number"><span v-if="errs.whatsapp" class="err">{{ errs.whatsapp }}</span></span></div><button type="button" class="row" @click="f.same = !f.same"><span class="mt lt">CL</span><span class="grow"><span class="h-sm" style="display: block">Calls on the same number</span><span class="tx muted">Turn off to add a landline</span></span><span :class="'sw' + (f.same ? ' on' : '')"></span></button>
<div v-if="!f.same" class="row" style="cursor: default"><span class="mt lt">PH</span><span class="grow" style="display: flex; flex-direction: column; gap: 6px"><label class="h-sm" for="ph">Number for calls</label><input id="ph" v-model="f.phone" class="in" :class="{ bad: errs.phone }" type="tel" maxlength="20"><span v-if="errs.phone" class="err">{{ errs.phone }}</span></span></div></div>
<div class="g2"><div class="fld"><label class="lab" for="op">Opens</label><select id="op" v-model="f.opens_at" class="in"><option v-for="h in HOURS" :key="h[0]" :value="h[0]">{{ h[1] }}</option></select><span v-if="errs.opens_at" class="err">{{ errs.opens_at }}</span></div><div class="fld"><label class="lab" for="cl">Closes</label><select id="cl" v-model="f.closes_at" class="in"><option v-for="h in HOURS" :key="h[0]" :value="h[0]">{{ h[1] }}</option></select><span v-if="errs.closes_at" class="err">{{ errs.closes_at }}</span></div></div>
<div class="fld"><span class="lab">Weekly off</span><div class="chips"><button v-for="d in WEEK" :key="d[0]" type="button" :class="'chip' + (f.weekly_off === d[0] ? ' on' : '')" @click="f.weekly_off = f.weekly_off === d[0] ? '' : d[0]">{{ d[1] }}</button></div><span class="hint">Nothing is posted on your off day unless a festival falls on it.</span></div>
</div>

<div v-if="st === 3" class="rise" style="display: flex; flex-direction: column; gap: 18px">
<div class="panel">
<div v-if="!f.items.length" class="row" style="cursor: default"><span class="tx muted">No products yet. Add your best sellers below - name and usual price.</span></div>
<div v-for="(it, i) in f.items" :key="it.name + i" class="row" style="cursor: default"><span style="display: block; width: 40px; height: 40px; border-radius: 8px; overflow: hidden; flex-shrink: 0"><Photo :name="`${f.category}-product-${i + 1}`" :label="false" /></span><span class="grow" style="min-width: 0"><span class="h-sm" style="display: block; overflow-wrap: anywhere">{{ it.name }}</span><span class="mono muted">{{ it.price || 'no price yet' }}</span></span><span v-if="i === 0" class="tag">Hero</span><button type="button" class="btn bg ib sm" :aria-label="'Remove ' + it.name" @click="f.items.splice(i, 1)"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div>
<form v-if="f.items.length < MAX_ITEMS" class="row add-row" style="cursor: default" @submit.prevent="addItem"><input v-model="newItem.name" class="in" :class="{ bad: errs.newItem }" maxlength="80" placeholder="Product or service" aria-label="Product or service"><input v-model="newItem.price" class="in" maxlength="40" placeholder="Price, e.g. ₹800 – 2,000" aria-label="Price"><button type="submit" class="btn ba sm"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>Add</button></form>
<div class="row" style="cursor: default; border-bottom: 0"><span v-if="errs.newItem" class="err">{{ errs.newItem }}</span><span v-else-if="errs.products" class="err">{{ errs.products }}</span><span v-else class="tx muted">The first one is your hero product · {{ f.items.length }} of {{ MAX_ITEMS }}</span></div>
</div>
</div>

<div v-if="st === 4" class="rise" style="display: flex; flex-direction: column; gap: 20px">
<div class="fld"><span class="lab">Customers</span><div class="chips"><button v-for="x in TYPES" :key="x" type="button" :class="'chip' + (f.types.includes(x) ? ' on' : '')" @click="tog(f.types, x)">{{ x }}</button></div></div>
<div class="fld"><span class="lab">Languages your posts should use</span><div class="chips"><button v-for="x in SPOKEN" :key="x[0]" type="button" :class="'chip' + (f.languages.includes(x[0]) ? ' on' : '')" @click="tog(f.languages, x[0])">{{ x[1] }}</button></div><span v-if="errs.languages" class="err">{{ errs.languages }}</span></div>
<div class="fld"><span class="lab">Busy places nearby</span><div class="chips"><button v-for="x in HUBS" :key="x" type="button" :class="'chip' + (f.hubs.includes(x) ? ' on' : '')" @click="tog(f.hubs, x)">{{ x }}</button></div></div>
</div>

<div v-if="st === 5" class="rise" style="display: flex; flex-direction: column; gap: 18px">
<div style="height: 150px; border-radius: 12px; overflow: hidden"><Photo :name="`${f.category}-front`" /></div>
<div class="fld"><div style="display: flex; align-items: center"><span class="lab grow">Colours from your signboard</span><button type="button" class="lnk ts" @click="swapColours">Swap main colour</button></div>
<div class="g3"><label v-for="(c, i) in f.colors" :key="i" class="panel" style="padding: 8px; display: flex; gap: 10px; align-items: center; cursor: pointer; min-width: 0"><span :style="{ position: 'relative', width: '36px', height: '36px', flexShrink: 0, borderRadius: '8px', background: c, boxShadow: isLight(c) ? 'inset 0 0 0 1px #E2E8F0' : 'none' }"><input type="color" :value="c" :aria-label="`Colour ${i + 1}`" style="position: absolute; inset: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer" @input="f.colors[i] = ($event.target as HTMLInputElement).value.toUpperCase()"></span><span class="mono" style="overflow: hidden; text-overflow: ellipsis">{{ c }}</span></label></div><span class="hint">Tap a colour to change it.</span></div>
<div class="fld"><span class="lab">Lettering</span><div class="seg"><button v-for="x in FEELS" :key="x" type="button" :class="f.feel === x ? 'on' : ''" @click="f.feel = x">{{ x }}</button></div></div>
<div class="fld"><span class="lab">Tagline</span>
<div class="panel"><button v-for="x in taglines" :key="x[0]" type="button" class="row" @click="f.tagline = x[0]"><span :class="'rd' + (tagline === x[0] ? ' on' : '')"></span><span class="grow"><span class="h-sm" style="display: block">{{ x[0] }}</span><span class="tx muted">{{ x[1] }}</span></span></button>
<div class="row" style="cursor: default; border-bottom: 0"><span :class="'rd' + (!taglines.some((x) => x[0] === tagline) ? ' on' : '')"></span><input v-model="f.tagline" class="in grow" maxlength="80" placeholder="Or write your own" aria-label="Your own tagline"></div></div></div>
</div>

<div v-if="st === 6" class="rise" style="display: flex; flex-direction: column; gap: 14px">
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(150px, 100%), 1fr)); gap: 10px">
<div v-for="p in photoSlots" :key="p.slot" class="slot"><span style="height: 110px; width: 100%; display: block"><Photo :name="p.slot" :label="false" :rounded="false" /></span><span style="padding: 10px 12px; display: flex; flex-direction: column; gap: 3px; width: 100%"><span class="ts" style="font-weight: 500">{{ p.n }}</span><span class="tx muted">{{ p.d }}</span></span></div>
</div>
<div class="note"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h3l2-2.5h6L17 7h3v12H4zM12 16.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"></path></svg><span>Photo upload is coming soon. Until then your posts use these slots, and you can add the real photos later from Settings - nothing to do here now.</span></div>
</div>

<div v-if="st === 7" class="rise g2">
<button v-for="g in G" :key="g[0]" type="button" :class="'opt' + (f.goals.includes(g[0]) ? ' on' : '')" style="align-items: flex-start; padding: 16px" @click="toggleGoal(g[0])"><span class="ck" style="margin-top: 2px"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg></span><span style="display: flex; flex-direction: column; gap: 4px"><span class="h-sm">{{ g[1] }}</span><span class="tx muted">{{ g[2] }}</span></span></button>
<span v-if="errs.goal" class="err">{{ errs.goal }}</span>
</div>

<div v-if="st === 8" class="rise" style="display: flex; flex-direction: column; gap: 14px">
<div class="g3"><div v-for="x in SRCS" :key="x[0]" class="opt" style="flex-direction: column; align-items: flex-start; gap: 6px; cursor: default"><span class="h-sm">{{ x[0] }}</span><span class="tx muted">{{ x[1] }}</span><span class="tag">Coming soon</span></div></div>
<div class="panel pb" style="display: flex; flex-direction: column; gap: 6px"><span class="h-sm">Import is not ready yet</span><span class="tx muted">You can skip this step. After setup, add regular customers one by one from Customers, with their WhatsApp consent - importing from contacts, khata apps and Excel comes later.</span></div>
<div class="note"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7.5 3v5.5c0 4.7-3.2 8.3-7.5 9.5-4.3-1.2-7.5-4.8-7.5-9.5V6z"></path></svg><span>Only people who said yes get offers. Your shop owns the numbers; Dikho only processes them.</span></div>
</div>

<div v-if="st === 9" class="rise" style="display: flex; flex-direction: column; gap: 14px">
<template v-if="complete">
<div class="panel pb" style="display: flex; flex-direction: column; gap: 6px"><span class="h-sm">You are on {{ currentPlan?.name ?? auth.account?.package?.name ?? 'your plan' }}<template v-if="currentPlan"> · {{ inr(currentPlan.price_paise) }}{{ planPer(currentPlan) }}</template></span><span class="tx muted">Setup is already done. To change plans, top up credits or see bills, use Settings → Billing.</span><NuxtLink class="lnk ts" to="/app/settings?tab=billing" style="align-self: flex-start">Open billing</NuxtLink></div>
</template>
<template v-else>
<span v-if="!pkgs.length" class="tx muted">Loading plans…</span>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(150px, 100%), 1fr)); gap: 8px"><button v-for="p in pkgs" :key="p.id" type="button" :class="'opt' + (f.package_id === p.id ? ' on' : '')" style="flex-direction: column; align-items: flex-start; gap: 8px; min-height: 150px" @click="f.package_id = p.id"><span style="display: flex; width: 100%; justify-content: space-between"><span class="h-sm">{{ p.name }}</span><span :class="'rd' + (f.package_id === p.id ? ' on' : '')"></span></span><span style="font: 600 22px/1 var(--f); letter-spacing: -0.03em">{{ inr(p.price_paise) }}<span class="tx muted" style="font-weight: 400">{{ planPer(p) }}</span></span><span class="tag cr">{{ num(p.credits) }} credits</span><span class="tx muted">{{ p.blurb }}</span></button></div>
<span v-if="errs.package_id" class="err">{{ errs.package_id }}</span>
<p class="tx muted">Prices include GST. Ad spend and WhatsApp fees come from a separate prepaid wallet at cost.</p>
</template>
</div>

</LoadState>

<div style="display: flex; gap: 10px; padding-top: 8px; border-top: 1px solid #E2E8F0"><button type="button" class="btn bs" :disabled="saving || st === 0" @click="prev">Back</button><span class="sp"></span><button v-if="st === 8 && !complete" type="button" class="btn bg" :disabled="saving || !filled" @click="skip">Skip</button><button type="button" class="btn bp" :disabled="saving || !filled" :aria-busy="saving" @click="next">{{ nextLabel }}<svg v-if="!saving" class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></button></div>
</main>
<aside class="setup-preview" style="flex: 1.3 1 320px; min-width: 0; padding: 32px 28px; border-left: 1px solid #E2E8F0; background: #F8FAFC; display: flex; flex-direction: column; gap: 18px">
<span class="lm">Live preview</span>
<div class="pst r45" :style="previewStyle"><div class="pi" style="padding: 0; overflow: hidden; align-items: stretch"><Photo :name="`${f.category}-front`" :label="false" :rounded="false" /></div><div class="pad"><span class="hk" :style="{ fontStretch: STRETCH[f.feel] }">{{ tagline }}</span><span class="sub">{{ [f.landmark || f.area, f.city].filter(Boolean).join(' · ') }}</span></div><div class="ft"><b>{{ f.name || 'Your shop' }}</b><span>{{ f.years ? 'Since ' + since : 'New' }}</span></div></div>
<div class="receipt panel" style="padding: 6px 14px">
<div v-for="x in facts" :key="x.k" class="ln" style="border-bottom: 1px dashed #E2E8F0; gap: 12px"><span class="muted">{{ x.k }}</span><span style="text-align: right; min-width: 0; overflow-wrap: anywhere">{{ x.v }}</span></div>
</div>
</aside>
</div>
</div>
</template>

<style scoped>
.stp{display:grid;grid-template-columns:28px minmax(0,1fr) 16px;gap:10px;align-items:center;width:100%;padding:9px 10px;border:0;border-radius:8px;background:none;cursor:pointer;text-align:left;font-size:13.5px;color:#64748B;transition:background .15s,color .15s}.stp:hover{background:#F1F5F9;color:#0F172A}.stp.on{background:#fff;color:#0F172A;box-shadow:0 0 0 1px #E2E8F0}.stp.done{color:#334155}.sq{width:24px;height:24px;border-radius:7px;display:grid;place-items:center;font:500 11px var(--fm);box-shadow:inset 0 0 0 1px #CBD5E1;background:#fff}.stp.done .sq{background:#0F172A;color:#fff;box-shadow:none}.stp.on .sq{box-shadow:inset 0 0 0 1.5px #0F172A;color:#0F172A}.slot{border-radius:10px;overflow:hidden;border:1px solid #E2E8F0;background:#fff;padding:0;text-align:left;display:flex;flex-direction:column}
.g2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.g3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
.in.bad{border-color:#f87171}
.add-row{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr) auto;gap:8px}
.btn:disabled{opacity:.6;cursor:not-allowed}
@media (max-width: 760px) {
    .setup-bars{display:none !important}
    .setup-steps{flex:1 1 100% !important;max-width:none !important;flex-direction:row !important;overflow-x:auto;padding:10px 12px !important;border-right:0 !important;border-bottom:1px solid #E2E8F0;gap:4px !important}
    .setup-steps .lm{display:none}
    .setup-steps .stp{width:auto;flex:none;grid-template-columns:24px auto;white-space:nowrap}
    .setup-steps .stp>span:nth-child(3){display:none}
    .setup-main{padding:24px 16px !important}
    .setup-preview{padding:24px 16px !important;border-left:0 !important;border-top:1px solid #E2E8F0}
    .g3{grid-template-columns:repeat(2,minmax(0,1fr))}
    .add-row{grid-template-columns:minmax(0,1fr) auto}
    .add-row input:first-child{grid-column:1 / -1}
}
@media (max-width: 480px) {
    .g2{grid-template-columns:minmax(0,1fr)}
    .bar{padding:10px 16px !important}
}
</style>
