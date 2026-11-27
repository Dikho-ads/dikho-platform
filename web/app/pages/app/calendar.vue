<script setup lang="ts">
// Month calendar of every content piece (any status), the occasions that fall in the month and the
// month's plan. ?month=YYYY-MM picks the month (default: this month).
definePageMeta({ layout: 'app', title: 'Calendar' });

const route = useRoute();
const auth = useAuth();
const api = useApi();
const toast = useToast();
const { confirm } = useConfirm();

const pad = (n: number) => String(n).padStart(2, '0');
const thisMonth = isoDate().slice(0, 7);
const month = computed(() => {
    const q = String(route.query.month || '');
    return /^\d{4}-(0[1-9]|1[0-2])$/.test(q) ? q : thisMonth;
});
const ym = computed(() => month.value.split('-').map(Number) as [number, number]);
const shiftMonth = (m: string, by: number) => {
    const [y, mo] = m.split('-').map(Number) as [number, number];
    const d = new Date(y, mo - 1 + by, 1);
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;
};
const lastDay = computed(() => new Date(ym.value[0], ym.value[1], 0).getDate());
const from = computed(() => `${month.value}-01`);
const to = computed(() => `${month.value}-${pad(lastDay.value)}`);
const monthLabel = (m: string, style: 'long' | 'short' = 'long') => monthName(`${m}-01`, style);
const title = computed(() => `${monthLabel(month.value)} ${ym.value[0]}`);
const navMonths = computed(() => [-1, 0, 1].map((by) => {
    const m = shiftMonth(month.value, by);
    return { m, label: monthLabel(m, 'short'), on: by === 0 };
}));

const content = useApiData<{ data: any[] }>(() => `cal-content-${month.value}`, 'content', { query: () => ({ from: from.value, to: to.value, limit: 500 }) });
const occ = useApiData<{ data: any[] }>(() => `cal-occasions-${month.value}`, 'occasions', { query: () => ({ from: from.value, to: to.value }) });
const planReq = useApiData<{ plan: any | null }>(() => `cal-plan-${month.value}`, () => `plans/${month.value}`);

const items = computed<any[]>(() => content.data.value?.data ?? []);
const occasions = computed<any[]>(() => occ.data.value?.data ?? []);
const plan = computed(() => planReq.data.value?.plan ?? null);

const today = isoDate();
const wd = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const OFF_KEY = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
const offIdx = computed(() => OFF_KEY.indexOf(auth.shop?.weekly_off ?? ''));
const legend = [['video', 'Video'], ['post', 'Posts'], ['ad', 'Ads'], ['camp', 'Messages'], ['free', 'Free']];
const dayOf = (it: any) => (it.scheduled_at || '').slice(0, 10);
const crTxt = (it: any) => (it.credits ? it.credits + ' cr' : 'free');
const dotFor = (it: any) => (it.credits ? typeDot(it.type) : 'free');
const STATUS: Record<string, [string, string]> = {
    draft: ['Draft', 'st warn'],
    ready: ['Ready', 'st acc'],
    approved: ['Approved', 'st ok'],
    scheduled: ['Scheduled', 'st ok'],
    posted: ['Posted', 'st ink'],
    skipped: ['Skipped', 'st'],
};
const waiting = (it: any) => it.status === 'draft' || it.status === 'ready';

const byDay = computed(() => {
    const m: Record<string, any[]> = {};
    for (const it of items.value) (m[dayOf(it)] ||= []).push(it);
    return m;
});
const occByDay = computed(() => {
    const m: Record<string, any[]> = {};
    for (const o of occasions.value) {
        const key = o.starts_on < from.value ? from.value : o.starts_on;
        (m[key] ||= []).push(o);
    }
    return m;
});

const sel = ref<string>('');
watch(month, () => (sel.value = ''));
const selDay = computed(() => sel.value || (month.value === thisMonth ? today : from.value));

// Grid: Monday-first weeks covering the month; days outside the month are greyed and empty.
const cells = computed(() => {
    const first = new Date(ym.value[0], ym.value[1] - 1, 1);
    const lead = (first.getDay() + 6) % 7;
    const total = Math.ceil((lead + lastDay.value) / 7) * 7;
    return Array.from({ length: total }, (_, i) => {
        const d = new Date(ym.value[0], ym.value[1] - 1, 1 - lead + i);
        const iso = isoDate(d);
        const inMonth = d.getMonth() === ym.value[1] - 1;
        const list = inMonth ? byDay.value[iso] ?? [] : [];
        return {
            iso,
            d: d.getDate(),
            inMonth,
            off: inMonth && (i % 7) === offIdx.value,
            isToday: iso === today,
            on: inMonth && iso === sel.value,
            items: list.slice(0, 3),
            more: Math.max(0, list.length - 3),
            occ: inMonth ? occByDay.value[iso] ?? [] : [],
        };
    });
});

// Week of the selected day (Mon–Sun) and its pieces still waiting for approval.
const weekStart = computed(() => {
    const d = new Date(selDay.value + 'T00:00:00');
    d.setDate(d.getDate() - ((d.getDay() + 6) % 7));
    return d;
});
const weekEnd = computed(() => {
    const d = new Date(weekStart.value);
    d.setDate(d.getDate() + 6);
    return d;
});
const weekItems = computed(() => {
    const a = isoDate(weekStart.value);
    const b = isoDate(weekEnd.value);
    return items.value.filter((it) => dayOf(it) >= a && dayOf(it) <= b && waiting(it) && dayOf(it) >= today);
});
const weekCredits = computed(() => weekItems.value.reduce((s, it) => s + (it.credits || 0), 0));
const dayItems = computed(() => byDay.value[selDay.value] ?? []);
const dayOcc = computed(() => occasions.value.filter((o) => o.starts_on <= selDay.value && o.ends_on >= selDay.value));

// Receipt: what the month's pieces cost, by kind.
const kinds = [['video', 'Reels'], ['post', 'Posts'], ['ad', 'Ads'], ['camp', 'Messages'], ['free', 'Free pieces']];
const live = computed(() => items.value.filter((it) => it.status !== 'skipped'));
const ledger = computed(() => kinds.map(([k, n]) => {
    const xs = live.value.filter((it) => dotFor(it) === k);
    return { k, n, c: xs.length, cr: xs.reduce((a, x) => a + (x.credits || 0), 0) };
}).filter((l) => l.c));
const toApprove = computed(() => live.value.filter(waiting).reduce((a, x) => a + (x.credits || 0), 0));
const left = computed(() => auth.account?.credits?.left ?? 0);
const totalCr = computed(() => auth.account?.credits?.total ?? 0);
const on = computed(() => (totalCr.value ? Math.min(50, Math.round((toApprove.value / totalCr.value) * 50)) : 0));
const ticks = computed(() => Array.from({ length: 50 }, (_, i) => ({ c: i < on.value ? 'on' : '', d: (0.2 + i * 0.012).toFixed(3) + 's', st: i >= on.value ? 'background: repeating-linear-gradient(135deg,#CBD5E1 0 1.5px,transparent 1.5px 3.5px)' : '' })));
const counts = computed(() => ({
    posted: items.value.filter((i) => i.status === 'posted').length,
    waiting: items.value.filter(waiting).length,
    scheduled: items.value.filter((i) => i.status === 'scheduled' || i.status === 'approved').length,
}));

const planSt = computed(() => {
    const p = plan.value;
    if (!p) return { cls: 'st', txt: counts.value.waiting ? `No plan · ${counts.value.waiting} drafts waiting` : 'No plan yet' };
    if (p.status === 'review') return { cls: 'st warn', txt: `Plan v${p.version} · waiting for your review` };
    if (p.status === 'approved') return { cls: 'st ok', txt: `Plan approved${p.approved_at ? ' · ' + day(p.approved_at) : ''}` };
    return { cls: 'st', txt: `Plan · ${p.status}` };
});
const eyebrow = computed(() => {
    const pkg = auth.account?.package?.name?.toUpperCase() ?? '';
    const n = items.value.length;
    return `${n} PIECE${n === 1 ? '' : 'S'}${pkg ? ' · ' + pkg : ''}`;
});
const occLabel = (o: any) => (o.starts_on === o.ends_on ? day(o.starts_on) : `${day(o.starts_on)} – ${day(o.ends_on)}`);

const pending = computed(() => content.pending.value || occ.pending.value || planReq.pending.value);
const error = computed(() => content.error.value || occ.error.value || planReq.error.value);
function retry() {
    content.refresh();
    occ.refresh();
    planReq.refresh();
}

const busy = ref(false);
async function approveWeek() {
    const list = weekItems.value;
    if (!list.length || busy.value) return;
    const ok = await confirm({
        title: `Approve ${list.length} piece${list.length === 1 ? '' : 's'} for ${day(weekStart.value)} – ${day(weekEnd.value)}?`,
        body: 'They go out on their days. You can still edit or skip any of them before then.',
        cost: weekCredits.value ? `${weekCredits.value} credits · you have ${left.value}` : 'Free · no credits',
        confirm: 'Approve week',
    });
    if (!ok) return;
    busy.value = true;
    try {
        const res = await api<{ items: any[]; account: any }>('content/approve-batch', { method: 'POST', body: { ids: list.map((i) => i.id) } });
        auth.set(res.account);
        const fresh = new Map(res.items.map((i) => [i.id, i]));
        if (content.data.value) content.data.value = { ...content.data.value, data: items.value.map((i) => fresh.get(i.id) ?? i) };
        toast.ok(`${res.items.length} approved · they’ll go out on their days`);
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="wrap" style="max-width: 1320px">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 360px">
                <span class="eyb"><b>CALENDAR</b>·<span>{{ eyebrow }}</span></span>
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 14px"><h1 class="h-lg">{{ title }}</h1><span v-if="!planReq.pending.value" :class="planSt.cls">{{ planSt.txt }}</span></div>
            </div>
            <div class="seg"><NuxtLink v-for="n in navMonths" :key="n.m" :to="{ query: { month: n.m } }" :class="n.on ? 'on' : ''">{{ n.label }}</NuxtLink></div>
            <NuxtLink v-if="plan && plan.status === 'review'" class="btn bp" :to="{ path: '/app/studio/plan/result', query: { id: plan.id } }">Review plan</NuxtLink>
            <NuxtLink v-else-if="plan" class="btn bs" :to="{ path: '/app/studio/plan/result', query: { id: plan.id } }">Open plan</NuxtLink>
            <NuxtLink v-else-if="month >= thisMonth" class="btn bp" to="/app/studio/plan">Plan {{ monthLabel(month) }}</NuxtLink>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
            <section class="panel rise d1" style="flex: 2.3 1 640px; min-width: 0; overflow: hidden">
                <div class="ph" style="flex-wrap: wrap">
                    <span class="eyb"><b>01</b>·<span>Calendar</span></span><span class="sp"></span>
                    <span style="display: flex; flex-wrap: wrap; gap: 14px" class="tx muted"><span v-for="l in legend" :key="l[0]" style="display: inline-flex; gap: 6px; align-items: center"><span :class="['ty', l[0]]"></span>{{ l[1] }}</span></span>
                </div>
                <LoadState :pending="pending" :error="error" :rows="6" @retry="retry">
                    <div class="cal-scroll"><div class="cal-inner">
                    <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border-bottom: 1px solid #E2E8F0; background: #F8FAFC"><span v-for="w in wd" :key="w" class="lm" style="padding: 9px 10px">{{ w }}</span></div>
                    <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr))">
                        <div v-for="c in cells" :key="c.iso" :class="['cal', { x: !c.inMonth, on: c.on, today: c.isToday }]" @click="c.inMonth && (sel = c.iso)">
                            <span style="display: flex; justify-content: space-between; align-items: center"><span class="mono dn" style="font-size: 12px">{{ c.d }}</span><span v-if="c.off" class="mono" style="font-size: 10px; color: #94A3B8">OFF</span></span>
                            <NuxtLink v-for="o in c.occ" :key="'o' + o.id" class="oc-flag" :to="'/app/occasions?id=' + o.id" :title="o.rule" @click.stop>{{ o.name }}</NuxtLink>
                            <NuxtLink v-for="it in c.items" :key="it.id" :class="['it', it.status]" :to="{ path: '/app/studio/editor', query: { id: it.id } }" :title="it.hook" @click.stop>
                                <span style="display: flex; gap: 6px; align-items: center"><span :class="['ty', dotFor(it)]"></span><span class="mono" style="font-size: 10.5px; color: #64748B">{{ crTxt(it) }}</span><span class="sp"></span><span class="mono stx">{{ STATUS[it.status]?.[0] }}</span></span>
                                <span class="itt">{{ it.title }}</span>
                            </NuxtLink>
                            <button v-if="c.more" type="button" class="more mono" @click.stop="sel = c.iso">+{{ c.more }} more</button>
                        </div>
                    </div>
                    </div></div>
                </LoadState>
                <div class="row" style="border-top: 1px solid #E2E8F0"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true" style="color: #64748B; flex-shrink: 0"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5M12 8v.01"></path></svg><span class="tx muted">Dashed pieces are drafts waiting for you; faded ones are already posted. Click a piece to edit it, or a day to see it and approve its week.</span></div>
            </section>
            <aside style="flex: 1 1 320px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                <section class="panel rise d2">
                    <div class="ph"><span class="eyb"><b>02</b>·<span>{{ eyebrowDate(selDay) }}</span></span><span class="sp"></span><span class="mono muted">{{ dayItems.length }} piece{{ dayItems.length === 1 ? '' : 's' }}</span></div>
                    <div class="pb fade" style="display: flex; flex-direction: column; gap: 12px">
                        <NuxtLink v-for="o in dayOcc" :key="o.id" class="tag acc" style="align-self: flex-start; text-decoration: none" :to="'/app/occasions?id=' + o.id">{{ o.name }} · {{ occLabel(o) }}</NuxtLink>
                        <p v-if="!dayItems.length" class="ts muted">Nothing on this day. <NuxtLink :to="{ path: '/app/create' }" style="color: #0F172A; font-weight: 500">Make a post</NuxtLink></p>
                        <NuxtLink v-for="it in dayItems" :key="it.id" class="dayit" :to="{ path: '/app/studio/editor', query: { id: it.id } }">
                            <span :class="['ty', dotFor(it)]" style="width: 12px; height: 12px; margin-top: 4px; flex-shrink: 0"></span>
                            <span class="grow" style="min-width: 0"><span class="ts" style="font-weight: 500; display: block">{{ it.hook || it.title }}</span><span class="tx muted">{{ it.scheduled_at ? time(it.scheduled_at) : '' }} · {{ it.type_label }} · {{ it.language_label }}</span></span>
                            <span style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px"><span :class="STATUS[it.status]?.[1]">{{ STATUS[it.status]?.[0] }}</span><span class="mono muted" style="font-size: 11px">{{ crTxt(it) }}</span></span>
                        </NuxtLink>
                        <div style="display: flex; flex-direction: column; gap: 8px; border-top: 1px dashed #E2E8F0; padding-top: 12px">
                            <span class="tx muted">Week of {{ day(weekStart) }} – {{ day(weekEnd) }}: {{ weekItems.length ? `${weekItems.length} waiting for approval · ${weekCredits} cr` : 'nothing waiting for approval' }}</span>
                            <button v-if="weekItems.length" type="button" class="btn bp" :disabled="busy" @click="approveWeek">{{ busy ? 'Approving…' : `Approve week · ${weekCredits} cr` }}</button>
                        </div>
                    </div>
                </section>
                <section class="panel rise d3">
                    <div class="ph"><span class="eyb"><b>03</b>·<span>Credit receipt</span></span><span class="sp"></span><span class="mono muted">{{ auth.account?.package?.name?.toUpperCase() }} · {{ totalCr }}</span></div>
                    <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
                        <div class="ticks"><i v-for="(k, i) in ticks" :key="i" :class="k.c" :style="`animation-delay: ${k.d}; ${k.st}`"></i></div>
                        <div class="receipt">
                            <div v-for="l in ledger" :key="l.k" class="ln"><span style="display: inline-flex; gap: 8px; align-items: center"><span :class="['ty', l.k]"></span>{{ l.n }} × {{ l.c }}</span><span>{{ l.cr }} cr</span></div>
                            <div v-if="!ledger.length" class="ln"><span class="muted">nothing in {{ monthLabel(month) }} yet</span><span>0 cr</span></div>
                            <div class="ln tot"><span>still to approve</span><span>{{ toApprove }} cr</span></div>
                            <div class="ln"><span class="muted">credits left now</span><span>{{ num(left) }} cr</span></div>
                            <div class="ln"><span class="muted">price this month</span><span>{{ inr(auth.account?.package?.price_paise) }} · fixed</span></div>
                        </div>
                        <span class="tx muted">{{ counts.posted }} posted · {{ counts.scheduled }} approved or scheduled · {{ counts.waiting }} waiting</span>
                    </div>
                </section>
                <section class="panel rise d4">
                    <div class="ph"><span class="eyb"><b>04</b>·<span>Occasions this month</span></span><span class="sp"></span><NuxtLink to="/app/occasions" class="tx" style="color: #0F172A">All</NuxtLink></div>
                    <LoadState :pending="occ.pending.value" :error="occ.error.value" :empty="!occasions.length" empty-text="No occasions in this month" @retry="occ.refresh">
                        <NuxtLink v-for="o in occasions" :key="o.id" class="row" :to="'/app/occasions?id=' + o.id" style="padding: 10px 18px; text-decoration: none; color: inherit">
                            <span :class="o.planned_items ? 'st ok' : 'st acc'"></span>
                            <span class="grow" style="min-width: 0"><span class="ts" style="font-weight: 500; display: block">{{ o.name }}</span><span class="tx muted">{{ occLabel(o) }} · {{ o.planned_items ? `${o.planned_items} planned` : 'nothing planned' }}</span></span>
                        </NuxtLink>
                    </LoadState>
                </section>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.cal { min-height: 112px; padding: 8px; border-right: 1px solid #E2E8F0; border-bottom: 1px solid #E2E8F0; display: flex; flex-direction: column; gap: 6px; min-width: 0; cursor: pointer; transition: background .15s; }
.cal:nth-child(7n) { border-right: 0; }
.cal:hover { background: #FBFCFD; }
.cal.x { background: #F8FAFC; color: #94A3B8; cursor: default; }
.cal.on { background: #F1F5F9; }
.cal.today .dn { background: #0F172A; color: #fff; border-radius: 5px; padding: 1px 5px; }
.it { display: flex; flex-direction: column; gap: 5px; width: 100%; padding: 7px 8px; border: 1px solid #E2E8F0; border-radius: 8px; background: #fff; text-align: left; cursor: pointer; text-decoration: none; color: #0F172A; transition: border-color .15s, transform .2s var(--ease), box-shadow .15s; }
.it:hover { border-color: #94A3B8; transform: translateY(-1px); }
.it.draft, .it.ready { border-style: dashed; border-color: #CBD5E1; }
.it.posted { opacity: .62; }
.it.skipped { opacity: .5; }
.it.skipped .itt { text-decoration: line-through; }
.itt { font: 500 12.5px/1.25 var(--f); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow-wrap: anywhere; }
.stx { font-size: 9.5px; color: #94A3B8; text-transform: uppercase; white-space: nowrap; }
.oc-flag { display: block; font: 600 10.5px/1.3 var(--f); color: #92400E; background: #FEF3C7; border-radius: 5px; padding: 3px 6px; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.more { align-self: flex-start; border: 0; background: none; padding: 0; font-size: 10.5px; color: #64748B; cursor: pointer; }
.dayit { display: flex; gap: 10px; align-items: flex-start; padding: 10px; border: 1px solid #E2E8F0; border-radius: 10px; text-decoration: none; color: #0F172A; transition: border-color .15s; }
.dayit:hover { border-color: #94A3B8; }
</style>

<style scoped>
/* Phones: keep day cells readable and let the month scroll sideways. */
@media (max-width: 699px) {
    .cal-scroll {
        overflow-x: auto;
    }
    .cal-inner {
        min-width: 680px;
    }
}
</style>
