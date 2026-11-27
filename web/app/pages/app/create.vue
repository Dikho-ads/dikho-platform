<script setup lang="ts">
// Create: a short brief → format → language → POST content (spends the format's credits) →
// pick one of three versions (free) → check trust → publish now or schedule.
definePageMeta({ layout: 'app', title: 'Create' });

const auth = useAuth();
const api = useApi();
const toast = useToast();
const { confirm } = useConfirm();
const route = useRoute();

const { data: studio, pending: studioPending, error: studioError, refresh: refreshStudio } = useApiData<any>('studio', 'studio');
const { data: occData } = useApiData<any>('create-occasions', 'occasions', { lazy: true });
const { data: shopData } = useApiData<any>('create-shop', 'shop', { lazy: true });
const { data: connData } = useApiData<any>('create-connections', 'connections', { lazy: true });

const shop = computed(() => auth.shop);
const formats = computed<any[]>(() => (studio.value?.formats || []).filter((f: any) => f.credits > 0));
const languages = computed<any[]>(() => studio.value?.languages || Object.entries(LANG_LABEL).map(([code, label]) => ({ code, label })));
const occasions = computed<any[]>(() => occData.value?.data || []);
const products = computed<any[]>(() => shopData.value?.data?.products || []);

// 01 · Brief
const fmt = ref<string>(String(route.query.type || 'post'));
const format = computed(() => formats.value.find((f) => f.type === fmt.value) || formats.value[0]);
const lang = ref<string>(shop.value?.languages?.[0] || 'en');
const brief = ref('');
const occasionId = ref<string>(route.query.occasion_id ? String(route.query.occasion_id) : '');
const productId = ref<string>(route.query.product_id ? String(route.query.product_id) : '');
const offer = ref('');
const angle = ref('');
const ANGLES = [
    ['', 'Best for this brief'],
    ['offer', 'Offer'],
    ['trust', 'Trust'],
    ['local', 'Local'],
    ['newness', 'Something new'],
    ['festival', 'Festival'],
    ['tip', 'Useful tip'],
];
const ANGLE_LABEL: Record<string, string> = Object.fromEntries(ANGLES.filter(([k]) => k));
watch(productId, (id) => {
    const p = products.value.find((x) => String(x.id) === id);
    if (p?.offer && !offer.value) offer.value = p.offer;
});

const fmts = computed(() => formats.value.map((f) => ({ ...f, cls: 'opt' + (format.value?.type === f.type ? ' on' : '') })));
const cost = computed(() => format.value?.credits ?? 0);
const left = computed(() => auth.account?.credits?.left ?? 0);
const makeTxt = computed(() => `Make 3 versions · ${cost.value} cr`);

// 02 · Versions
type Stage = 'empty' | 'making' | 'ready' | 'published';
const stage = ref<Stage>('empty');
const step = ref(0);
const item = ref<any>(null);
const variants = ref<any[]>([]);
const k = ref(0);
const making = ref(false);
const picking = ref(false);

const PIPE = [
    ['Brief', 'occasion, product, offer'],
    ['Angle', 'offer, trust, local — ranked'],
    ['Hook', 'first line, your words'],
    ['Message', 'one idea, one real price'],
    ['Visual', 'your photo slot, your colours'],
    ['Proof + CTA', 'rating, area, WhatsApp'],
    ['Language', 'written in the language you picked'],
    ['Gates', 'trust check on each version'],
];
const pipe = computed(() =>
    PIPE.map(([t, d], i) => ({ t, d, mark: i < step.value ? '✓' : String(i + 1), cls: 'pm' + (i < step.value ? ' d' : i === step.value ? ' c' : ''), c: i <= step.value ? '#0F172A' : '#94A3B8' })),
);
const stageHead = computed(() => ({ empty: 'Versions', making: 'Making 3 versions…', ready: '3 versions · pick one', published: 'Done' })[stage.value]);
const ready = computed(() => stage.value === 'ready');

let iv: any = null;
onMounted(() => {
    iv = setInterval(() => {
        // A visual walk through the steps while the real request runs; it waits at the last step.
        if (stage.value === 'making' && step.value < PIPE.length - 1) step.value++;
    }, 380);
});
onBeforeUnmount(() => clearInterval(iv));

function moneyError(e: any) {
    if (e.code === 'insufficient_credits') toast.error(e.message, { label: 'Top up', run: () => navigateTo('/app/settings?tab=billing') });
    else toast.error(e.message);
}

const fieldErr = ref<Record<string, string>>({});

async function make() {
    if (!format.value) return;
    const ok = await confirm({
        title: `Make 3 versions of a ${format.value.label.toLowerCase()}?`,
        body: 'Picking a different version afterwards is free.',
        cost: `${cost.value} credits · ${left.value - cost.value} left after`,
        confirm: 'Make versions',
    });
    if (!ok) return;
    fieldErr.value = {};
    making.value = true;
    const before = stage.value;
    stage.value = 'making';
    step.value = 0;
    try {
        const body: Record<string, any> = { type: format.value.type, language: lang.value };
        if (brief.value.trim()) body.brief = brief.value.trim();
        if (offer.value.trim()) body.offer = offer.value.trim();
        if (occasionId.value) body.occasion_id = Number(occasionId.value);
        if (productId.value) body.product_id = Number(productId.value);
        if (angle.value) body.angle = angle.value;
        const res = await api<any>('content', { method: 'POST', body });
        auth.set(res.account);
        item.value = res.item;
        variants.value = res.variants || [];
        k.value = 0;
        resetForm();
        ch.value = (res.item.channels || []).filter((c: string) => CHANNELS.some((x) => x.id === c));
        step.value = PIPE.length;
        setTimeout(() => (stage.value = 'ready'), 250);
        toast.ok(`3 versions made · ${cost.value} credits used`);
    } catch (e: any) {
        stage.value = before === 'making' ? 'empty' : before;
        fieldErr.value = Object.fromEntries(Object.keys(e.errors || {}).map((f) => [f, e.field(f)]));
        moneyError(e);
    } finally {
        making.value = false;
    }
}

const vars = computed(() =>
    variants.value.map((v: any, i: number) => ({
        ...v,
        // Each card shows its own text on the made item's design.
        card: { ...(item.value || {}), ...v, ...(k.value === i && item.value ? { hook: item.value.hook, subline: item.value.subline, terms: item.value.terms } : {}) },
        score: k.value === i && item.value ? item.value.trust_score : v.trust_score,
        cls: 'vc' + (k.value === i ? ' on' : ''),
        dl: i * 0.08 + 's',
    })),
);

async function pick(i: number) {
    if (i === k.value || picking.value || !item.value) return;
    const v = variants.value[i];
    const prev = k.value;
    k.value = i;
    picking.value = true;
    try {
        const res = await api<any>(`content/${item.value.id}`, {
            method: 'PATCH',
            body: { hook: v.hook, subline: v.subline, caption: v.caption, cta: v.cta, terms: v.terms },
        });
        item.value = res.data;
        resetForm();
    } catch (e: any) {
        k.value = prev;
        toast.error(e.message);
    } finally {
        picking.value = false;
    }
}

// Ratio switch (saved on the item).
const RATIOS = computed(() => {
    const base = ['9:16', '4:5', '1:1'];
    const own = item.value?.ratio;
    return own && !base.includes(own) ? [...base, own] : base;
});
async function setRatio(r: string) {
    if (!item.value || item.value.ratio === r) return;
    const prev = item.value.ratio;
    item.value = { ...item.value, ratio: r };
    try {
        const res = await api<any>(`content/${item.value.id}`, { method: 'PATCH', body: { ratio: r } });
        item.value = res.data;
    } catch (e: any) {
        item.value = { ...item.value, ratio: prev };
        toast.error(e.message);
    }
}

// Edit the picked version's words.
const form = reactive({ hook: '', subline: '', terms: '', cta: '', caption: '' });
const formErr = ref<Record<string, string>>({});
const saving = ref(false);
function resetForm() {
    const it = item.value || {};
    form.hook = it.hook || '';
    form.subline = it.subline || '';
    form.terms = it.terms || '';
    form.cta = it.cta || '';
    form.caption = it.caption || '';
    formErr.value = {};
}
const dirty = computed(() => !!item.value && (['hook', 'subline', 'terms', 'cta', 'caption'] as const).some((f) => (form[f] || '') !== (item.value[f] || '')));
async function saveWords() {
    if (!item.value || !dirty.value) return true;
    saving.value = true;
    formErr.value = {};
    try {
        const res = await api<any>(`content/${item.value.id}`, { method: 'PATCH', body: { ...form } });
        item.value = res.data;
        resetForm();
        toast.ok(`Saved · trust ${res.data.trust_score}`);
        return true;
    } catch (e: any) {
        formErr.value = Object.fromEntries(Object.keys(e.errors || {}).map((f) => [f, e.field(f)]));
        toast.error(e.message);
        return false;
    } finally {
        saving.value = false;
    }
}

// 03 · Trust check
const score = computed(() => item.value?.trust_score ?? 0);
const off = computed(() => Math.round(170 - (170 * score.value) / 100));
const checks = computed<any[]>(() => item.value?.trust_checks || []);
const failing = computed(() => checks.value.filter((c) => !c.ok).length);
const trustHead = computed(() => {
    if (!item.value) return 'Checked on every version';
    if (!failing.value) return 'Strong — ready to go';
    return score.value >= 70 ? `Passes · ${failing.value} quick ${failing.value === 1 ? 'fix' : 'fixes'}` : 'Needs work before it goes out';
});

// 04 · Publish
const CHANNELS = [
    { id: 'whatsapp_status', m: 'WA', n: 'WhatsApp Status', s: 'free · 24 h', provider: 'whatsapp' },
    { id: 'instagram', m: 'IG', n: 'Instagram', s: 'feed + story', provider: 'instagram' },
    { id: 'facebook', m: 'FB', n: 'Facebook Page', s: 'feed', provider: 'facebook' },
    { id: 'google', m: 'GB', n: 'Google Business', s: 'offer post', provider: 'google' },
];
const ch = ref<string[]>(['whatsapp_status', 'instagram', 'facebook']);
const conn = computed<Record<string, string>>(() => Object.fromEntries((connData.value?.data || []).map((c: any) => [c.provider, c.status])));
const chans = computed(() =>
    CHANNELS.map((c) => {
        const status = conn.value[c.provider];
        return { ...c, s: status && status !== 'connected' ? `${c.s} · not linked, share from phone` : c.s, sw: 'sw' + (ch.value.includes(c.id) ? ' on' : '') };
    }),
);
function toggleCh(id: string) {
    ch.value = ch.value.includes(id) ? ch.value.filter((x) => x !== id) : ch.value.concat(id);
}

// When: now, this evening at 7:30, or a picked time.
const when = ref(0);
const evening = computed(() => {
    const d = new Date();
    if (d.getHours() > 19 || (d.getHours() === 19 && d.getMinutes() >= 15)) d.setDate(d.getDate() + 1);
    d.setHours(19, 30, 0, 0);
    return d;
});
const eveningLabel = computed(() => (relative(evening.value) === 'today' ? 'Today' : 'Tomorrow') + ' 7:30 pm');
const whenNames = computed(() => ['Now', eveningLabel.value, 'Pick']);
const toLocalInput = (d: Date) => `${isoDate(d)}T${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
const picked = ref('');
onMounted(() => {
    const d = new Date(evening.value);
    d.setDate(d.getDate() + 1);
    picked.value = toLocalInput(d);
});
const scheduleAt = computed<Date | null>(() => (when.value === 1 ? evening.value : when.value === 2 && picked.value ? new Date(picked.value) : null));
const pubTxt = computed(() => (when.value === 0 ? 'Publish now' : scheduleAt.value ? `Schedule for ${day(scheduleAt.value)}, ${time(scheduleAt.value).toLowerCase()}` : 'Pick a time'));
// The API keeps the wall-clock time of what it is sent, so send local time with its offset
// (e.g. 2026-11-27T19:30:00+05:30), not UTC.
const withOffset = (d: Date) => {
    const off = -d.getTimezoneOffset();
    const p = (n: number) => String(Math.floor(Math.abs(n))).padStart(2, '0');
    return `${isoDate(d)}T${p(d.getHours())}:${p(d.getMinutes())}:00${off >= 0 ? '+' : '-'}${p(off / 60)}:${p(off % 60)}`;
};
const publishing = ref(false);
const pubErr = ref('');

async function publish() {
    if (!item.value) return;
    pubErr.value = '';
    if (!ch.value.length) {
        pubErr.value = 'Pick at least one place to post.';
        return;
    }
    if (when.value !== 0 && !scheduleAt.value) {
        pubErr.value = 'Pick a date and time.';
        return;
    }
    publishing.value = true;
    try {
        if (dirty.value && !(await saveWords())) return;
        let res: any;
        if (when.value === 0) {
            res = await api<any>(`content/${item.value.id}/publish`, { method: 'POST', body: { channels: ch.value } });
        } else {
            await api<any>(`content/${item.value.id}`, { method: 'PATCH', body: { channels: ch.value } });
            res = await api<any>(`content/${item.value.id}/schedule`, { method: 'POST', body: { scheduled_at: withOffset(scheduleAt.value!) } });
        }
        auth.set(res.account);
        item.value = res.item;
        stage.value = 'published';
        if (res.item.status === 'posted') toast.ok(`Posted → ${channelsText.value}`, { label: 'Undo', run: undo });
        else toast.ok(`Scheduled for ${day(res.item.scheduled_at)}, ${time(res.item.scheduled_at).toLowerCase()}`);
    } catch (e: any) {
        pubErr.value = e.field?.('scheduled_at') || e.field?.('status') || '';
        moneyError(e);
    } finally {
        publishing.value = false;
    }
}

async function undo() {
    if (!item.value) return;
    try {
        const res = await api<any>(`content/${item.value.id}/unpublish`, { method: 'POST' });
        auth.set(res.account);
        item.value = res.item;
        stage.value = 'ready';
        toast.info('Undone · not posted');
    } catch (e: any) {
        toast.error(e.message);
    }
}

const channelsText = computed(() => (item.value?.channels || []).map((c: string) => CHANNEL_LABEL[c] || c).join(', '));
const doneHead = computed(() => {
    const it = item.value;
    if (!it) return '';
    if (it.status === 'posted') return 'Posted just now.';
    return `Goes out ${relative(it.scheduled_at) === 'today' ? 'today' : relative(it.scheduled_at) === 'tomorrow' ? 'tomorrow' : 'on ' + day(it.scheduled_at)} at ${time(it.scheduled_at).toLowerCase()}.`;
});

function again() {
    stage.value = 'empty';
    item.value = null;
    variants.value = [];
    k.value = 0;
    brief.value = '';
    when.value = 0;
}
</script>

<template>
    <LoadState v-if="studioError" :error="studioError" @retry="refreshStudio" />
    <div v-else class="cr-grid" style="display: flex; flex-wrap: wrap; flex: 1; align-items: stretch">
        <section class="rise cr-brief" style="flex: 1 1 300px; min-width: 0; padding: 22px; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 18px">
            <span class="eyb"><b>01</b>·<span>Brief</span></span>
            <LoadState :pending="studioPending && !studio" :rows="5">
                <div style="display: flex; flex-direction: column; gap: 4px" role="radiogroup" aria-label="Format">
                    <button v-for="f in fmts" :key="f.type" type="button" :class="f.cls" style="padding: 9px 12px" role="radio" :aria-checked="format?.type === f.type" @click="fmt = f.type">
                        <span class="rd"></span><span class="grow ts" style="font-weight: 500">{{ f.label }}</span><span class="tag cr">{{ f.credits }} cr</span>
                    </button>
                </div>
            </LoadState>
            <div class="panel" style="padding: 12px; display: flex; flex-direction: column; gap: 10px">
                <div style="display: flex; align-items: center; gap: 10px">
                    <span class="btn bs ib" aria-hidden="true" style="width: 40px; height: 40px; border-radius: 10px; cursor: default"><svg class="i" viewBox="0 0 24 24"><path d="M12 3a3 3 0 0 1 3 3v5a3 3 0 0 1-6 0V6a3 3 0 0 1 3-3zM5 11a7 7 0 0 0 14 0M12 18v3"></path></svg></span>
                    <span style="display: flex; flex-direction: column; min-width: 0">
                        <span class="ts" style="font-weight: 500">Bol ke batayein · likh ke</span>
                        <span class="tx muted">Type it for now; speaking comes later. Your words become the title.</span>
                    </span>
                </div>
                <textarea v-model="brief" class="in" maxlength="500" style="height: 70px; padding: 9px 11px; line-height: 1.45; resize: none; font-size: 13px" aria-label="What is this post about" placeholder="e.g. Shaadi season offer on gift boxes, till Sunday"></textarea>
                <span v-if="fieldErr.brief" class="tx" style="color: #b91c1c">{{ fieldErr.brief }}</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px">
                <span class="lm">Details · optional</span>
                <label class="kv"><span class="tx muted">Occasion</span>
                    <select v-model="occasionId" class="in"><option value="">None</option><option v-for="o in occasions" :key="o.id" :value="String(o.id)">{{ o.name }} · {{ o.days_away <= 0 ? 'now' : day(o.starts_on) }}</option></select>
                </label>
                <label class="kv"><span class="tx muted">Product</span>
                    <select v-model="productId" class="in"><option value="">Any</option><option v-for="p in products" :key="p.id" :value="String(p.id)">{{ p.name }}</option></select>
                </label>
                <label class="kv"><span class="tx muted">Offer</span><input v-model="offer" class="in" maxlength="120" placeholder="e.g. 250 g free on 1 kg" /></label>
                <span v-if="fieldErr.offer" class="tx" style="color: #b91c1c">{{ fieldErr.offer }}</span>
                <label class="kv"><span class="tx muted">Angle</span>
                    <select v-model="angle" class="in"><option v-for="[key, label] in ANGLES" :key="key" :value="key">{{ label }}</option></select>
                </label>
            </div>
            <div class="fld">
                <span class="lm">Language · bhasha</span>
                <div class="seg full" role="group" aria-label="Language">
                    <button v-for="l in languages" :key="l.code" type="button" :class="lang === l.code ? 'on' : ''" :lang="l.code" @click="lang = l.code">{{ l.label }}</button>
                </div>
            </div>
            <button type="button" class="btn bp lg full" :disabled="making || !format" @click="make">{{ making ? 'Making…' : makeTxt }}</button>
            <span class="tx muted" style="margin-top: -10px">{{ num(left) }} credits left</span>
        </section>

        <section class="dots cr-versions" style="flex: 2.2 1 520px; min-width: 0; padding: 22px; display: flex; flex-direction: column; gap: 16px; background-color: #f8fafc">
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px">
                <span class="eyb"><b>02</b>·<span>{{ stageHead }}</span></span><span class="sp"></span>
                <template v-if="ready && item">
                    <div class="seg" role="group" aria-label="Size"><button v-for="r in RATIOS" :key="r" type="button" :class="item.ratio === r ? 'on' : ''" @click="setRatio(r)">{{ r }}</button></div>
                    <span class="tag" :lang="item.language">{{ item.language_label || LANG_LABEL[item.language] }}</span>
                </template>
            </div>

            <div v-if="stage === 'empty'" class="fade" style="flex: 1; display: grid; place-items: center; min-height: 280px">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 16px; text-align: center">
                    <div style="display: grid; grid-template-columns: repeat(3, 44px); gap: 6px" aria-hidden="true"><span style="height: 44px; background: #0f172a; border-radius: 44px 0 0 0"></span><span style="height: 44px; background: #cbd5e1; border-radius: 50%"></span><span style="height: 44px; background: #f2a007; border-radius: 0 44px 0 0"></span></div>
                    <p class="h-md">Your 3 versions appear here</p>
                    <p class="ts muted" style="max-width: 320px">Each takes a different angle, so every post is also a small test.</p>
                </div>
            </div>

            <div v-if="stage === 'making'" class="fade mk-grid">
                <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; align-content: start"><div class="sk" style="aspect-ratio: 4/5"></div><div class="sk" style="aspect-ratio: 4/5; animation-delay: 0.2s"></div><div class="sk" style="aspect-ratio: 4/5; animation-delay: 0.4s"></div></div>
                <div class="panel" style="padding: 12px 16px">
                    <div v-for="p in pipe" :key="p.t" class="ps"><span :class="p.cls">{{ p.mark }}</span><span><span class="ts" :style="{ fontWeight: 500, display: 'block', color: p.c }">{{ p.t }}</span><span class="tx muted">{{ p.d }}</span></span></div>
                </div>
            </div>

            <div v-if="ready && item" style="display: flex; flex-direction: column; gap: 16px">
                <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; align-items: start">
                    <button v-for="(v, i) in vars" :key="v.key" type="button" :class="v.cls" :aria-pressed="k === i" :disabled="picking" :style="{ animation: 'rise .6s var(--ease) both', animationDelay: v.dl }" @click="pick(i)">
                        <PostCard tag="span" :item="v.card" terms-as="pr" style="font-size: 9.6px; width: 100%" />
                        <span style="display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 6px; flex-wrap: wrap"><span class="ts" style="font-weight: 600">{{ v.key }} · {{ ANGLE_LABEL[v.angle] || v.angle }}</span><span class="mono muted">trust {{ v.score }}</span></span>
                    </button>
                </div>
                <div v-if="vars[k]?.why" class="note" style="background: #fff"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3z"></path></svg><span><b>Why {{ vars[k].key }}:</b> {{ vars[k].why }}</span></div>

                <div class="panel">
                    <div class="ph"><span class="eyb"><b>{{ vars[k]?.key }}</b>·<span>Words</span></span><span class="sp"></span><NuxtLink class="btn bg sm" :to="`/app/studio/editor?id=${item.id}`">Open in Editor</NuxtLink></div>
                    <div class="pb words">
                        <div class="fld" style="grid-column: 1 / -1"><label class="lab" for="cr-hook">Hook · pehli line</label><input id="cr-hook" v-model="form.hook" class="in" maxlength="160" :lang="item.language" /><span v-if="formErr.hook" class="hint" style="color: #b91c1c">{{ formErr.hook }}</span></div>
                        <div class="fld"><label class="lab" for="cr-sub">Second line</label><input id="cr-sub" v-model="form.subline" class="in" maxlength="200" :lang="item.language" /></div>
                        <div class="fld"><label class="lab" for="cr-terms">Terms · deadline</label><input id="cr-terms" v-model="form.terms" class="in" maxlength="160" :lang="item.language" /></div>
                        <div class="fld" style="grid-column: 1 / -1"><label class="lab" for="cr-cta">Button</label><input id="cr-cta" v-model="form.cta" class="in" maxlength="80" :lang="item.language" /></div>
                        <div class="fld" style="grid-column: 1 / -1"><label class="lab" for="cr-cap">Caption</label><textarea id="cr-cap" v-model="form.caption" class="in" maxlength="2200" style="height: 92px; padding: 9px 11px; line-height: 1.45; resize: vertical; font-size: 13px" :lang="item.language"></textarea></div>
                        <div style="grid-column: 1 / -1; display: flex; gap: 8px; align-items: center">
                            <button type="button" class="btn bs sm" :disabled="!dirty || saving" @click="saveWords">{{ saving ? 'Saving…' : 'Save changes' }}</button>
                            <button v-if="dirty" type="button" class="btn bg sm" @click="resetForm">Discard</button>
                            <span class="tx muted">Saving re-runs the trust check.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="stage === 'published' && item" class="rise panel done">
                <div style="display: flex; flex-direction: column; gap: 12px; min-width: 0">
                    <span :class="item.status === 'posted' ? 'st ok live' : 'st ok'">{{ item.status === 'posted' ? 'Posted' : 'Scheduled' }}</span>
                    <h2 class="h-lg">{{ doneHead }}</h2>
                    <p class="ts muted">On {{ channelsText }}. Views, chats, calls and QR scans for this piece land in Results.</p>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap">
                        <NuxtLink class="btn bp" to="/app">Back to dashboard</NuxtLink>
                        <button type="button" class="btn bs" @click="again">Make another</button>
                        <button v-if="item.status === 'posted'" type="button" class="btn bg" @click="undo">Undo</button>
                        <NuxtLink v-else class="btn bg" :to="`/app/studio/editor?id=${item.id}`">Edit</NuxtLink>
                    </div>
                </div>
                <PostCard :item="item" style="font-size: 6px; width: 140px" />
            </div>
        </section>

        <section class="cr-publish" :style="{ flex: '1 1 300px', minWidth: 0, padding: '22px', borderLeft: '1px solid #E2E8F0', display: 'flex', flexDirection: 'column', gap: '18px', opacity: ready ? 1 : 0.45, transition: 'opacity .3s', pointerEvents: ready ? 'auto' : 'none' }" :aria-disabled="!ready">
            <span class="eyb"><b>03</b>·<span>Trust check</span></span>
            <div style="display: flex; gap: 14px; align-items: center">
                <svg viewBox="0 0 64 64" width="64" height="64" role="img" :aria-label="`Trust score ${score}`" style="flex-shrink: 0"><circle cx="32" cy="32" r="27" fill="none" stroke="#F1F5F9" stroke-width="6"></circle><circle cx="32" cy="32" r="27" fill="none" stroke="#0F172A" stroke-width="6" stroke-linecap="round" stroke-dasharray="170" :stroke-dashoffset="off" transform="rotate(-90 32 32)" style="transition: stroke-dashoffset 0.8s cubic-bezier(0.2, 0.7, 0.2, 1)"></circle><text x="32" y="38" text-anchor="middle" font-family="Geist" font-weight="600" font-size="17" fill="#0F172A">{{ item ? score : '—' }}</text></svg>
                <div><p class="h-sm">{{ trustHead }}</p><p class="tx muted">Scored on honest price, real deadline, your place and number. Fix warnings for more chats.</p></div>
            </div>
            <div style="display: flex; flex-direction: column">
                <div v-for="c in checks" :key="c.key" style="display: flex; flex-direction: column; gap: 2px; padding: 6px 0; border-bottom: 1px dashed #e2e8f0">
                    <span :class="c.ok ? 'st ok' : 'st warn'" style="font-size: 12.5px">{{ c.label }}</span>
                    <span v-if="!c.ok" class="tx muted">{{ c.tip }}</span>
                </div>
                <span v-if="!checks.length" class="tx muted">Make versions to see the checks.</span>
            </div>
            <span class="eyb" style="margin-top: 4px"><b>04</b>·<span>Publish</span></span>
            <div class="panel">
                <button v-for="c in chans" :key="c.id" type="button" class="row" style="padding: 9px 12px" role="switch" :aria-checked="ch.includes(c.id)" @click="toggleCh(c.id)"><span class="mt sm lt">{{ c.m }}</span><span class="grow"><span class="ts" style="font-weight: 500; display: block">{{ c.n }}</span><span class="tx muted">{{ c.s }}</span></span><span :class="c.sw"></span></button>
            </div>
            <div class="seg full"><button v-for="(n, i) in whenNames" :key="n" type="button" :class="when === i ? 'on' : ''" @click="when = i">{{ n }}</button></div>
            <input v-if="when === 2" v-model="picked" type="datetime-local" class="in" :min="toLocalInput(new Date())" aria-label="Date and time" />
            <div class="receipt">
                <div class="ln"><span class="muted">credits</span><span>{{ item ? `${item.credits} cr · paid when made` : `${cost} cr` }}</span></div>
                <div class="ln"><span class="muted">wallet</span><span>₹0</span></div>
            </div>
            <span v-if="pubErr" class="tx" style="color: #b91c1c" role="alert">{{ pubErr }}</span>
            <button type="button" class="btn bp lg full" :disabled="publishing || !item" @click="publish">{{ publishing ? 'Working…' : pubTxt }}</button>
        </section>
    </div>
</template>

<style scoped>
.kv {
    display: grid;
    grid-template-columns: 84px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
}
.kv .in {
    height: 34px;
    font-size: 13px;
    min-width: 0;
}
.vc {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    cursor: pointer;
    text-align: left;
    min-width: 0;
    transition:
        border-color 0.2s,
        box-shadow 0.2s,
        transform 0.25s var(--ease);
}
.vc:hover {
    transform: translateY(-2px);
    border-color: #cbd5e1;
}
.vc.on {
    border-color: #0f172a;
    box-shadow: inset 0 0 0 1px #0f172a;
}
.sk {
    border-radius: 10px;
    background: #e2e8f0;
    animation: breathe 1.6s ease-in-out infinite;
}
.mk-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 260px;
    gap: 20px;
    flex: 1;
}
.ps {
    display: grid;
    grid-template-columns: 22px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
    padding: 7px 0;
}
.pm {
    width: 20px;
    height: 20px;
    border-radius: 6px;
    display: grid;
    place-items: center;
    font: 500 10px var(--fm);
    box-shadow: inset 0 0 0 1px #cbd5e1;
    background: #fff;
    color: #64748b;
}
.pm.d {
    background: #0f172a;
    color: #fff;
    box-shadow: none;
}
.pm.c {
    box-shadow: inset 0 0 0 1.5px #0f172a;
    color: #0f172a;
}
.words {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.done {
    padding: 28px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 140px;
    gap: 28px;
    align-items: center;
}
@media (max-width: 599px) {
    .mk-grid {
        grid-template-columns: minmax(0, 1fr);
    }
    .words {
        grid-template-columns: minmax(0, 1fr);
    }
    .done {
        grid-template-columns: minmax(0, 1fr);
        padding: 20px;
    }
}

/* Laptops: three columns do not fit; brief on the left, versions over trust + publish on the right. */
@media (min-width: 900px) and (max-width: 1279px) {
    .cr-grid {
        display: grid !important;
        grid-template-columns: minmax(280px, 320px) minmax(0, 1fr);
        align-items: start !important;
    }
    .cr-brief {
        grid-row: 1 / span 2;
        align-self: stretch;
    }
    .cr-publish {
        border-left: 0 !important;
        border-top: 1px solid #e2e8f0;
    }
}
</style>
