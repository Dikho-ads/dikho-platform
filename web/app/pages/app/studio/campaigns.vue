<script setup lang="ts">
// Campaigns: the list (live first) and one selected campaign (?id=) with its funnel, timeline,
// pieces and messages, budget vs spent, and actions (create, edit, pause/resume, end).
definePageMeta({ layout: 'app', title: 'Campaigns' });

const auth = useAuth();
const api = useApi();
const toast = useToast();
const { confirm } = useConfirm();
const route = useRoute();
const router = useRouter();

const listReq = useApiData<any>('campaigns', 'campaigns');
await listReq;
const list = computed<any[]>(() => listReq.data.value?.data ?? []);
const selId = computed(() => (route.query.id ? String(route.query.id) : list.value[0]?.id ? String(list.value[0].id) : ''));

const detailReq = useAsyncData<any>(
    () => `campaign-${selId.value || 'none'}`,
    () =>
        selId.value
            ? api<any>(`campaigns/${selId.value}`)
                  .then((r) => r.data)
                  .catch((e) => {
                      if (e?.status === 404) return null;
                      throw e;
                  })
            : Promise.resolve(null),
);
const local = ref<any>(null);
watch(selId, () => (local.value = null));
const c = computed<any>(() => local.value ?? detailReq.data.value ?? null);

const form = ref<'new' | 'edit' | null>(route.query.new ? 'new' : null);
watch(selId, () => form.value === 'edit' && (form.value = null));

// ---------- status ----------
const today = isoDate();
const paused = computed(() => c.value?.status === 'draft' && c.value.starts_on <= today);
const statusOf = (x: any) => {
    if (x.status === 'live') return { t: 'Live', sc: 'st ok live' };
    if (x.status === 'ended') return { t: 'Ended', sc: 'st ink' };
    if (x.status === 'scheduled') return { t: 'Scheduled', sc: 'st acc' };
    return x.starts_on <= today ? { t: 'Paused', sc: 'st warn' } : { t: 'Draft', sc: 'st' };
};
const short = (d: string) => {
    const x = new Date(d + 'T00:00:00');
    return x.getDate() + ' ' + monthName(x, 'short').toUpperCase();
};
const eyebrow = computed(() => {
    const x = c.value;
    if (!x) return '';
    const r = `${short(x.starts_on)} – ${short(x.ends_on)}`;
    if (x.status === 'ended') return `${r} · ENDED`;
    if (today < x.starts_on) return `${r} · STARTS ${relative(x.starts_on).toUpperCase()}`;
    if (today > x.ends_on) return `${r} · DATES OVER`;
    return `${r} · TODAY IS DAY ${x.day} OF ${x.days}`;
});
const planId = computed(() => (c.value?.items ?? []).find((i: any) => i.plan_id)?.plan_id ?? null);
const subline = computed(() => {
    const x = c.value;
    if (!x) return '';
    const n = x.items?.length ?? 0;
    const m = x.messages?.length ?? 0;
    return [
        x.goal && 'Goal: ' + x.goal,
        x.offer,
        `${n} ${n === 1 ? 'piece' : 'pieces'} · ${m} ${m === 1 ? 'message' : 'messages'}`,
        x.occasion?.name && 'for ' + x.occasion.name,
    ]
        .filter(Boolean)
        .join(' · ');
});

const busy = ref(false);
async function setStatus(status: string) {
    const x = c.value;
    if (!x) return;
    const ask: Record<string, any> = {
        draft: { title: `Pause “${x.name}”?`, body: 'Scheduled pieces and messages for this campaign stay as they are; resume any time.', confirm: 'Pause' },
        ended: { title: `End “${x.name}”?`, body: 'The campaign is marked ended. Its numbers stay here.', confirm: 'End campaign', danger: true },
        live: { title: `Start “${x.name}” now?`, body: `It runs till ${day(x.ends_on)}.`, confirm: 'Start' },
    };
    if (ask[status] && !(await confirm(ask[status]))) return;
    busy.value = true;
    try {
        const res = await api<any>(`campaigns/${x.id}`, { method: 'PATCH', body: { status } });
        local.value = { ...x, ...res.data, items: x.items, messages: x.messages, occasion: x.occasion };
        toast.ok(status === 'draft' ? 'Campaign paused' : status === 'ended' ? 'Campaign ended' : 'Campaign is live');
        listReq.refresh();
        auth.load(true);
    } catch (e: any) {
        toast.error(toApiError(e).message);
    } finally {
        busy.value = false;
    }
}
async function onSaved(saved: any) {
    const wasEdit = form.value === 'edit';
    form.value = null;
    await listReq.refresh();
    if (wasEdit && c.value) {
        local.value = { ...c.value, ...saved, items: c.value.items, messages: c.value.messages, occasion: c.value.occasion };
    } else {
        local.value = null;
        await router.push({ query: { id: saved.id } });
    }
}
function pick(id: number) {
    router.push({ query: { id } });
}

// ---------- funnel ----------
const L = [
    ['reach', 'Saw it', 'Status, feed, ads, messages'],
    ['enquiries', 'Enquired', 'chat or call'],
    ['visits', 'Came to shop', 'QR at counter'],
    ['sales', 'Bought', 'marked by you'],
];
const p = ref(1);
let raf = 0;
function go() {
    if (!import.meta.client) return;
    cancelAnimationFrame(raf);
    p.value = 0;
    const t0 = performance.now();
    const tick = (t: number) => {
        p.value = Math.min(1, (t - t0) / 1000);
        if (p.value < 1) raf = requestAnimationFrame(tick);
    };
    raf = requestAnimationFrame(tick);
}
onMounted(go);
watch(() => c.value?.id, go);
onBeforeUnmount(() => cancelAnimationFrame(raf));
const arr = computed<number[]>(() => L.map(([k]) => c.value?.funnel?.[k!] ?? 0));
const e = computed(() => 1 - Math.pow(1 - p.value, 3));
const funnel = computed(() =>
    arr.value.map((n, i) => ({
        l: L[i]![1],
        s: L[i]![2],
        v: Math.round(n * e.value).toLocaleString('en-IN'),
        h: Math.round(16 + 30 * Math.log10(Math.max(n, 1))) + 'px',
        c: i >= 2 ? '#F2A007' : i === 1 ? '#0F172A' : '#CBD5E1',
        dl: i * 0.08 + 's',
    })),
);
const conv = computed(() => arr.value.slice(1).map((n, i) => (arr.value[i] ? Math.round((n / arr.value[i]!) * 1000) / 10 + '% →' : '— →')));
const hasNumbers = computed(() => arr.value.some((n) => n > 0));

// ---------- pieces + messages ----------
const KIND: Record<string, string> = { whatsapp: 'camp', sms: 'camp' };
const MSG_STATUS: Record<string, [string, string]> = { draft: ['Draft', 'st'], scheduled: ['Scheduled', 'st'], sending: ['Sending', 'st ok live'], sent: ['Sent', 'st ink'] };
const ITEM_STATUS: Record<string, [string, string]> = {
    draft: ['Draft', 'st'],
    ready: ['Ready', 'st acc'],
    approved: ['Approved', 'st'],
    scheduled: ['Scheduled', 'st'],
    posted: ['Posted', 'st ink'],
    skipped: ['Skipped', 'st warn'],
};
const rows = computed(() => {
    const x = c.value;
    if (!x) return [];
    const items = (x.items ?? []).map((i: any) => {
        const when = i.posted_at || i.scheduled_at;
        const where = (i.channels ?? []).map((ch: string) => CHANNEL_LABEL[ch] ?? ch).join(' + ') || i.type_label;
        const done = ['posted'].includes(i.status);
        return {
            key: 'i' + i.id,
            n: i.title || i.type_label,
            k: typeDot(i.type),
            at: when,
            w: where + (when ? ' · ' + day(when) : ''),
            v: done ? num(i.metrics?.views) : '—',
            c: done ? num((i.metrics?.chats ?? 0) + (i.metrics?.calls ?? 0)) : '—',
            e: done ? num(i.metrics?.qr_scans) : '—',
            s: ITEM_STATUS[i.status]?.[0] ?? i.status,
            sc: ITEM_STATUS[i.status]?.[1] ?? 'st',
            link: '/app/studio/editor?id=' + i.id,
        };
    });
    const msgs = (x.messages ?? []).map((m: any) => {
        const when = m.sent_at || m.scheduled_at;
        const sent = m.status === 'sent' || m.status === 'sending';
        return {
            key: 'm' + m.id,
            n: m.name,
            k: KIND[m.channel] ?? 'camp',
            at: when,
            w: `${m.channel === 'sms' ? 'SMS' : 'WhatsApp'} · ${num(m.recipients)} people` + (when ? ' · ' + day(when) : ''),
            v: sent ? num(m.delivered) : '—',
            c: sent ? num(m.replies) : '—',
            e: '—',
            s: MSG_STATUS[m.status]?.[0] ?? m.status,
            sc: MSG_STATUS[m.status]?.[1] ?? 'st',
            link: '/app/studio/messages',
        };
    });
    return [...items, ...msgs].sort((a, b) => String(a.at ?? '').localeCompare(String(b.at ?? '')));
});

// ---------- timeline ----------
const startDate = computed(() => (c.value ? new Date(c.value.starts_on + 'T00:00:00') : new Date()));
const days = computed(() => Math.max(1, c.value?.days ?? 1));
const every = computed(() => (days.value <= 14 ? 1 : days.value <= 31 ? 3 : 7));
const DAYS = computed(() =>
    Array.from({ length: days.value }, (_, i) => {
        const d = new Date(startDate.value.getTime() + i * 86400000);
        const isToday = isoDate(d) === today;
        return { i, n: i % every.value === 0 || isToday ? String(d.getDate()) : '', c: isToday ? '#0F172A' : '#94A3B8' };
    }),
);
const COLOR: Record<string, string> = {
    ad: 'repeating-linear-gradient(135deg,#0F172A 0 3px,#475569 3px 6px)',
    video: '#0F172A',
    camp: '#F2A007',
    free: '#CBD5E1',
    post: '#64748B',
};
const GANTT = computed(() =>
    rows.value
        .filter((r) => r.at)
        .map((r, i) => {
            const d = new Date(r.at);
            d.setHours(0, 0, 0, 0);
            const idx = Math.min(days.value - 1, Math.max(0, Math.round((d.getTime() - startDate.value.getTime()) / 86400000)));
            return { key: r.key, n: r.n, k: r.k, col: idx + 2 + ' / span 1', c: COLOR[r.k] ?? '#64748B', dl: 0.2 + i * 0.06 + 's' };
        }),
);
const todayPos = computed(() => {
    const x = c.value;
    if (!x || today < x.starts_on || today > x.ends_on) return null;
    return `calc(190px + (100% - 190px) * ${(x.day - 0.5) / days.value})`;
});
const todayLabel = computed(() => eyebrowDate(new Date()));

// ---------- budget ----------
const spentPct = computed(() => (c.value?.budget_paise ? Math.min(100, Math.round((c.value.spent_paise / c.value.budget_paise) * 100)) : 0));
const msgCost = computed(() => (c.value?.messages ?? []).reduce((a: number, m: any) => a + (m.cost_paise ?? 0), 0));

// ---------- shop page ----------
const shop = computed(() => auth.shop ?? {});
const WEEKDAY: Record<string, string> = { mon: 'Mondays', tue: 'Tuesdays', wed: 'Wednesdays', thu: 'Thursdays', fri: 'Fridays', sat: 'Saturdays', sun: 'Sundays' };
const hhmm = (t?: string) => (t ? time(`2000-01-01T${t}:00`).replace(':00', '').toLowerCase() : '');
const hours = computed(() => {
    const s = shop.value;
    const parts = [s.opens_at && s.closes_at ? `Open ${hhmm(s.opens_at)} – ${hhmm(s.closes_at)}` : '', s.landmark ? 'near ' + s.landmark : '', s.weekly_off ? 'closed ' + WEEKDAY[s.weekly_off] : 'open all week'];
    return parts.filter(Boolean).join(' · ');
});
</script>

<template>
    <div class="wrap" style="max-width: 1400px">
        <LoadState :pending="listReq.pending.value && !list.length" :error="listReq.error.value" :rows="6" @retry="listReq.refresh()">
            <template v-if="form === 'new'">
                <CampaignsForm @saved="onSaved" @cancel="form = null" />
            </template>

            <div v-if="!list.length && form !== 'new'" class="panel pb rise" style="display: flex; flex-direction: column; gap: 10px; align-items: flex-start">
                <span class="eyb"><b>CAMPAIGNS</b>·<span>None yet</span></span>
                <h1 class="h-lg">Run your first campaign</h1>
                <p class="ts muted">A campaign groups an offer, its dates, a budget and the posts and messages that push it, so you can see what it brought in.</p>
                <button type="button" class="btn bp" @click="form = 'new'">New campaign</button>
            </div>

            <template v-if="list.length">
                <div class="clist rise" role="tablist" aria-label="Your campaigns">
                    <button v-for="x in list" :key="x.id" type="button" role="tab" :aria-selected="String(x.id) === selId" :class="'cc' + (String(x.id) === selId ? ' on' : '')" @click="pick(x.id)">
                        <span class="ts" style="font-weight: 500; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis">{{ x.name }}</span>
                        <span style="display: flex; gap: 8px; align-items: center; justify-content: space-between">
                            <span class="mono muted" style="font-size: 11px">{{ short(x.starts_on) }} – {{ short(x.ends_on) }}</span>
                            <span :class="statusOf(x).sc">{{ statusOf(x).t }}</span>
                        </span>
                    </button>
                    <button v-if="form !== 'new'" type="button" class="cc add" @click="form = 'new'">+ New campaign</button>
                </div>

                <LoadState :pending="detailReq.pending.value && !c" :error="detailReq.error.value" :empty="!c" empty-text="Campaign not found" :rows="6" @retry="detailReq.refresh()">
                    <template #empty><button type="button" class="btn bs sm" @click="pick(list[0].id)">Open the latest one</button></template>
                    <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
                        <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 440px; min-width: 0">
                            <span class="eyb"><b>CAMPAIGN</b>·<span>{{ eyebrow }}</span></span>
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap">
                                <h1 class="h-lg">{{ c.name }}</h1>
                                <span :class="statusOf(c).sc">{{ statusOf(c).t }}</span>
                            </div>
                            <p class="ts muted">{{ subline }}</p>
                        </div>
                        <button v-if="c.status === 'live'" type="button" class="btn bs" :disabled="busy" @click="setStatus('draft')">Pause</button>
                        <button v-else-if="c.status !== 'ended'" type="button" class="btn bs" :disabled="busy" @click="setStatus('live')">{{ paused ? 'Resume' : 'Start now' }}</button>
                        <button v-if="c.status !== 'ended'" type="button" class="btn bs" :disabled="busy" @click="setStatus('ended')">End</button>
                        <button type="button" class="btn bs" @click="form = form === 'edit' ? null : 'edit'">{{ form === 'edit' ? 'Close edit' : 'Edit' }}</button>
                        <NuxtLink v-if="planId" class="btn bp" :to="{ path: '/app/studio/plan/result', query: { id: planId } }">Open plan</NuxtLink>
                    </div>

                    <CampaignsForm v-if="form === 'edit'" :key="'edit-' + c.id" :campaign="c" @saved="onSaved" @cancel="form = null" />

                    <section class="panel rise d1">
                        <div class="ph" style="flex-wrap: wrap"><span class="eyb"><b>01</b>·<span>From seeing to buying</span></span><span class="sp"></span><span v-if="c.cost_per_enquiry_paise != null" class="tx muted">{{ inr(c.cost_per_enquiry_paise) }} per enquiry</span></div>
                        <div class="pb">
                            <div :key="c.id" style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; align-items: end">
                                <div v-for="f in funnel" :key="f.l" class="fs">
                                    <span class="lm">{{ f.l }}</span><span class="num">{{ f.v }}</span>
                                    <span class="fb" :style="{ height: f.h, background: f.c, animationDelay: f.dl }"></span>
                                    <span class="tx muted">{{ f.s }}</span>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-top: 10px">
                                <span></span>
                                <span v-for="(x, i) in conv" :key="i" class="cv" style="justify-self: start">{{ x }}</span>
                            </div>
                            <p class="tx muted" style="margin-top: 14px">
                                <template v-if="hasNumbers">“Came to shop” is counted when the campaign’s QR is scanned at the counter; “bought” is what you mark in one tap.</template>
                                <template v-else>No numbers yet. They fill in as the campaign’s posts and messages go out.</template>
                            </p>
                        </div>
                    </section>

                    <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
                        <div style="flex: 2 1 620px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                            <section class="panel rise d2" style="overflow: hidden">
                                <div class="ph" style="flex-wrap: wrap"><span class="eyb"><b>02</b>·<span>Timeline</span></span><span class="sp"></span><span v-if="todayPos" class="tx muted">Marigold line = today, {{ todayLabel }}</span></div>
                                <div v-if="GANTT.length" class="tscroll">
                                    <div class="gwrap" :style="{ '--days': days }">
                                        <div class="gt" style="background: #f8fafc; min-height: 32px">
                                            <span class="lm" style="padding-left: 16px">Piece</span>
                                            <span v-for="d in DAYS" :key="d.i" class="mono" :style="{ fontSize: '10.5px', color: d.c, textAlign: 'center' }">{{ d.n }}</span>
                                        </div>
                                        <div v-for="g in GANTT" :key="g.key" class="gt">
                                            <span style="display: flex; align-items: center; gap: 8px; padding-left: 16px; font-size: 13px; min-width: 0"><span :class="'ty ' + g.k"></span><span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap">{{ g.n }}</span></span>
                                            <span :style="{ gridColumn: g.col, padding: '0 1px' }"><span class="gbar" :style="{ display: 'block', background: g.c, animationDelay: g.dl }"></span></span>
                                        </div>
                                        <span v-if="todayPos" style="position: absolute; top: 0; bottom: 0; width: 2px; background: #f2a007" :style="{ left: todayPos }"></span>
                                    </div>
                                </div>
                                <p v-else class="ts muted" style="padding: 14px 18px">Nothing is dated in this campaign yet.</p>
                            </section>
                            <section class="panel rise d3" style="overflow: hidden">
                                <div class="ph"><span class="eyb"><b>03</b>·<span>What each piece did</span></span></div>
                                <div v-if="rows.length" class="tscroll">
                                    <table class="tbl">
                                        <thead>
                                            <tr><th>Piece</th><th>Where</th><th style="text-align: right">Saw it</th><th style="text-align: right">Chats + calls</th><th style="text-align: right">QR scans</th><th>Status</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="r in rows" :key="r.key">
                                                <td><NuxtLink :to="r.link" style="display: inline-flex; gap: 8px; align-items: center"><span :class="'ty ' + r.k"></span><b style="font-weight: 500">{{ r.n }}</b></NuxtLink></td>
                                                <td class="muted">{{ r.w }}</td>
                                                <td class="mono" style="text-align: right">{{ r.v }}</td>
                                                <td class="mono" style="text-align: right">{{ r.c }}</td>
                                                <td class="mono" style="text-align: right">{{ r.e }}</td>
                                                <td><span :class="r.sc">{{ r.s }}</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="pb" style="display: flex; flex-direction: column; gap: 10px; align-items: flex-start">
                                    <span class="ts muted">No posts or messages in this campaign yet.</span>
                                    <span style="display: flex; gap: 8px; flex-wrap: wrap"><NuxtLink class="btn bs sm" to="/app/create">Make a post</NuxtLink><NuxtLink class="btn bs sm" to="/app/studio/messages">Send a message</NuxtLink></span>
                                </div>
                                <p v-if="rows.length" class="tx muted" style="padding: 10px 18px">For messages, “saw it” is delivered and “chats + calls” is replies.</p>
                            </section>
                        </div>
                        <aside style="flex: 1 1 340px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                            <section class="panel rise d2" style="overflow: hidden">
                                <div class="ph"><span class="eyb"><b>04</b>·<span>Your shop page</span></span></div>
                                <div class="url"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 10.5h15v10h-15zM8 10.5V7a4 4 0 0 1 8 0v3.5"></path></svg><span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap">dikho.in/{{ (shop.city || '').toLowerCase() }}/{{ shop.slug }}</span></div>
                                <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px">
                                    <div style="display: flex; gap: 12px; align-items: center">
                                        <span class="mt" style="width: 44px; height: 44px; background: #1d3a8a; flex: none">{{ shop.initials }}</span>
                                        <span style="min-width: 0">
                                            <span class="h-md" style="display: block">{{ shop.name }}</span>
                                            <span class="tx muted">{{ shop.category_label }} · {{ [shop.area, shop.city].filter(Boolean).join(', ') }}<template v-if="shop.rating"> · ★ {{ shop.rating }} ({{ num(shop.reviews_count) }})</template></span>
                                        </span>
                                    </div>
                                    <div v-if="c.offer && c.status !== 'ended'" style="background: #ffd23f; color: #1d3a8a; border-radius: 10px; padding: 10px 12px; font: 600 13.5px/1.35 var(--f)">{{ c.name }} — {{ c.offer }} · till {{ day(c.ends_on) }}</div>
                                    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px"><span class="btn bp sm">WhatsApp</span><span class="btn bs sm">Call</span><span class="btn bs sm">Directions</span></div>
                                    <span class="tx muted">{{ hours }}</span>
                                </div>
                            </section>
                            <section class="panel rise d3">
                                <div class="ph"><span class="eyb"><b>05</b>·<span>Budget</span></span><span class="sp"></span><span class="mono">{{ spentPct }}%</span></div>
                                <div class="pb" style="display: flex; flex-direction: column; gap: 12px">
                                    <span class="bar-t" style="height: 8px"><i :style="{ width: spentPct + '%', background: spentPct >= 90 ? '#F2A007' : '#0F172A' }"></i></span>
                                    <div class="receipt">
                                        <div class="ln"><span class="muted">budget</span><span>{{ c.budget_paise ? inr(c.budget_paise) : 'not set' }}</span></div>
                                        <div class="ln"><span class="muted">spent so far</span><span>{{ inr(c.spent_paise) }}</span></div>
                                        <div v-if="msgCost" class="ln"><span class="muted">of which messages</span><span>{{ inr(msgCost) }}</span></div>
                                        <div v-if="c.budget_paise" class="ln"><span class="muted">left</span><span>{{ inr(Math.max(0, c.budget_paise - c.spent_paise)) }}</span></div>
                                        <div class="ln tot"><span>cost per enquiry</span><span>{{ c.cost_per_enquiry_paise != null ? inr(c.cost_per_enquiry_paise) : '—' }}</span></div>
                                        <div class="ln"><span class="muted">days</span><span>{{ c.status === 'ended' || today > c.ends_on ? c.days + ' of ' + c.days : 'day ' + c.day + ' of ' + c.days }}</span></div>
                                    </div>
                                </div>
                            </section>
                        </aside>
                    </div>
                </LoadState>
            </template>
        </LoadState>
    </div>
</template>

<style scoped>
.fs{display:flex;flex-direction:column;gap:8px;min-width:0}.fb{border-radius:8px 8px 3px 3px;transform-origin:bottom;animation:growY .9s var(--ease) both}.cv{align-self:center;font:500 11px/1 var(--fm);color:#64748B;padding:4px 6px;border:1px dashed #CBD5E1;border-radius:6px;background:#fff}
.gwrap{position:relative;min-width:560px}
.gt{display:grid;grid-template-columns:190px repeat(var(--days),minmax(0,1fr));align-items:center;min-height:38px;border-bottom:1px solid #E2E8F0}.gt:last-of-type{border-bottom:0}.gbar{height:14px;border-radius:4px;transform-origin:left;animation:growX .8s var(--ease) both}
.url{display:flex;align-items:center;gap:8px;padding:8px 12px;background:#F1F5F9;border-bottom:1px solid #E2E8F0;font:500 12px var(--fm);color:#334155;min-width:0}
.clist{display:flex;gap:10px;overflow-x:auto;padding-bottom:4px;max-width:100%}
.cc{flex:0 0 220px;display:flex;flex-direction:column;gap:6px;padding:10px 12px;border:1px solid #E2E8F0;border-radius:12px;background:#fff;text-align:left;cursor:pointer;min-width:0;transition:border-color .15s,box-shadow .15s}
.cc:hover{border-color:#CBD5E1}
.cc.on{border-color:#0F172A;box-shadow:inset 0 0 0 1px #0F172A}
.cc.add{flex-basis:160px;align-items:center;justify-content:center;border-style:dashed;color:#334155;font-weight:500}
@media (max-width:599px){.fs .num{font-size:20px}}
</style>
