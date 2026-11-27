<script setup lang="ts">
// Customers: the shop's customer book from GET customers, with stage tabs, search (?q=), paging,
// a detail drawer (GET/PATCH customers/{id}), an add form (POST customers) and win-back
// (POST customers/win-back, paid from the wallet per WhatsApp recipient).
definePageMeta({ layout: 'app', title: 'Customers' });

const route = useRoute();
const router = useRouter();
const api = useApi();
const auth = useAuth();
const toast = useToast();
const { confirm } = useConfirm();

// Meta's WhatsApp rate the wallet pays per win-back recipient (API.md: 86 paise).
const WA_RATE_PAISE = 86;
const PER_PAGE = 25;

type Customer = {
    id: number;
    name: string;
    initials: string;
    phone: string;
    area: string | null;
    gender: string | null;
    birthday: string | null;
    whatsapp_consent: boolean;
    consent_at: string | null;
    stage: 'new' | 'regular' | 'at_risk' | 'lapsed';
    visits: number;
    spend_paise: number;
    first_visit_on: string | null;
    last_visit_on: string | null;
    days_since_visit: number | null;
    last_purchase: string | null;
    source: string | null;
    notes: string | null;
};

const STAGES = [
    { id: '', n: 'All', key: 'total', rule: 'everyone in your book' },
    { id: 'new', n: 'New', key: 'new', rule: 'first visits' },
    { id: 'regular', n: 'Regular', key: 'regular', rule: 'keep coming back' },
    { id: 'at_risk', n: 'At risk', key: 'at_risk', rule: 'gap longer than usual' },
    { id: 'lapsed', n: 'Lapsed', key: 'lapsed', rule: 'stopped coming' },
] as const;
const SC: Record<string, [string, string]> = { new: ['st ink', 'New'], regular: ['st ok', 'Regular'], at_risk: ['st warn', 'At risk'], lapsed: ['st bad', 'Lapsed'] };
const LIFE = ['new', 'regular', 'at_risk', 'lapsed'];
const SOURCES: Record<string, string> = { walk_in: 'Walk-in', whatsapp: 'WhatsApp', instagram: 'Instagram', meta_ad: 'Meta ad', referral: 'Referral', google: 'Google' };
const GENDERS: [string, string][] = [['f', 'Woman'], ['m', 'Man'], ['o', 'Other']];

const phone = (p?: string | null) => (p && p.length === 10 ? p.slice(0, 5) + ' ' + p.slice(5) : p || '—');
const since = (d: number | null) => (d == null ? '—' : d === 0 ? 'today' : d === 1 ? '1 day' : num(d) + ' days');
const firstName = (n: string) => n.split(' ')[0];

// ---- list, synced with the URL (?stage=&q=&page=) ----
const stage = computed(() => (typeof route.query.stage === 'string' ? route.query.stage : ''));
const q = computed(() => (typeof route.query.q === 'string' ? route.query.q : ''));
const page = computed(() => Math.max(1, Number(route.query.page) || 1));

function setQuery(patch: Record<string, string | number | null>) {
    const next: Record<string, any> = { ...route.query, ...patch };
    for (const k of Object.keys(next)) if (next[k] === '' || next[k] == null || (k === 'page' && next[k] === 1)) delete next[k];
    router.replace({ query: next });
}

type ListResponse = { data: Customer[]; meta: any; summary: any };

// The API searches names and, when the term has 3+ digits, phone numbers.
function loadList(): Promise<ListResponse> {
    const term = q.value.trim();
    return api<ListResponse>('customers', { query: { stage: stage.value || undefined, q: term || undefined, page: page.value, per_page: PER_PAGE } });
}

const { data, pending, error, refresh } = useAsyncData<ListResponse>('customers', loadList, { watch: [stage, q, page] });
const rows = computed(() => data.value?.data ?? []);
const meta = computed(() => data.value?.meta);
const summary = computed(() => data.value?.summary);

const search = ref(q.value);
watch(q, (v) => (search.value = v));
let searchTimer: ReturnType<typeof setTimeout> | undefined;
watch(search, (v) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        if (v.trim() !== q.value) setQuery({ q: v.trim(), page: null });
    }, 350);
});
onBeforeUnmount(() => clearTimeout(searchTimer));

const tabs = computed(() => STAGES.map((g, i) => ({ ...g, c: summary.value ? num(summary.value[g.key]) : '—', arrow: i > 0 && i < STAGES.length - 1 })));
const range = computed(() => {
    const m = meta.value;
    if (!m || !m.total) return '';
    const from = (m.current_page - 1) * m.per_page + 1;
    return `${num(from)}–${num(Math.min(m.total, m.current_page * m.per_page))} of ${num(m.total)}`;
});
const emptyText = computed(() => (q.value ? `Nobody matches “${q.value}”` : stage.value ? `No ${SC[stage.value]?.[1].toLowerCase()} customers right now` : 'No customers yet'));
const emptyHint = computed(() => (q.value ? 'Try part of the name or the last 4 digits of the number.' : stage.value ? 'Good news, or try another stage.' : 'Add the people who buy from you; they get offers only after saying yes.'));

// ---- picking for win-back: lapsed and at-risk people, kept across pages ----
const picked = ref<Map<number, Customer>>(new Map());
const canPick = (c: Customer) => c.stage === 'at_risk' || c.stage === 'lapsed';
function togglePick(c: Customer) {
    const m = new Map(picked.value);
    m.has(c.id) ? m.delete(c.id) : m.set(c.id, c);
    picked.value = m;
}
function pickAllShown() {
    const m = new Map(picked.value);
    rows.value.filter(canPick).forEach((c) => m.set(c.id, c));
    picked.value = m;
}
const pickedList = computed(() => [...picked.value.values()]);
const winTarget = computed(() => (summary.value ? summary.value.at_risk + summary.value.lapsed : 0));

function startPicking() {
    if (picked.value.size) return openWin();
    setQuery({ stage: 'lapsed', q: null, page: null });
    toast.info('Tick the people you want to win back, then press “Send win-back”.');
}

// ---- win-back modal ----
const win = ref(false);
const winList = ref<Customer[]>([]);
const offer = ref('');
const who = ref(0);
const sending = ref(false);
const reach = computed(() => winList.value.filter((c) => c.whatsapp_consent));
const skipped = computed(() => winList.value.length - reach.value.length);
const winCost = computed(() => reach.value.length * WA_RATE_PAISE);
const wallet = computed(() => auth.account?.wallet_paise ?? 0);
const previewPeople = computed(() => (reach.value.length ? reach.value : winList.value).slice(0, 3));
const previewText = computed(() => {
    const p = previewPeople.value[who.value] ?? previewPeople.value[0];
    const shop = auth.shop?.name ?? '';
    return `Namaste ${p ? firstName(p.name) : '{name}'}! Aapko ${shop} mein miss kiya. ${offer.value.trim() || 'Is hafte aapke liye ek khaas offer hai.'} Reply karein ya dukaan aayein.`;
});

function openWin(list?: Customer[]) {
    winList.value = list ?? pickedList.value;
    who.value = 0;
    win.value = true;
}
async function startWin() {
    if (!reach.value.length) return;
    const ok = await confirm({
        title: `Send a win-back offer to ${reach.value.length} ${reach.value.length === 1 ? 'person' : 'people'}?`,
        body: skipped.value ? `${skipped.value} without WhatsApp consent will be skipped.` : 'Everyone picked has said yes to WhatsApp offers.',
        cost: `${reach.value.length} × ${inr(WA_RATE_PAISE, { paise: true })} = ${inr(winCost.value, { paise: true })} from wallet ${inr(wallet.value, { paise: true })}`,
        confirm: 'Send now',
    });
    if (!ok) return;
    sending.value = true;
    try {
        const res = await api<any>('customers/win-back', { method: 'POST', body: { ids: winList.value.map((c) => c.id), offer: offer.value.trim() || undefined } });
        auth.set(res.account);
        toast.ok(`Sent to ${res.sent_to}, skipped ${res.skipped_no_consent} without consent · ${inr(res.cost_paise, { paise: true })} from wallet`);
        win.value = false;
        offer.value = '';
        picked.value = new Map();
    } catch (e: any) {
        toast.error(e.code === 'insufficient_wallet' ? `Not enough in the wallet: ${inr(e.data?.needed, { paise: true })} needed, ${inr(e.data?.available, { paise: true })} available.` : e.message, e.code === 'insufficient_wallet' ? { label: 'Add money', run: () => navigateTo('/app/settings?tab=wallet') } : undefined);
    } finally {
        sending.value = false;
    }
}

// ---- detail drawer ----
const openId = ref<number | null>(null);
const detail = ref<Customer | null>(null);
const detailLoading = ref(false);
const detailError = ref<any>(null);
const form = reactive({ notes: '', birthday: '' });
const saving = ref<string | null>(null);

async function openCustomer(c: Customer) {
    openId.value = c.id;
    detail.value = c;
    form.notes = c.notes ?? '';
    form.birthday = c.birthday ?? '';
    await loadDetail();
}
async function loadDetail() {
    if (!openId.value) return;
    detailLoading.value = true;
    detailError.value = null;
    try {
        const res = await api<{ data: Customer }>(`customers/${openId.value}`);
        detail.value = res.data;
        form.notes = res.data.notes ?? '';
        form.birthday = res.data.birthday ?? '';
    } catch (e) {
        detailError.value = e;
    } finally {
        detailLoading.value = false;
    }
}
function closeDrawer() {
    openId.value = null;
    detail.value = null;
}
async function patchCustomer(body: Record<string, any>, what: string, okText: string) {
    if (!detail.value) return;
    saving.value = what;
    try {
        const res = await api<{ data: Customer }>(`customers/${detail.value.id}`, { method: 'PATCH', body });
        detail.value = res.data;
        // data is a shallow ref (Nuxt 4): swap the row in so the table re-renders.
        if (data.value) data.value = { ...data.value, data: data.value.data.map((r) => (r.id === res.data.id ? res.data : r)) };
        if (picked.value.has(res.data.id)) picked.value.set(res.data.id, res.data);
        toast.ok(okText);
        return true;
    } catch (e: any) {
        toast.error(e.field?.('birthday') || e.field?.('notes') || e.message);
        return false;
    } finally {
        saving.value = null;
    }
}
const toggleConsent = () =>
    detail.value &&
    patchCustomer({ whatsapp_consent: !detail.value.whatsapp_consent }, 'consent', detail.value.whatsapp_consent ? 'Marked as not agreeing to WhatsApp offers' : 'Consent recorded · offers can go to this person');
const saveNotes = () => patchCustomer({ notes: form.notes.trim() || null, birthday: form.birthday || null }, 'notes', 'Saved');

const d = computed(() => {
    const c = detail.value;
    if (!c) return null;
    const at = LIFE.indexOf(c.stage);
    return {
        consentLong: c.whatsapp_consent ? 'Said yes to WhatsApp offers' + (c.consent_at ? ' on ' + day(c.consent_at) : '') : 'Has not said yes · no offers go out',
        since: c.last_visit_on ? 'last came ' + relative(c.last_visit_on) : 'no visit recorded',
        life: LIFE.map((k, i) => ({ n: SC[k][1], c: i === at ? '#0F172A' : at > i ? '#CBD5E1' : '#F1F5F9', tc: i === at ? '#0F172A' : '#64748B', fw: i === at ? 600 : 400 })),
    };
});

// ---- add customer ----
const adding = ref(false);
const addBusy = ref(false);
const blank = () => ({ name: '', phone: '', area: '', gender: '', birthday: '', whatsapp_consent: false, source: 'walk_in', last_purchase: '', notes: '' });
const add = reactive(blank());
const addErr = ref<Record<string, string | undefined>>({});
function openAdd() {
    Object.assign(add, blank());
    addErr.value = {};
    adding.value = true;
}
async function saveAdd() {
    addBusy.value = true;
    addErr.value = {};
    const body: Record<string, any> = { name: add.name.trim(), phone: add.phone.replace(/\D/g, '').slice(-10), whatsapp_consent: add.whatsapp_consent, source: add.source || null };
    if (add.area.trim()) body.area = add.area.trim();
    if (add.gender) body.gender = add.gender;
    if (add.birthday) body.birthday = add.birthday;
    if (add.last_purchase.trim()) body.last_purchase = add.last_purchase.trim();
    if (add.notes.trim()) body.notes = add.notes.trim();
    try {
        const res = await api<{ data: Customer }>('customers', { method: 'POST', body });
        adding.value = false;
        toast.ok(`${res.data.name} added to your customers`);
        await refresh();
    } catch (e: any) {
        if (Object.keys(e.errors || {}).length) {
            addErr.value = Object.fromEntries(Object.keys(e.errors).map((k) => [k, e.field(k)]));
        } else toast.error(e.message);
    } finally {
        addBusy.value = false;
    }
}

function onKey(e: KeyboardEvent) {
    if (e.key !== 'Escape') return;
    if (adding.value) adding.value = false;
    else if (win.value) win.value = false;
    else if (openId.value) closeDrawer();
}
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <div class="wrap">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 360px">
                <span class="eyb"><b>{{ summary ? num(summary.total) : '—' }}</b>·<span>CUSTOMERS · {{ summary ? num(summary.whatsapp_consent) : '—' }} SAID YES TO OFFERS</span></span>
                <h1 class="h-lg">Your regulars, and who stopped coming</h1>
            </div>
            <button type="button" class="btn bs" @click="openAdd">
                <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>Add customer
            </button>
            <button type="button" class="btn bp" :disabled="!summary" @click="startPicking">{{ picked.size ? `Win back ${picked.size}` : winTarget ? `Win back · ${num(winTarget)} drifting` : 'Win back' }}</button>
        </div>

        <div class="panel rise d1 stages">
            <button v-for="g in tabs" :key="g.id" type="button" :class="'lc' + (stage === g.id ? ' on' : '')" :aria-pressed="stage === g.id" @click="setQuery({ stage: g.id, page: null })">
                <span class="lm">{{ g.n }}</span><span class="num" style="font-size: 26px">{{ g.c }}</span><span class="tx muted">{{ g.rule }}</span>
                <span v-if="g.arrow" class="arw"><svg viewBox="0 0 24 24" width="8" height="8" aria-hidden="true" style="fill: none; stroke: currentColor; stroke-width: 3"><path d="M9 5l7 7-7 7"></path></svg></span>
            </button>
        </div>

        <section class="panel rise d2" style="overflow: hidden">
            <div class="ph" style="flex-wrap: wrap">
                <label class="search" style="width: 280px; background: #fff">
                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4"></path></svg>
                    <input v-model="search" placeholder="Name or number" aria-label="Search customers" type="search" />
                </label>
                <span class="sp"></span>
                <span v-if="picked.size" class="fade" style="display: inline-flex; flex-wrap: wrap; gap: 10px; align-items: center">
                    <span class="mono">{{ picked.size }} selected</span>
                    <button type="button" class="btn bp sm" @click="openWin()">Send win-back</button>
                    <button type="button" class="btn bg sm" @click="picked = new Map()">Clear</button>
                </span>
                <span v-else-if="rows.some(canPick)" style="display: inline-flex; gap: 10px; align-items: center">
                    <span class="tx muted">Tick at-risk or lapsed people to win them back</span>
                    <button type="button" class="btn bg sm" @click="pickAllShown">Select shown</button>
                </span>
                <span v-else class="tx muted">Stages follow each person’s own visit pattern</span>
            </div>
            <LoadState :pending="pending && !data" :error="!data ? error : null" :empty="!rows.length" :empty-text="emptyText" :empty-hint="emptyHint" :rows="6" @retry="refresh">
                <template #empty>
                    <button v-if="q" type="button" class="btn bs sm" @click="setQuery({ q: null, page: null })">Clear search</button>
                    <button v-else-if="!stage" type="button" class="btn bp sm" @click="openAdd">Add a customer</button>
                </template>
                <div class="tscroll" :style="{ opacity: pending ? 0.55 : 1, transition: 'opacity .15s' }">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th style="width: 40px"></th>
                                <th>Customer</th>
                                <th>Last visit</th>
                                <th>Since then</th>
                                <th>Bought</th>
                                <th>Stage</th>
                                <th>Offers</th>
                                <th style="text-align: right">Visits</th>
                                <th style="text-align: right">Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in rows" :key="r.id" :class="openId === r.id ? 'sel' : ''" @click="openCustomer(r)">
                                <td>
                                    <button
                                        v-if="canPick(r)"
                                        type="button"
                                        :class="'ck' + (picked.has(r.id) ? ' on' : '')"
                                        :aria-label="'Select ' + r.name"
                                        :aria-pressed="picked.has(r.id)"
                                        @click.stop="togglePick(r)"
                                    >
                                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg>
                                    </button>
                                </td>
                                <td>
                                    <span style="display: inline-flex; gap: 10px; align-items: center">
                                        <span class="av">{{ r.initials }}</span>
                                        <span style="display: flex; flex-direction: column; gap: 2px; min-width: 0">
                                            <b style="font-weight: 500; white-space: nowrap">{{ r.name }}</b><span class="mono muted" style="font-size: 11.5px">{{ phone(r.phone) }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td class="mono" style="white-space: nowrap">{{ r.last_visit_on ? day(r.last_visit_on).toUpperCase() : '—' }}</td>
                                <td class="mono muted" style="white-space: nowrap">{{ since(r.days_since_visit) }}</td>
                                <td>{{ r.last_purchase || '—' }}</td>
                                <td><span :class="SC[r.stage]?.[0] || 'st'">{{ SC[r.stage]?.[1] || r.stage }}</span></td>
                                <td :class="r.whatsapp_consent ? '' : 'muted'" style="white-space: nowrap">{{ r.whatsapp_consent ? 'opted in' : 'not opted in' }}</td>
                                <td class="mono" style="text-align: right">{{ num(r.visits) }}</td>
                                <td class="mono" style="text-align: right; white-space: nowrap">{{ inr(r.spend_paise) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="meta && meta.last_page > 1" class="ph" style="border-top: 1px solid #e2e8f0; border-bottom: 0">
                    <span class="mono muted">{{ range }}</span><span class="sp"></span>
                    <button type="button" class="btn bs sm" :disabled="meta.current_page <= 1 || pending" @click="setQuery({ page: meta.current_page - 1 })">Previous</button>
                    <span class="mono">{{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button type="button" class="btn bs sm" :disabled="meta.current_page >= meta.last_page || pending" @click="setQuery({ page: meta.current_page + 1 })">Next</button>
                </div>
                <div v-else-if="range" class="ph" style="border-top: 1px solid #e2e8f0; border-bottom: 0"><span class="mono muted">{{ range }}</span></div>
            </LoadState>
        </section>

        <template v-if="openId && detail && d">
            <div class="scrim" style="background: rgba(15, 23, 42, 0.12)" @click="closeDrawer"></div>
            <aside class="drawer" aria-label="Customer details">
                <div class="ph">
                    <span class="av">{{ detail.initials }}</span>
                    <span class="grow" style="min-width: 0"><span class="h-sm" style="display: block">{{ detail.name }}</span><span class="tx muted">{{ d.consentLong }}</span></span>
                    <button type="button" class="btn bg ib sm" aria-label="Close" @click="closeDrawer"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>
                </div>
                <div class="pb" style="display: flex; flex-direction: column; gap: 18px">
                    <p v-if="detailError" class="err">{{ detailError.message }} <button type="button" class="btn bg sm" @click="loadDetail">Retry</button></p>
                    <div style="display: flex; flex-direction: column; gap: 10px">
                        <div style="display: flex; justify-content: space-between"><span class="lm">Stage</span><span class="mono muted">{{ d.since }}</span></div>
                        <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 4px">
                            <span v-for="l in d.life" :key="l.n" style="display: flex; flex-direction: column; gap: 6px">
                                <span :style="`height: 6px; border-radius: 3px; background: ${l.c}; transition: background .4s`"></span>
                                <span class="tx" :style="`color: ${l.tc}; font-weight: ${l.fw}`">{{ l.n }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="receipt" :style="{ opacity: detailLoading ? 0.6 : 1 }">
                        <div class="ln" style="border-bottom: 1px solid #e2e8f0"><span class="muted">phone</span><a :href="'tel:+91' + detail.phone" style="color: inherit">{{ phone(detail.phone) }}</a></div>
                        <div class="ln" style="border-bottom: 1px solid #e2e8f0"><span class="muted">area</span><span>{{ detail.area || '—' }}</span></div>
                        <div class="ln" style="border-bottom: 1px solid #e2e8f0"><span class="muted">last bought</span><span>{{ detail.last_purchase || '—' }}{{ detail.last_visit_on ? ' · ' + day(detail.last_visit_on) : '' }}</span></div>
                        <div class="ln" style="border-bottom: 1px solid #e2e8f0"><span class="muted">visits · spent</span><span>{{ num(detail.visits) }} · {{ inr(detail.spend_paise) }}</span></div>
                        <div class="ln" style="border-bottom: 1px solid #e2e8f0"><span class="muted">customer since</span><span>{{ detail.first_visit_on ? day(detail.first_visit_on) : '—' }}</span></div>
                        <div class="ln"><span class="muted">came from</span><span>{{ (detail.source && SOURCES[detail.source]) || detail.source || '—' }}</span></div>
                    </div>
                    <button type="button" class="srow-btn" :disabled="saving === 'consent'" @click="toggleConsent">
                        <span><span class="h-sm" style="display: block">Said yes to WhatsApp offers</span><span class="tx muted">Only people who agreed get offers; change it if they tell you.</span></span>
                        <span :class="'sw' + (detail.whatsapp_consent ? ' on' : '')" role="switch" :aria-checked="detail.whatsapp_consent"></span>
                    </button>
                    <div class="fld">
                        <label class="lab" for="cbd">Birthday</label>
                        <input id="cbd" v-model="form.birthday" class="in" type="date" :max="isoDate()" />
                    </div>
                    <div class="fld">
                        <label class="lab" for="cnt">Notes</label>
                        <textarea id="cnt" v-model="form.notes" class="in" maxlength="1000" style="height: 84px; padding: 10px 12px; resize: vertical; line-height: 1.5" placeholder="What they like, when they usually come…"></textarea>
                    </div>
                    <button type="button" class="btn bs" :disabled="saving === 'notes' || (form.notes === (detail.notes ?? '') && form.birthday === (detail.birthday ?? ''))" @click="saveNotes">
                        {{ saving === 'notes' ? 'Saving…' : 'Save notes and birthday' }}
                    </button>
                    <button type="button" class="btn bp lg full" :disabled="!detail.whatsapp_consent" @click="openWin([detail])">
                        {{ detail.whatsapp_consent ? 'Send a personal offer' : 'Needs consent before an offer' }}
                    </button>
                </div>
            </aside>
        </template>

        <template v-if="win">
            <div class="scrim" @click="win = false"></div>
            <div class="modal" role="dialog" aria-modal="true" aria-label="Win-back">
                <div class="ph">
                    <span class="eyb"><b>WIN-BACK</b>·<span>{{ winList.length }} {{ winList.length === 1 ? 'PERSON' : 'PEOPLE' }} · {{ reach.length }} OPTED IN</span></span><span class="sp"></span>
                    <button type="button" class="btn bg ib sm" aria-label="Close" @click="win = false"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>
                </div>
                <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
                    <div class="fld">
                        <label class="lab" for="wbo">Offer (optional)</label>
                        <input id="wbo" v-model="offer" class="in" maxlength="160" placeholder="Is hafte aapke liye ek khaas offer hai." />
                    </div>
                    <div v-if="previewPeople.length > 1" style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center">
                        <span class="tx muted">Preview for</span>
                        <button v-for="(p, i) in previewPeople" :key="p.id" type="button" :class="'chip' + (who === i ? ' on' : '')" style="height: 28px" @click="who = i">{{ firstName(p.name) }}</button>
                    </div>
                    <div class="wbrow">
                        <span style="display: flex; flex-direction: column; gap: 4px"><span class="h-sm">WhatsApp</span><span class="mono muted" style="font-size: 11px">goes out now</span></span>
                        <p class="bub">{{ previewText }}</p>
                    </div>
                    <div class="receipt" style="border-top: 1px dashed #cbd5e1; padding-top: 6px">
                        <div class="ln"><span class="muted">{{ reach.length }} × {{ inr(WA_RATE_PAISE, { paise: true }) }} WhatsApp</span><span>{{ inr(winCost, { paise: true }) }} wallet</span></div>
                        <div v-if="skipped" class="ln"><span class="muted">skipped · no consent</span><span>{{ skipped }}</span></div>
                        <div class="ln"><span class="muted">wallet after</span><span :style="wallet < winCost ? 'color: #B91C1C' : ''">{{ inr(wallet - winCost, { paise: true }) }}</span></div>
                    </div>
                    <p v-if="!reach.length" class="err">None of these people said yes to WhatsApp offers, so nothing can be sent.</p>
                    <p v-else class="tx muted">Only people who said yes get it. One message each, paid from the wallet at Meta’s rate.</p>
                    <div style="display: flex; gap: 8px; justify-content: flex-end">
                        <button type="button" class="btn bs" @click="win = false">Cancel</button>
                        <button type="button" class="btn bp" :disabled="!reach.length || sending" @click="startWin">{{ sending ? 'Sending…' : `Send to ${reach.length}` }}</button>
                    </div>
                </div>
            </div>
        </template>

        <template v-if="adding">
            <div class="scrim" @click="adding = false"></div>
            <form class="modal" role="dialog" aria-modal="true" aria-label="Add a customer" novalidate @submit.prevent="saveAdd">
                <div class="ph">
                    <span class="eyb"><b>NEW</b>·<span>ADD A CUSTOMER</span></span><span class="sp"></span>
                    <button type="button" class="btn bg ib sm" aria-label="Close" @click="adding = false"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>
                </div>
                <div class="pb addgrid">
                    <div class="fld">
                        <label class="lab" for="an">Name</label><input id="an" v-model="add.name" class="in" maxlength="80" autocomplete="off" required />
                        <span v-if="addErr.name" class="err">{{ addErr.name }}</span>
                    </div>
                    <div class="fld">
                        <label class="lab" for="ap">Mobile number</label><input id="ap" v-model="add.phone" class="in" inputmode="numeric" maxlength="14" placeholder="98200 00147" required />
                        <span v-if="addErr.phone" class="err">{{ addErr.phone }}</span>
                    </div>
                    <div class="fld">
                        <label class="lab" for="aa">Area</label><input id="aa" v-model="add.area" class="in" maxlength="80" />
                        <span v-if="addErr.area" class="err">{{ addErr.area }}</span>
                    </div>
                    <div class="fld">
                        <label class="lab" for="ab">Birthday</label><input id="ab" v-model="add.birthday" class="in" type="date" :max="isoDate()" />
                        <span v-if="addErr.birthday" class="err">{{ addErr.birthday }}</span>
                    </div>
                    <div class="fld">
                        <span class="lab">Gender</span>
                        <span class="seg"><button v-for="g in GENDERS" :key="g[0]" type="button" :class="add.gender === g[0] ? 'on' : ''" @click="add.gender = add.gender === g[0] ? '' : g[0]">{{ g[1] }}</button></span>
                    </div>
                    <div class="fld">
                        <label class="lab" for="as">Came from</label>
                        <select id="as" v-model="add.source" class="in"><option v-for="(l, k) in SOURCES" :key="k" :value="k">{{ l }}</option></select>
                    </div>
                    <div class="fld" style="grid-column: 1 / -1">
                        <label class="lab" for="al">Bought</label><input id="al" v-model="add.last_purchase" class="in" maxlength="120" placeholder="What they bought last" />
                    </div>
                    <button type="button" class="srow-btn" style="grid-column: 1 / -1" @click="add.whatsapp_consent = !add.whatsapp_consent">
                        <span><span class="h-sm" style="display: block">Said yes to WhatsApp offers</span><span class="tx muted">Ask at the counter. Without a yes they never get offers.</span></span>
                        <span :class="'sw' + (add.whatsapp_consent ? ' on' : '')" role="switch" :aria-checked="add.whatsapp_consent"></span>
                    </button>
                    <div style="grid-column: 1 / -1; display: flex; gap: 8px; justify-content: flex-end">
                        <button type="button" class="btn bs" @click="adding = false">Cancel</button>
                        <button type="submit" class="btn bp" :disabled="addBusy">{{ addBusy ? 'Adding…' : 'Add customer' }}</button>
                    </div>
                </div>
            </form>
        </template>
    </div>
</template>

<style scoped>
.stages {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    overflow: hidden;
}
.lc {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 14px 16px;
    border: 0;
    border-right: 1px solid #e2e8f0;
    background: #fff;
    text-align: left;
    cursor: pointer;
    position: relative;
    transition: background 0.15s;
    min-width: 0;
}
.lc:last-child {
    border-right: 0;
}
.lc:hover {
    background: #f8fafc;
}
.lc.on {
    background: #f1f5f9;
}
.lc.on::after {
    content: '';
    position: absolute;
    left: 16px;
    right: 16px;
    bottom: 0;
    height: 2px;
    background: #0f172a;
    animation: growX 0.35s var(--ease) both;
    transform-origin: left;
}
.arw {
    position: absolute;
    right: -7px;
    top: 50%;
    transform: translateY(-50%);
    width: 14px;
    height: 14px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 50%;
    display: grid;
    place-items: center;
    z-index: 1;
    color: #94a3b8;
}
.bub {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px 14px 14px 14px;
    padding: 10px 12px;
    font-size: 13.5px;
    line-height: 20px;
}
.wbrow {
    display: grid;
    grid-template-columns: 120px minmax(0, 1fr);
    gap: 14px;
    align-items: start;
}
.srow-btn {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 16px;
    align-items: center;
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    text-align: left;
    cursor: pointer;
}
.srow-btn:hover {
    background: #f8fafc;
}
.addgrid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    max-height: calc(100vh - 160px);
    overflow-y: auto;
}
.tbl tbody tr:focus-within td {
    background: var(--bg);
}
@media (max-width: 760px) {
    .stages {
        grid-template-columns: repeat(5, minmax(118px, 1fr));
        overflow-x: auto;
        scrollbar-width: none;
    }
    .stages::-webkit-scrollbar {
        display: none;
    }
    .arw {
        display: none;
    }
    .addgrid {
        grid-template-columns: minmax(0, 1fr);
    }
    .wbrow {
        grid-template-columns: minmax(0, 1fr);
        gap: 8px;
    }
}
</style>
