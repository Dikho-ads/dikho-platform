<script setup lang="ts">
// Editor: one content item (?id=). Words save with PATCH content/{id} (the trust check is redone
// on the server); size, look, language, photo and channels save as soon as they change.
// Without an id, a picker of the next drafts and ready pieces.
import { PHOTO_SLOTS } from '~/utils/photoSlots';

definePageMeta({ layout: 'app', title: 'Editor' });

const auth = useAuth();
const api = useApi();
const toast = useToast();
const { confirm } = useConfirm();
const route = useRoute();

const id = computed(() => (route.query.id ? String(route.query.id) : ''));
const shop = computed(() => auth.shop);

// Picker (no id).
const today = isoDate();
const {
    data: list,
    pending: listPending,
    error: listError,
    refresh: refreshList,
} = useAsyncData<any>('editor-picker', () => (id.value ? Promise.resolve(null) : api('content', { query: { status: 'draft,ready', from: today, limit: 20 } })), { watch: [id] });

// The item.
const { data, pending, error, refresh } = await useAsyncData<any>(() => `content-${id.value || 'none'}`, () => (id.value ? api(`content/${id.value}`) : Promise.resolve(null)), {
    watch: [id],
});
const it = ref<any>(null);
// A missing piece reads better than the server's model message.
const loadError = computed(() => (error.value ? (error.value as any).status === 404 || (error.value as any).statusCode === 404 ? { message: 'This piece does not exist any more. Pick another one.' } : error.value : null));

const STAGE_LABEL: Record<string, string> = { awareness: 'Pehchaan · awareness', trust: 'Bharosa · trust', buying: 'Kharidari · buying', return: 'Wapsi · return' };
const SOURCE_LABEL: Record<string, string> = { auto: 'Made by Auto mode', plan: 'From your plan', manual: 'Made by you' };
const STATUS_CLS: Record<string, string> = { draft: 'st', ready: 'st acc', approved: 'st ok', scheduled: 'st ok', posted: 'st ok live', skipped: 'st warn' };
const lc = computed(() => it.value?.language || 'en');

const adv = ref(true);
const levels = ['Simple', 'Advanced'];

// Words.
const FIELDS = ['title', 'hook', 'subline', 'terms', 'cta', 'caption'] as const;
const form = reactive<Record<string, string>>({ title: '', hook: '', subline: '', terms: '', cta: '', caption: '' });
const formErr = ref<Record<string, string>>({});
function resetForm() {
    for (const f of FIELDS) form[f] = it.value?.[f] || '';
    formErr.value = {};
}
const dirty = computed(() => !!it.value && FIELDS.some((f) => (form[f] || '') !== (it.value[f] || '')));
const locked = computed(() => it.value?.status === 'posted');
const saving = ref(false);
const log = ref<string[]>([]);
const addLog = (m: string) => (log.value = [m, ...log.value].slice(0, 3));

watch(
    () => data.value?.data,
    (v) => {
        if (v && id.value) {
            it.value = v;
            resetForm();
        }
    },
    { immediate: true },
);

async function save(quiet = false) {
    if (!it.value || !dirty.value) return true;
    saving.value = true;
    formErr.value = {};
    const body: Record<string, any> = {};
    for (const f of FIELDS) if ((form[f] || '') !== (it.value[f] || '')) body[f] = form[f];
    try {
        const res = await api<any>(`content/${it.value.id}`, { method: 'PATCH', body });
        it.value = res.data;
        resetForm();
        if (!quiet) toast.ok(`Saved · trust ${res.data.trust_score}`);
        return true;
    } catch (e: any) {
        formErr.value = Object.fromEntries(Object.keys(e.errors || {}).map((f) => [f, e.field(f)]));
        toast.error(e.message);
        return false;
    } finally {
        saving.value = false;
    }
}

// Settings that save straight away.
const patching = ref('');
async function patch(body: Record<string, any>, label: string) {
    if (!it.value) return;
    const prev = { ...it.value };
    it.value = { ...it.value, ...body };
    patching.value = Object.keys(body)[0];
    try {
        const res = await api<any>(`content/${it.value.id}`, { method: 'PATCH', body });
        // Keep unsaved words while taking the fresh trust check and settings.
        it.value = res.data;
        addLog(label);
    } catch (e: any) {
        it.value = prev;
        toast.error(e.message);
    } finally {
        patching.value = '';
    }
}

const TABS = [
    ['Poster', '4:5'],
    ['Status', '9:16'],
    ['Square', '1:1'],
    ['Google', '4:3'],
    ['Print', 'A5'],
];
const THEMES = [
    ['brand', 'Brand'],
    ['light', 'Light'],
    ['dark', 'Dark'],
    ['festive', 'Festive'],
];
const languages = Object.entries(LANG_LABEL);

const photoOptions = computed(() => {
    const cat = shop.value?.category;
    const keys = Object.keys(PHOTO_SLOTS).filter((k) => cat && k.startsWith(cat + '-'));
    if (it.value?.occasion?.slug) keys.push(`occasion-${it.value.occasion.slug}`);
    if (it.value?.photo_slot && !keys.includes(it.value.photo_slot)) keys.unshift(it.value.photo_slot);
    return keys.map((k) => ({ k, t: PHOTO_SLOTS[k]?.title || (k.startsWith('occasion-') ? `Occasion · ${it.value?.occasion?.name || k.slice(9).replace(/-/g, ' ')}` : k.replace(/-/g, ' ')) }));
});

// Quick changes: plain edits made from the shop's own details.
const append = (base: string, extra: string) => (base ? `${base} · ${extra}` : extra);
const cmds = computed(() => {
    const s = shop.value || {};
    const out: { n: string; on: boolean; run: () => void }[] = [];
    if (s.landmark) {
        const on = (form.terms || '').includes(s.landmark);
        out.push({
            n: 'Landmark daalo',
            on,
            run: () => {
                form.terms = on ? form.terms.replace(` · ${s.landmark}`, '').replace(s.landmark, '').trim() : append(form.terms, s.landmark);
                addLog(on ? 'Landmark removed' : 'Landmark added');
            },
        });
    }
    if (s.area) {
        const on = [form.hook, form.subline, form.terms].some((x) => (x || '').includes(s.area));
        out.push({
            n: `${s.area} likho`,
            on,
            run: () => {
                if (on) return;
                form.terms = append(form.terms, s.area);
                addLog('Area added');
            },
        });
    }
    if (s.whatsapp) {
        const on = (form.cta || '').includes(s.whatsapp);
        out.push({
            n: 'WhatsApp number',
            on,
            run: () => {
                if (on) return;
                form.cta = `WhatsApp ${s.whatsapp}`;
                addLog('WhatsApp number on the button');
            },
        });
    }
    const festive = it.value?.theme === 'festive';
    out.push({ n: 'Tyohaar touch do', on: festive, run: () => patch({ theme: festive ? 'brand' : 'festive' }, festive ? 'Festive look removed' : 'Festive look on') });
    return out;
});

// Preview.
const RATIO_W: Record<string, string> = { '4:5': '340px', '9:16': '250px', '1:1': '320px', '4:3': '380px', A5: '300px' };
const RATIO_DIM: Record<string, string> = { '4:5': '1080 × 1350', '9:16': '1080 × 1920', '1:1': '1080 × 1080', '4:3': '1200 × 900', A5: 'A5 · 148 × 210 mm' };
const preview = computed(() => ({ ...(it.value || {}), ...form }));
const kind = computed(() => {
    const t = it.value?.type;
    if (t === 'whatsapp') return 'whatsapp';
    if (t === 'sms') return 'sms';
    if (t === 'google_post') return 'google';
    if (t === 'ad') return 'ad';
    return 'poster';
});
const caps = computed(() => {
    const it2 = it.value;
    if (!it2) return '';
    const dim = RATIO_DIM[it2.ratio] || it2.ratio;
    const ch = (it2.channels || []).map((c: string) => CHANNEL_LABEL[c] || c).join(', ');
    const extra: Record<string, string> = {
        reel: ' · cover frame shown',
        carousel: ' · first card shown',
        whatsapp: ' · only to customers who said yes',
        sms: ' · STOP to opt out included by the sender',
    };
    return `${it2.type_label} · ${kind.value === 'sms' ? 'text' : dim}${extra[it2.type] || ''}${ch ? ' · ' + ch : ''}`;
});
const beads = Array.from({ length: 16 }, (_, i) => {
    const x = 10 + i * 21.3;
    const y = 2 + 12 * (1 - Math.pow((x - 170) / 170, 2)) + 6;
    return { x: x.toFixed(1), y: y.toFixed(1), leaf: `M${x - 5} ${y - 6}l5 -5 5 5z` };
});
const msgDate = computed(() => {
    const v = it.value?.scheduled_at;
    if (!v) return '';
    return `${eyebrowDate(v)} · ${time(v)}`;
});
const smsBody = computed(() => form.caption || [form.hook, form.terms].filter(Boolean).join(' · '));
const smsCount = computed(() => {
    const dev = /[ऀ-ॿ]/.test(smsBody.value);
    const per = dev ? 70 : 160;
    const n = smsBody.value.length;
    return `${n} / ${per}${dev ? ' · Unicode' : ''} · ${Math.max(1, Math.ceil(n / per))} SMS`;
});
const bodyLabel = computed(() => (it.value?.type === 'sms' ? 'SMS text' : it.value?.type === 'whatsapp' ? 'Message' : 'Caption'));
const hashtags = computed(() => [...new Set((form.caption || '').match(/#[\p{L}\p{N}_]+/gu) || [])]);

// Where & when.
const CHANNELS = [
    { id: 'whatsapp_status', m: 'WA', n: 'WhatsApp Status' },
    { id: 'instagram', m: 'IG', n: 'Instagram' },
    { id: 'facebook', m: 'FB', n: 'Facebook Page' },
    { id: 'google', m: 'GB', n: 'Google Business' },
    { id: 'broadcast', m: 'WB', n: 'WhatsApp to customers' },
    { id: 'meta_ad', m: 'AD', n: 'Meta ad' },
];
const chans = computed(() => {
    const on = it.value?.channels || [];
    // Show the usual four, plus any of the paid ones this piece already uses.
    return CHANNELS.filter((c) => !['broadcast', 'meta_ad'].includes(c.id) || on.includes(c.id)).map((c) => ({ ...c, sw: 'sw' + (on.includes(c.id) ? ' on' : '') }));
});
function toggleCh(cid: string) {
    const on: string[] = it.value?.channels || [];
    const next = on.includes(cid) ? on.filter((x) => x !== cid) : on.concat(cid);
    patch({ channels: next }, `${CHANNEL_LABEL[cid] || cid} ${on.includes(cid) ? 'off' : 'on'}`);
}

const toLocalInput = (v: string | Date) => {
    const d = new Date(v);
    return `${isoDate(d)}T${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};
const at = ref('');
watch(
    () => it.value?.scheduled_at,
    (v) => {
        at.value = v ? toLocalInput(v) : '';
    },
    { immediate: true },
);

const credits = computed(() => auth.account?.credits);
// Plan pieces are paid for when the plan is approved and manual ones when made, so only other
// drafts with a price charge now.
const due = computed(() => (it.value && it.value.credits > 0 && it.value.source !== 'manual' && ['draft', 'ready'].includes(it.value.status) ? it.value.credits : 0));
const costLine = computed(() => `${due.value} credits · ${(credits.value?.left ?? 0) - due.value} left after`);
const busy = ref('');
const actErr = ref('');

function moneyError(e: any) {
    if (e.code === 'insufficient_credits') toast.error(e.message, { label: 'Top up', run: () => navigateTo('/app/settings?tab=billing') });
    else toast.error(e.message);
}

async function run(action: 'approve' | 'schedule' | 'publish', body?: any) {
    if (!it.value) return;
    actErr.value = '';
    if (dirty.value && !(await save(true))) return;
    if (due.value) {
        const titles = { approve: 'Approve', schedule: 'Schedule', publish: 'Post' };
        const ok = await confirm({ title: `${titles[action]} “${it.value.title}”?`, body: 'Credits for this piece are charged once.', cost: costLine.value, confirm: titles[action] });
        if (!ok) return;
    }
    busy.value = action;
    try {
        const res = await api<any>(`content/${it.value.id}/${action}`, { method: 'POST', body });
        auth.set(res.account);
        it.value = res.item;
        resetForm();
        if (action === 'publish') toast.ok(`Posted → ${(res.item.channels || []).map((c: string) => CHANNEL_LABEL[c] || c).join(', ')}`, { label: 'Undo', run: unpublish });
        else if (res.item.status === 'scheduled') toast.ok(`Scheduled · ${day(res.item.scheduled_at)}, ${time(res.item.scheduled_at).toLowerCase()}`);
        else toast.ok('Approved');
        refreshNuxtData('editor-picker');
    } catch (e: any) {
        actErr.value = e.field?.('scheduled_at') || e.field?.('status') || '';
        moneyError(e);
    } finally {
        busy.value = '';
    }
}

// The API keeps the wall-clock time of what it is sent, so send local time with its offset
// (e.g. 2026-11-27T19:30:00+05:30), not UTC.
const withOffset = (d: Date) => {
    const off = -d.getTimezoneOffset();
    const p = (n: number) => String(Math.floor(Math.abs(n))).padStart(2, '0');
    return `${isoDate(d)}T${p(d.getHours())}:${p(d.getMinutes())}:00${off >= 0 ? '+' : '-'}${p(off / 60)}:${p(off % 60)}`;
};
function schedule() {
    if (!at.value) {
        actErr.value = 'Pick a date and time.';
        return;
    }
    run('schedule', { scheduled_at: withOffset(new Date(at.value)) });
}

async function unpublish() {
    if (!it.value) return;
    try {
        const res = await api<any>(`content/${it.value.id}/unpublish`, { method: 'POST' });
        auth.set(res.account);
        it.value = res.item;
        resetForm();
        toast.info('Undone · not posted');
    } catch (e: any) {
        toast.error(e.message);
    }
}

async function skip() {
    if (!it.value) return;
    const ok = await confirm({ title: `Skip “${it.value.title}”?`, body: 'It will not go out. Any credits it used come back.', confirm: 'Skip it', danger: true });
    if (!ok) return;
    busy.value = 'skip';
    try {
        const res = await api<any>(`content/${it.value.id}/skip`, { method: 'POST' });
        auth.set(res.account);
        it.value = res.item;
        toast.ok('Skipped');
        refreshNuxtData('editor-picker');
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        busy.value = '';
    }
}

const canUndo = computed(() => it.value?.status === 'posted' && it.value.posted_at && Date.now() - new Date(it.value.posted_at).getTime() < 15 * 60 * 1000);
const open = computed(() => it.value && ['draft', 'ready', 'approved', 'scheduled'].includes(it.value.status));
const primary = computed(() => {
    const s = it.value?.status;
    if (s === 'draft' || s === 'ready') return { label: due.value ? `Approve · ${due.value} cr` : 'Approve', run: () => run('approve') };
    if (s === 'approved' || s === 'scheduled') return { label: 'Post now', run: () => run('publish', { channels: it.value.channels }) };
    return null;
});
const statusText = computed(() => {
    const v = it.value;
    if (!v) return '';
    if (v.status === 'posted') return `Posted ${v.posted_at ? relative(v.posted_at) + ', ' + time(v.posted_at).toLowerCase() : ''}`;
    if (v.status === 'scheduled' && v.scheduled_at) return `Scheduled · ${day(v.scheduled_at)}, ${time(v.scheduled_at).toLowerCase()}`;
    if (v.status === 'skipped') return 'Skipped';
    return (v.status === 'approved' ? 'Approved' : v.status === 'ready' ? 'Ready' : 'Draft') + (v.scheduled_at ? ` · planned ${day(v.scheduled_at)}, ${time(v.scheduled_at).toLowerCase()}` : '');
});

// Trust check.
const checks = computed<any[]>(() => it.value?.trust_checks || []);

// Leaving with unsaved words.
onBeforeRouteLeave(() => {
    if (dirty.value && import.meta.client && !window.confirm('You have unsaved changes. Leave without saving?')) return false;
});
onBeforeRouteUpdate(() => {
    if (dirty.value && import.meta.client && !window.confirm('You have unsaved changes. Leave without saving?')) return false;
});

const pickerItems = computed<any[]>(() => list.value?.data || []);
const pickDate = (v: string | null) => (v ? (weekday(v) + ' ' + new Date(v).getDate()).toUpperCase() : '—');
</script>

<template>
    <!-- Picker -->
    <div v-if="!id" class="wrap" style="max-width: 1000px">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 320px; min-width: 0">
                <span class="eyb"><b>EDITOR</b>·<span>NEXT UP FROM {{ eyebrowDate(new Date()) }}</span></span>
                <h1 class="h-lg">Pick a piece to edit</h1>
                <p class="ts muted">Drafts and ready posts from today on. Open one to change the words, size and look, then approve it.</p>
            </div>
            <NuxtLink class="btn bp" to="/app/create"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>Create new</NuxtLink>
        </div>
        <section class="panel rise d1">
            <LoadState :pending="listPending && !list" :error="listError" :empty="!pickerItems.length" :rows="6" empty-text="Nothing waiting to be edited" empty-hint="Make a new post, or plan the month and the drafts show up here." @retry="refreshList">
                <template #empty><NuxtLink class="btn bp sm" to="/app/create">Create a post</NuxtLink></template>
                <NuxtLink v-for="x in pickerItems" :key="x.id" class="pk" :to="{ path: '/app/studio/editor', query: { id: x.id } }">
                    <span class="mono muted">{{ pickDate(x.scheduled_at) }}</span>
                    <span :class="['ty', typeDot(x.type)]"></span>
                    <span style="min-width: 0">
                        <span class="ts" style="font-weight: 500; display: block">{{ x.title }}<span v-if="!String(x.title).includes(x.type_label)" class="tx muted"> · {{ x.type_label }}</span></span>
                        <span class="tx muted pk-hook" :lang="x.language">{{ x.hook }}</span>
                    </span>
                    <span class="pk-tags"><span class="tag">{{ x.language_label }}</span><span :class="STATUS_CLS[x.status] || 'st'">{{ x.status }}</span></span>
                </NuxtLink>
            </LoadState>
        </section>
    </div>

    <!-- One item -->
    <div v-else class="wrap" style="max-width: 1400px">
        <template v-if="!it || error">
            <LoadState :pending="pending && !it" :error="loadError" :rows="8" @retry="refresh" />
            <NuxtLink v-if="error" class="btn bs sm" to="/app/studio/editor" style="align-self: flex-start">Pick another piece</NuxtLink>
        </template>
        <template v-else>
            <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
                <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 420px; min-width: 0">
                    <span class="eyb"><b>{{ it.type_label?.toUpperCase() }}</b>·<span>{{ [it.occasion?.name, STAGE_LABEL[it.stage], SOURCE_LABEL[it.source]].filter(Boolean).join(' · ').toUpperCase() }}</span></span>
                    <h1 class="h-lg" :lang="lc" style="overflow-wrap: anywhere">{{ form.hook || 'Untitled' }}</h1>
                    <span style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap"><span :class="STATUS_CLS[it.status] || 'st'">{{ statusText }}</span><NuxtLink class="lnk tx" to="/app/studio/editor">Pick another</NuxtLink></span>
                </div>
                <div class="seg" role="group" aria-label="Detail level">
                    <button v-for="(n, i) in levels" :key="n" type="button" :class="(adv ? 1 : 0) === i ? 'on' : ''" @click="adv = i === 1">{{ n }}</button>
                </div>
                <button type="button" class="btn bs" :disabled="!dirty || saving" @click="save()">{{ saving ? 'Saving…' : dirty ? 'Save' : 'Saved' }}</button>
                <button v-if="primary" type="button" class="btn bp" :disabled="!!busy" @click="primary.run">{{ busy === 'approve' || busy === 'publish' ? 'Working…' : primary.label }}</button>
                <button v-else-if="canUndo" type="button" class="btn bs" @click="unpublish">Undo post</button>
            </div>

            <div v-if="kind !== 'sms'" class="tabs rise d1" style="overflow-x: auto" role="tablist" aria-label="Size">
                <button v-for="t in TABS" :key="t[1]" type="button" role="tab" :aria-selected="it.ratio === t[1]" :class="it.ratio === t[1] ? 'on' : ''" :disabled="patching === 'ratio'" @click="it.ratio !== t[1] && patch({ ratio: t[1] }, `Size ${t[1]}`)">
                    <span :class="it.ratio === t[1] ? 'st ok' : 'st'" style="font-size: 0; gap: 0"></span>{{ t[0] }}<span class="n">{{ t[1] }}</span>
                </button>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
                <aside class="panel rise d2 ed-left">
                    <div class="ph"><span class="eyb"><b>01</b>·<span>Quick changes</span></span></div>
                    <div class="pb" style="display: flex; flex-direction: column; gap: 16px">
                        <span class="tx muted">From your shop details. Words changes need Save; the look saves straight away.</span>
                        <div class="chips">
                            <button v-for="c in cmds" :key="c.n" type="button" :class="'chip cmd' + (c.on ? ' on' : '')" :disabled="locked && c.n !== 'Tyohaar touch do'" @click="c.run">{{ c.n }}</button>
                        </div>
                        <div v-if="log.length" style="display: flex; flex-direction: column; gap: 6px">
                            <span v-for="(g, i) in log" :key="g + i" class="st ok fade" style="font-size: 12.5px">{{ g }}</span>
                        </div>
                        <div class="fld">
                            <span class="lm">Language · bhasha</span>
                            <div class="seg full">
                                <button v-for="[code, n] in languages" :key="code" type="button" :lang="code" :class="it.language === code ? 'on' : ''" @click="it.language !== code && patch({ language: code }, `Marked as ${n}`)">{{ n }}</button>
                            </div>
                            <span class="hint">Marks the language the words are in. We don’t translate; rewrite the words to match.</span>
                        </div>
                        <div class="fld">
                            <span class="lm">Look · rang</span>
                            <div class="seg full">
                                <button v-for="[key, n] in THEMES" :key="key" type="button" :class="it.theme === key ? 'on' : ''" @click="it.theme !== key && patch({ theme: key }, `${n} look`)">{{ n }}</button>
                            </div>
                        </div>
                        <div v-if="adv" class="fld fade">
                            <label class="lm" for="ed-photo">Photo</label>
                            <select id="ed-photo" class="in" :value="it.photo_slot || ''" @change="patch({ photo_slot: ($event.target as HTMLSelectElement).value || null }, 'Photo changed')">
                                <option v-for="p in photoOptions" :key="p.k" :value="p.k">{{ p.t }}</option>
                            </select>
                        </div>
                        <div class="receipt" style="border-top: 1px dashed #cbd5e1; padding-top: 6px">
                            <div v-if="it.subline" class="ln"><span class="muted">offer</span><span :lang="lc">{{ it.subline }}</span></div>
                            <div v-if="it.product" class="ln"><span class="muted">product</span><span>{{ it.product.name }}</span></div>
                            <div v-if="it.occasion" class="ln"><span class="muted">occasion</span><span>{{ it.occasion.name }}</span></div>
                            <div class="ln"><span class="muted">angle</span><span style="text-transform: capitalize">{{ it.angle || '—' }}</span></div>
                            <div class="ln"><span class="muted">stage</span><span>{{ STAGE_LABEL[it.stage] || it.stage || '—' }}</span></div>
                            <div class="ln"><span class="muted">credits</span><span>{{ due ? `${due} cr when approved` : it.credits ? 'paid' : 'free' }}</span></div>
                        </div>
                    </div>
                </aside>

                <section style="flex: 2.1 1 480px; min-width: 0; display: flex; flex-direction: column; gap: 12px">
                    <div class="stage dots rise d3" style="background-color: #f8fafc">
                        <!-- Poster / status / reel / carousel / print -->
                        <div v-if="kind === 'poster'" style="position: relative; max-width: 100%" :style="{ width: RATIO_W[it.ratio] || '320px' }">
                            <PostCard :item="preview" :sub="true" terms-as="terms" :foot-wa="true" :photo="it.type === 'reel' ? 'top' : null" :class="it.type === 'reel' ? 'k' : ''" style="width: 100%; font-size: 12px; box-shadow: 0 24px 48px -28px rgba(15, 23, 42, 0.45)">
                                <template #over>
                                    <svg v-if="it.theme === 'festive'" class="toran fade" viewBox="0 0 340 22" preserveAspectRatio="none" aria-hidden="true">
                                        <path d="M0 2 Q170 14 340 2" stroke="#1D3A8A" stroke-width="1.5" fill="none"></path>
                                        <g v-for="(b, i) in beads" :key="i"><path :d="b.leaf" fill="#1D3A8A"></path><circle :cx="b.x" :cy="b.y" r="4.2" fill="#E8820C"></circle></g>
                                    </svg>
                                    <span v-if="it.occasion" class="badge" :style="{ top: it.theme === 'festive' ? '2.2em' : '.8em' }">{{ it.occasion.name }}</span>
                                    <span v-else-if="it.type === 'carousel'" class="badge">1 / 5</span>
                                </template>
                            </PostCard>
                        </div>

                        <!-- WhatsApp offer -->
                        <div v-else-if="kind === 'whatsapp'" class="phone">
                            <div class="scr2" style="height: 470px">
                                <div style="display: flex; gap: 8px; align-items: center; padding: 10px 12px; background: #fff; border-bottom: 1px solid #e2e8f0">
                                    <span class="mt sm">{{ shop?.initials }}</span>
                                    <span><span class="ts" style="font-weight: 600; display: block; line-height: 1.1">{{ shop?.name }}</span><span class="tx muted">Business account</span></span>
                                </div>
                                <div style="padding: 12px; display: flex; flex-direction: column; gap: 8px; flex: 1; overflow-y: auto">
                                    <span v-if="msgDate" class="mono" style="align-self: center; font-size: 10px; color: #64748b">{{ msgDate }}</span>
                                    <div class="msg">
                                        <PostCard :item="preview" :short="true" style="width: 100%; font-size: 5.4px; border-radius: 0" />
                                        <p style="padding: 8px 10px; white-space: pre-line" :lang="lc">{{ form.caption || form.subline }}</p>
                                        <span v-if="form.cta" class="mbtn" :lang="lc">{{ form.cta }}</span><span class="mbtn" style="color: #64748b">Stop offers</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SMS -->
                        <div v-else-if="kind === 'sms'" class="phone">
                            <div class="scr2" style="height: 380px">
                                <div style="padding: 10px 12px; background: #fff; border-bottom: 1px solid #e2e8f0"><span class="mono" style="font-weight: 500">{{ shop?.short_name || shop?.name }}</span></div>
                                <div style="padding: 14px 12px; display: flex; flex-direction: column; gap: 8px">
                                    <div class="msg" style="padding: 9px 11px; background: #e2e8f0; border: 0; white-space: pre-line" :lang="lc">{{ smsBody }}</div>
                                    <span class="mono" style="font-size: 10.5px; color: #64748b">{{ smsCount }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Google Business post -->
                        <div v-else-if="kind === 'google'" class="panel" style="width: 380px; max-width: 100%; overflow: hidden; box-shadow: 0 20px 40px -24px rgba(15, 23, 42, 0.3)">
                            <div style="display: flex; gap: 10px; align-items: center; padding: 12px 14px">
                                <span class="mt sm">{{ shop?.initials }}</span>
                                <span style="min-width: 0"><span class="ts" style="font-weight: 600; display: block">{{ shop?.name }}</span><span class="tx muted">{{ [shop?.category_label, [shop?.area, shop?.city].filter(Boolean).join(', '), shop?.rating ? '★ ' + shop.rating : ''].filter(Boolean).join(' · ') }}</span></span>
                            </div>
                            <PostCard :item="preview" ratio="4:3" photo="none" terms-as="pr" style="border-radius: 0; font-size: 9px" />
                            <div style="padding: 12px 14px; display: flex; flex-direction: column; gap: 10px">
                                <span v-if="form.terms" class="tag" style="align-self: flex-start" :lang="lc">OFFER · {{ form.terms }}</span>
                                <p class="ts" style="white-space: pre-line" :lang="lc">{{ form.caption || form.subline }}</p>
                                <span v-if="form.cta" class="btn bs sm" style="align-self: flex-start" :lang="lc">{{ form.cta }}</span>
                            </div>
                        </div>

                        <!-- Meta ad -->
                        <div v-else class="panel" style="width: 320px; max-width: 100%; overflow: hidden">
                            <div style="display: flex; gap: 10px; align-items: center; padding: 10px 12px">
                                <span class="mt sm">{{ shop?.initials }}</span>
                                <span style="min-width: 0"><span class="ts" style="font-weight: 600; display: block">{{ shop?.name }}</span><span class="tx muted">Sponsored{{ shop?.area ? ` · near ${shop.area}` : '' }}</span></span>
                            </div>
                            <p class="ts" style="padding: 0 12px 10px; white-space: pre-line" :lang="lc">{{ form.caption || form.subline }}</p>
                            <PostCard :item="preview" style="border-radius: 0; font-size: 8.5px" />
                            <div style="display: flex; align-items: center; padding: 10px 12px; gap: 8px; background: #f8fafc">
                                <span class="grow" style="min-width: 0"><span class="ts" style="font-weight: 600; display: block" :lang="lc">{{ form.subline || form.hook }}</span><span class="tx muted">{{ shop?.name }}{{ shop?.city ? ' · ' + shop.city : '' }}</span></span>
                                <span class="btn bs sm">Send message</span>
                            </div>
                        </div>
                    </div>
                    <p class="tx muted" style="text-align: center">{{ caps }}</p>
                </section>

                <aside style="flex: 1.1 1 290px; min-width: 0; display: flex; flex-direction: column; gap: 16px" class="rise d4">
                    <section class="panel">
                        <div class="ph"><span class="eyb"><b>02</b>·<span>Words</span></span><span class="sp"></span><span class="tag">{{ it.type_label?.toUpperCase() }}</span></div>
                        <div class="pb" style="display: flex; flex-direction: column; gap: 12px">
                            <p v-if="locked" class="tx muted">Already posted, so the words are locked. Make a new post to change them.</p>
                            <div class="fld"><label class="lab" for="ed-hk">Hook · pehli line</label><input id="ed-hk" v-model="form.hook" class="in" maxlength="160" :lang="lc" :disabled="locked" /><span v-if="formErr.hook" class="hint" style="color: #b91c1c">{{ formErr.hook }}</span></div>
                            <div class="fld"><label class="lab" for="ed-sub">Second line · offer</label><input id="ed-sub" v-model="form.subline" class="in" maxlength="200" :lang="lc" :disabled="locked" /><span v-if="formErr.subline" class="hint" style="color: #b91c1c">{{ formErr.subline }}</span></div>
                            <div class="fld"><label class="lab" for="ed-terms">Terms · deadline</label><input id="ed-terms" v-model="form.terms" class="in" maxlength="160" :lang="lc" :disabled="locked" /><span v-if="formErr.terms" class="hint" style="color: #b91c1c">{{ formErr.terms }}</span></div>
                            <div class="fld">
                                <label class="lab" for="ed-bd">{{ bodyLabel }}</label>
                                <textarea id="ed-bd" v-model="form.caption" class="in" maxlength="2200" style="height: 92px; padding: 9px 11px; line-height: 1.45; resize: vertical; font-size: 13px" :lang="lc" :disabled="locked"></textarea>
                                <span v-if="formErr.caption" class="hint" style="color: #b91c1c">{{ formErr.caption }}</span>
                                <span v-if="it.type === 'sms'" class="hint mono">{{ smsCount }}</span>
                            </div>
                            <div class="fld"><label class="lab" for="ed-cta">Button</label><input id="ed-cta" v-model="form.cta" class="in" maxlength="80" :lang="lc" list="ed-cta-list" /><datalist id="ed-cta-list"><option value="WhatsApp karo"></option><option value="Call karo"></option><option value="Dukaan ka raasta"></option><option value="Offer dekho"></option></datalist><span v-if="formErr.cta" class="hint" style="color: #b91c1c">{{ formErr.cta }}</span></div>
                            <template v-if="adv">
                                <div class="fld fade"><label class="lab" for="ed-title">Name in your calendar</label><input id="ed-title" v-model="form.title" class="in" maxlength="120" /><span v-if="formErr.title" class="hint" style="color: #b91c1c">{{ formErr.title }}</span></div>
                                <div v-if="hashtags.length" class="fld fade"><span class="lab">Hashtags in the caption</span><div class="chips"><span v-for="h in hashtags" :key="h" class="chip">{{ h }}</span></div></div>
                            </template>
                            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap">
                                <button type="button" class="btn bs sm" :disabled="!dirty || saving" @click="save()">{{ saving ? 'Saving…' : 'Save words' }}</button>
                                <button v-if="dirty" type="button" class="btn bg sm" @click="resetForm">Discard</button>
                                <span v-if="dirty" class="tx muted">Unsaved changes</span>
                            </div>
                        </div>
                    </section>

                    <section class="panel">
                        <div class="ph"><span class="eyb"><b>03</b>·<span>Where &amp; when</span></span><span class="sp"></span><span :class="STATUS_CLS[it.status] || 'st'" style="text-transform: capitalize">{{ it.status }}</span></div>
                        <button v-for="c in chans" :key="c.id" type="button" class="row" style="padding: 9px 12px" role="switch" :aria-checked="it.channels?.includes(c.id)" :disabled="!open || patching === 'channels'" @click="toggleCh(c.id)">
                            <span class="mt sm lt">{{ c.m }}</span><span class="grow ts" style="font-weight: 500">{{ c.n }}</span><span :class="c.sw"></span>
                        </button>
                        <div v-if="open" class="pb" style="display: flex; flex-direction: column; gap: 10px">
                            <label class="fld"><span class="lab">Goes out at</span><input v-model="at" type="datetime-local" class="in" /></label>
                            <span v-if="actErr" class="tx" style="color: #b91c1c" role="alert">{{ actErr }}</span>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap">
                                <button type="button" class="btn bp sm" :disabled="!!busy" @click="schedule">{{ busy === 'schedule' ? 'Scheduling…' : 'Schedule' }}</button>
                                <button type="button" class="btn bs sm" :disabled="!!busy" @click="run('publish', { channels: it.channels })">{{ busy === 'publish' ? 'Posting…' : 'Post now' }}</button>
                                <span class="sp"></span>
                                <button type="button" class="btn bg sm" :disabled="!!busy" @click="skip">{{ busy === 'skip' ? 'Skipping…' : 'Skip' }}</button>
                            </div>
                            <span v-if="due" class="tx muted">{{ due }} credits are charged on the first approve, schedule or post. Skipping gives them back.</span>
                        </div>
                        <div v-else class="pb" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap">
                            <span class="tx muted">{{ statusText }}.</span>
                            <button v-if="canUndo" type="button" class="btn bs sm" @click="unpublish">Undo post</button>
                            <NuxtLink v-if="it.status === 'posted'" class="btn bg sm" to="/app/results">See results</NuxtLink>
                        </div>
                    </section>

                    <section class="panel">
                        <div class="ph"><span class="eyb"><b>04</b>·<span>Trust check</span></span><span class="sp"></span><span class="mono">{{ it.trust_score }} / 100</span></div>
                        <div class="pb" style="display: flex; flex-direction: column; gap: 7px">
                            <template v-for="c in checks" :key="c.key">
                                <span :class="c.ok ? 'st ok' : 'st warn'">{{ c.label }}</span>
                                <span v-if="!c.ok" class="tx muted" style="margin: -3px 0 3px">{{ c.tip }}</span>
                            </template>
                            <span v-if="dirty" class="tx muted">Save to re-check your changes.</span>
                        </div>
                    </section>
                </aside>
            </div>
        </template>
    </div>
</template>

<style scoped>
.ed-left {
    flex: 1 1 260px;
    max-width: 300px;
    min-width: 0;
}
@media (max-width: 899px) {
    .ed-left {
        max-width: none;
    }
}
.stage {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    min-height: 560px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    padding: 24px;
}
.phone {
    width: 290px;
    max-width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 28px;
    background: #fff;
    padding: 10px;
    box-shadow: 0 20px 40px -24px rgba(15, 23, 42, 0.3);
}
.scr2 {
    border-radius: 20px;
    background: #f1f5f9;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.msg {
    align-self: flex-start;
    max-width: 88%;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 4px 14px 14px 14px;
    overflow: hidden;
    font-size: 13px;
    line-height: 19px;
}
.mbtn {
    display: block;
    text-align: center;
    padding: 8px;
    border-top: 1px solid #e2e8f0;
    font-weight: 500;
    font-size: 13px;
    color: #1d4ed8;
}
.toran {
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    height: 22px;
    z-index: 2;
    width: 100%;
}
.cmd {
    transition: all 0.15s;
}
.pk {
    display: grid;
    grid-template-columns: 58px 12px minmax(0, 1fr) auto;
    gap: 10px;
    align-items: center;
    padding: 12px 18px;
    border-bottom: 1px solid #e2e8f0;
    color: #0f172a;
    text-decoration: none;
    transition: background 0.15s;
}
.pk:last-child {
    border-bottom: 0;
}
.pk:hover {
    background: #f8fafc;
}
.pk-hook {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.pk-tags {
    display: flex;
    gap: 6px;
    align-items: center;
}
@media (max-width: 599px) {
    .stage {
        min-height: 0;
        padding: 16px;
    }
    .pk {
        grid-template-columns: 48px 10px minmax(0, 1fr);
        padding: 12px 14px;
    }
    .pk-tags {
        grid-column: 3;
    }
}
</style>
