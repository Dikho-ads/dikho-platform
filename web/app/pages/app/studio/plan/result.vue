<script setup lang="ts">
// A generated month plan: strategy (KPIs, reasons, funnel, wallet split), the channel mix and the
// week-by-week calendar worked out from its items, a re-plan box, and approval (spends credits).
definePageMeta({ layout: 'app', title: 'Plan mode' });

const auth = useAuth();
const api = useApi();
const toast = useToast();
const { confirm } = useConfirm();
const route = useRoute();

const ymKey = (d: Date) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
const nextMonth = (() => {
    const d = new Date();
    return ymKey(new Date(d.getFullYear(), d.getMonth() + 1, 1));
})();
const monthOf = (key: string) => new Date(Number(key.slice(0, 4)), Number(key.slice(5, 7)) - 1, 1);

const id = computed(() => (route.query.id ? String(route.query.id) : ''));
const { data, pending, error, refresh } = useAsyncData<any>(
    () => `plan-result-${id.value || 'latest-' + nextMonth}`,
    () =>
        (id.value ? api<any>(`plans/id/${id.value}`).then((r) => r.data) : api<any>(`plans/${nextMonth}`).then((r) => r.plan)).catch((e) => {
            if (e?.status === 404) return null;
            throw e;
        }),
);
const workflowsReq = useApiData<any>('plan-result-workflows', 'workflows', { lazy: true });

// Approving replaces the plan locally with the server's answer.
const approved = ref<any>(null);
watch(id, () => (approved.value = null));
const plan = computed<any>(() => approved.value ?? data.value ?? null);
const isApproved = computed(() => plan.value?.status === 'approved');
const items = computed<any[]>(() => plan.value?.items ?? []);
const strategy = computed<any>(() => plan.value?.strategy ?? {});
const brief = computed<any>(() => plan.value?.brief ?? {});
const left = computed(() => auth.account?.credits?.left ?? 0);
const shop = computed(() => auth.shop ?? {});

const mon = computed(() => (plan.value ? monthOf(plan.value.month) : new Date()));
const monthLong = computed(() => monthName(mon.value));
const itemDate = (i: any) => new Date(i.scheduled_at);
const range = computed(() => {
    if (!items.value.length) return monthLong.value;
    const a = itemDate(items.value[0]);
    const b = itemDate(items.value[items.value.length - 1]);
    return a.getDate() === b.getDate() ? `${a.getDate()} ${monthLong.value}` : `${a.getDate()} – ${b.getDate()} ${monthLong.value}`;
});
const goalLabel = computed(() => brief.value.goal_label || strategy.value.goal?.label || 'Month plan');
const ver = computed(() => `v${plan.value?.version ?? 1} · ` + (isApproved.value && plan.value?.approved_at ? 'APPROVED ' + day(plan.value.approved_at).toUpperCase() : 'WAITING FOR YOUR APPROVAL'));

// ---------- strategy ----------
const kpis = computed(() =>
    (strategy.value.kpis ?? []).map((k: any) => {
        // The stored "Credits" KPI can be 0 (written before the total was known); the plan's own budget is the truth.
        const v = k.label === 'Credits' ? plan.value?.credits_budget ?? k.value : k.value;
        return { l: k.label, v: num(v), s: k.note };
    }),
);
const whys = computed(() => (strategy.value.reasons ?? []).map((r: any, i: number) => ({ n: String(i + 1), t: r.title, d: r.detail })));
const FUNNEL_COLOR: Record<string, string> = { awareness: '#0F172A', trust: '#475569', buying: '#F2A007', return: '#94A3B8' };
const funnel = computed(() => {
    const list = strategy.value.funnel ?? [];
    const top = Math.max(1, ...list.map((x: any) => x.share));
    return list.map((x: any) => ({
        n: x.name,
        en: x.label,
        p: x.share + '%',
        c: x.count,
        fm: x.formats,
        msg: items.value.find((i) => i.stage === x.key && i.hook)?.hook ?? '',
        col: FUNNEL_COLOR[x.key] ?? '#0F172A',
        w: (x.share / top) * 100 + '%',
    }));
});

// ---------- channel mix (from the items) ----------
const JOB: Record<string, string> = {
    status: 'Be seen by everyone who has your number',
    post: 'Feed posts for people who follow you',
    carousel: 'Tips and proof, swipe by swipe',
    reel: 'Short videos that reach new people',
    google_post: 'Show up when people search on Google',
    whatsapp: 'Offers to customers who said yes',
    ad: 'Paid reach around the shop',
    sms: 'Reach people not on WhatsApp',
    print: 'Counter flex and pamphlets',
};
const spanDays = computed(() => {
    if (!items.value.length) return 30;
    const a = itemDate(items.value[0]).getDate();
    const b = itemDate(items.value[items.value.length - 1]).getDate();
    return b - a + 1;
});
const chans = computed(() => {
    const by: Record<string, any> = {};
    for (const i of items.value) {
        const g = (by[i.type] ??= { n: i.type_label, type: i.type, p: 0, cr: 0, ch: new Set<string>() });
        g.p += 1;
        g.cr += i.credits || 0;
        (i.channels ?? []).forEach((c: string) => g.ch.add(CHANNEL_LABEL[c] ?? c));
    }
    const weeks = spanDays.value / 7;
    return Object.values(by)
        .sort((a: any, b: any) => b.p - a.p)
        .map((g: any) => ({
            n: g.n,
            k: typeDot(g.type),
            f: g.p >= spanDays.value * 0.9 ? 'daily' : g.p === 1 ? 'once' : Math.max(1, Math.round(g.p / weeks)) + ' / week',
            j: JOB[g.type] ?? '',
            w: [...g.ch].join(', '),
            p: g.p,
            cr: isApproved.value ? '—' : g.cr || 'free',
        }));
});
const langSplit = computed(() => {
    const by: Record<string, number> = {};
    items.value.forEach((i) => (by[i.language_label] = (by[i.language_label] ?? 0) + 1));
    const total = items.value.length || 1;
    return Object.entries(by)
        .sort((a, b) => b[1] - a[1])
        .map(([l, n]) => `${l} ${Math.round((n / total) * 100)}%`)
        .join(' · ');
});

// ---------- weeks ----------
const weeks = computed(() => {
    const m = mon.value;
    const last = new Date(m.getFullYear(), m.getMonth() + 1, 0).getDate();
    const short = monthName(m, 'short').toUpperCase();
    const bounds = [
        [1, 7],
        [8, 14],
        [15, 21],
        [22, last],
    ];
    return bounds
        .map(([a, b], w) => {
            const list = items.value.filter((i) => {
                const d = itemDate(i).getDate();
                return d >= a! && d <= b!;
            });
            const occs = [...new Set(list.filter((i) => i.occasion).map((i) => i.occasion.name))];
            const stages: Record<string, number> = {};
            list.forEach((i) => (stages[i.stage] = (stages[i.stage] ?? 0) + 1));
            const topStage = Object.entries(stages).sort((x, y) => y[1] - x[1])[0]?.[0];
            const stageName = (strategy.value.funnel ?? []).find((f: any) => f.key === topStage);
            return {
                n: 'Week ' + (w + 1),
                d: `${a} – ${b} ${short}`,
                theme: occs.length ? occs.join(', ') : stageName ? `Mostly ${stageName.label.toLowerCase()} · ${stageName.name}` : '',
                items: list.map((i, k) => ({ id: i.id, k: typeDot(i.type), t: (i.occasion?.name ?? i.type_label) + ' · ' + itemDate(i).getDate(), hook: i.hook, dl: k * 0.05 + 's' })),
            };
        })
        .filter((w) => w.items.length);
});

// ---------- budget ----------
const split = computed(() => strategy.value.wallet_split);
const wallet = computed(() =>
    split.value
        ? [
              { n: 'Meta ads', v: inr(split.value.meta_ads_paise) },
              { n: 'WhatsApp messages', v: inr(split.value.whatsapp_paise) },
              { n: 'SMS', v: inr(split.value.sms_paise) },
          ]
        : [],
);
const freeCount = computed(() => items.value.filter((i) => !i.credits).length);
const afterApprove = computed(() => left.value - (plan.value?.credits_budget ?? 0));
const activeFlows = computed(() => (workflowsReq.data.value?.data ?? []).filter((w: any) => w.active));

// ---------- re-plan ----------
const noAds = ref(false);
const fewer = ref(false);
const onlyLang = ref<string | null>(null);
const notes = ref('');
watch(
    () => plan.value?.id,
    () => {
        notes.value = brief.value.notes ?? '';
        noAds.value = false;
        fewer.value = false;
        onlyLang.value = null;
    },
    { immediate: true },
);
const briefLangs = computed<string[]>(() => brief.value.languages ?? shop.value.languages ?? []);
const asks = computed(() => {
    const list: { n: string; on: boolean; run: () => void }[] = [];
    if ((plan.value?.wallet_budget_paise ?? 0) > 0) list.push({ n: 'No ads this month', on: noAds.value, run: () => (noAds.value = !noAds.value) });
    if ((plan.value?.credits_budget ?? 0) > 10) list.push({ n: 'Use fewer credits', on: fewer.value, run: () => (fewer.value = !fewer.value) });
    if (briefLangs.value.length > 1)
        briefLangs.value.forEach((l) => list.push({ n: 'Only ' + (LANG_LABEL[l] ?? l), on: onlyLang.value === l, run: () => (onlyLang.value = onlyLang.value === l ? null : l) }));
    return list;
});
const changed = computed(() => noAds.value || fewer.value || !!onlyLang.value || notes.value.trim() !== (brief.value.notes ?? '').trim());
const replanning = ref(false);
async function replan() {
    if (!plan.value) return;
    replanning.value = true;
    try {
        const body: any = { ...brief.value, month: plan.value.month, notes: notes.value.trim() || undefined };
        if (noAds.value) body.wallet_paise = 0;
        if (fewer.value) body.credits = Math.floor((plan.value.credits_budget ?? 0) * 0.7);
        if (onlyLang.value) body.languages = [onlyLang.value];
        const res = await api<any>('plans', { method: 'POST', body });
        toast.ok(`New version v${res.data.version} is ready`);
        await navigateTo({ path: '/app/studio/plan/result', query: { id: res.data.id } });
    } catch (e: any) {
        toast.error(toApiError(e).message);
    } finally {
        replanning.value = false;
    }
}

// ---------- approve ----------
const approving = ref(false);
async function approve() {
    const p = plan.value;
    if (!p) return;
    const cost = p.credits_budget ?? 0;
    const ok = await confirm({
        title: `Approve the ${monthLong.value} plan?`,
        body: `${items.value.length} pieces get scheduled on their dates. You can still edit or skip any of them later.`,
        cost: cost ? `${num(cost)} credits · you have ${num(left.value)} left` : 'No credits needed · all pieces are free',
        confirm: 'Approve plan',
    });
    if (!ok) return;
    approving.value = true;
    try {
        const res = await api<any>(`plans/${p.id}/approve`, { method: 'POST' });
        approved.value = res.plan;
        auth.set(res.account);
        toast.ok(`Plan approved · ${res.plan.items?.length ?? items.value.length} pieces scheduled`);
    } catch (e: any) {
        const err = toApiError(e);
        if (err.code === 'insufficient_credits') {
            toast.error(`Not enough credits: this plan needs ${num(err.data?.needed)}, you have ${num(err.data?.available)}.`, {
                label: 'Top up',
                run: () => navigateTo('/app/settings?tab=billing'),
            });
        } else toast.error(err.message);
    } finally {
        approving.value = false;
    }
}
</script>

<template>
    <div class="wrap" style="max-width: 1360px">
        <LoadState :pending="pending && !plan" :error="error" :empty="!plan" :rows="6" :empty-text="id ? 'This plan was not found' : `No plan for ${monthName(monthOf(nextMonth))} yet`" empty-hint="Fill a short brief and Dikho writes the month for you. Making a plan is free." @retry="refresh()">
            <template #empty>
                <NuxtLink class="btn bp" to="/app/studio/plan">Write the brief</NuxtLink>
            </template>
            <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
                <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 440px; min-width: 0">
                    <span class="eyb"><b>PLAN MODE</b>·<span>GENERATED PLAN · {{ ver }}</span></span>
                    <h1 class="h-lg">{{ goalLabel }} · {{ range }}</h1>
                    <p class="ts muted">Written from your brief. Change anything - edit the brief, or ask for a new version on the right.</p>
                </div>
                <NuxtLink class="btn bs" :to="{ path: '/app/studio/plan', query: { from: plan.id } }">Change brief</NuxtLink>
                <button v-if="!isApproved" type="button" class="btn bp" :disabled="approving || !items.length" @click="approve">
                    <svg v-if="approving" class="i s spin" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9"></path></svg>{{ approving ? 'Approving…' : 'Approve plan' }}
                </button>
                <NuxtLink v-else class="btn bp" :to="{ path: '/app/calendar', query: { month: plan.month } }">
                    See the {{ items.length }} pieces<svg class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                </NuxtLink>
            </div>
            <div v-if="kpis.length" class="strip rise d1">
                <div v-for="k in kpis" :key="k.l"><span class="lm">{{ k.l }}</span><span class="num">{{ k.v }}</span><span class="tx muted">{{ k.s }}</span></div>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
                <div style="flex: 2.2 1 640px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                    <section v-if="whys.length" class="panel rise d2">
                        <div class="ph"><span class="eyb"><b>01</b>·<span>The idea in {{ whys.length }} lines</span></span><span class="sp"></span><span class="tag">strategy</span></div>
                        <div style="padding: 4px 18px">
                            <div v-for="w in whys" :key="w.n" class="why">
                                <span class="mt sm lt">{{ w.n }}</span>
                                <span><span class="h-sm" style="display: block">{{ w.t }}</span><span class="ts muted">{{ w.d }}</span></span>
                            </div>
                        </div>
                    </section>

                    <section class="panel rise d3">
                        <div class="ph"><span class="eyb"><b>02</b>·<span>Funnel · grahak ka safar</span></span><span class="sp"></span><span class="mono">{{ items.length }} pieces</span></div>
                        <div class="pb" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(190px, 100%), 1fr)); gap: 10px">
                            <div v-for="x in funnel" :key="x.n" class="fc">
                                <span style="display: flex; justify-content: space-between; align-items: baseline"><span class="h-sm">{{ x.n }}</span><span class="mono">{{ x.p }}</span></span>
                                <span class="tx muted">{{ x.en }} · {{ x.c }} pieces</span>
                                <div class="bar-t"><i :style="{ width: x.w, background: x.col }"></i></div>
                                <span class="ts">{{ x.fm }}</span>
                                <span v-if="x.msg" class="tx" style="color: #334155; font-style: italic">“{{ x.msg }}”</span>
                            </div>
                        </div>
                    </section>

                    <section class="panel rise d4" style="overflow: hidden">
                        <div class="ph" style="flex-wrap: wrap"><span class="eyb"><b>03</b>·<span>Channels</span></span><span class="sp"></span><span class="tx muted">{{ langSplit }}</span></div>
                        <div class="tscroll">
                            <table class="tbl">
                                <thead>
                                    <tr><th>Format</th><th>How often</th><th>Its job</th><th>Goes to</th><th style="text-align: right">Pieces</th><th style="text-align: right">Credits</th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="c in chans" :key="c.n">
                                        <td><span style="display: inline-flex; gap: 8px; align-items: center"><span :class="'ty ' + c.k"></span><b style="font-weight: 500">{{ c.n }}</b></span></td>
                                        <td class="mono">{{ c.f }}</td>
                                        <td class="muted">{{ c.j }}</td>
                                        <td class="muted">{{ c.w }}</td>
                                        <td class="mono" style="text-align: right">{{ c.p }}</td>
                                        <td class="mono" style="text-align: right">{{ c.cr }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="panel rise d5">
                        <div class="ph"><span class="eyb"><b>04</b>·<span>Week by week</span></span><span class="sp"></span><NuxtLink class="btn bg sm" :to="{ path: '/app/calendar', query: { month: plan.month } }">Open in calendar</NuxtLink></div>
                        <div v-for="w in weeks" :key="w.n" class="wk">
                            <span>
                                <span class="h-sm" style="display: block">{{ w.n }}</span>
                                <span class="mono muted" style="font-size: 11px">{{ w.d }}</span>
                                <span class="tx" style="display: block; margin-top: 4px; color: #334155">{{ w.theme }}</span>
                            </span>
                            <span style="display: flex; flex-wrap: wrap; gap: 6px; min-width: 0">
                                <NuxtLink v-for="i in w.items" :key="i.id" class="pill2" :to="{ path: '/app/studio/editor', query: { id: i.id } }" :title="i.hook" :style="{ animationDelay: i.dl }"><span :class="'ty ' + i.k"></span>{{ i.t }}</NuxtLink>
                            </span>
                        </div>
                        <p v-if="!weeks.length" class="ts muted" style="padding: 14px 18px">This plan has no pieces.</p>
                    </section>
                </div>

                <aside style="flex: 1 1 320px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                    <section class="panel rise d2">
                        <div class="ph"><span class="eyb"><b>AI</b>·<span>Isme kya badlein?</span></span></div>
                        <div v-if="isApproved" class="pb" style="display: flex; flex-direction: column; gap: 12px; align-items: flex-start">
                            <p class="ts muted">This plan is approved and its pieces are scheduled. To change one, open it from the calendar and edit or skip it.</p>
                            <NuxtLink class="btn bs sm" :to="{ path: '/app/calendar', query: { month: plan.month } }">Open in calendar</NuxtLink>
                        </div>
                        <div v-else class="pb" style="display: flex; flex-direction: column; gap: 12px">
                            <p class="ts muted">Pick a change or write a note. Dikho makes a new version (v{{ (plan.version ?? 1) + 1 }}) from the same brief; this one stays as it is.</p>
                            <div v-if="asks.length" class="chips"><button v-for="a in asks" :key="a.n" type="button" :class="'chip' + (a.on ? ' on' : '')" @click="a.run">{{ a.n }}</button></div>
                            <textarea v-model="notes" class="in" style="height: 84px; padding: 9px 11px; resize: vertical; line-height: 1.45" maxlength="1000" placeholder="Likh ke batayein - e.g. more about gift boxes" aria-label="Notes for the next version"></textarea>
                            <button type="button" class="btn bs full" :disabled="!changed || replanning" @click="replan">
                                <svg v-if="replanning" class="i s spin" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9"></path></svg>{{ replanning ? 'Making a new version…' : 'Make v' + ((plan.version ?? 1) + 1) }}
                            </button>
                        </div>
                    </section>
                    <section class="panel rise d3">
                        <div class="ph"><span class="eyb"><b>05</b>·<span>Budget</span></span><span class="sp"></span><span class="tag cr">{{ num(plan.credits_budget) }} / {{ num(isApproved ? auth.account?.credits?.total : left) }} cr</span></div>
                        <div class="pb receipt">
                            <div v-for="w in wallet" :key="w.n" class="ln"><span class="muted">{{ w.n }}</span><span>{{ w.v }}</span></div>
                            <div class="ln tot"><span>wallet set aside</span><span>{{ inr(plan.wallet_budget_paise) }}</span></div>
                            <template v-if="!isApproved">
                                <div class="ln"><span class="muted">pieces needing credits</span><span>{{ items.length - freeCount }}</span></div>
                                <div class="ln"><span class="muted">free pieces</span><span>{{ freeCount }}</span></div>
                            </template>
                            <div v-if="isApproved" class="ln"><span class="muted">credits paid on approval</span><span>{{ num(plan.credits_budget) }} cr</span></div>
                            <div v-else class="ln">
                                <span class="muted">credits left after approval</span><span :style="afterApprove < 0 ? 'color:#B91C1C' : ''">{{ num(afterApprove) }} cr</span>
                            </div>
                        </div>
                    </section>
                    <section class="panel rise d4">
                        <div class="ph"><span class="eyb"><b>06</b>·<span>Workflows running alongside</span></span></div>
                        <LoadState :pending="workflowsReq.pending.value" :error="workflowsReq.error.value" :empty="!activeFlows.length" empty-text="No workflows switched on" :rows="2" @retry="workflowsReq.refresh()">
                            <template #empty><NuxtLink class="btn bs sm" to="/app/studio/workflows">Set them up</NuxtLink></template>
                            <NuxtLink v-for="x in activeFlows" :key="x.id" class="row" to="/app/studio/workflows" style="padding: 10px 18px">
                                <span class="grow"><span class="ts" style="font-weight: 500; display: block">{{ x.name }}</span><span class="tx muted">{{ x.description || x.trigger }}</span></span>
                                <span class="st ok">on</span>
                            </NuxtLink>
                        </LoadState>
                    </section>
                </aside>
            </div>
        </LoadState>
    </div>
</template>

<style scoped>
.why{display:grid;grid-template-columns:28px minmax(0,1fr);gap:12px;padding:12px 0;border-bottom:1px dashed #E2E8F0}
.why:last-child{border-bottom:0}
.fc{display:flex;flex-direction:column;gap:8px;padding:14px;border:1px solid #E2E8F0;border-radius:12px;min-width:0}
.wk{display:grid;grid-template-columns:150px minmax(0,1fr);gap:14px;padding:12px 18px;border-bottom:1px solid #E2E8F0;align-items:start}
.wk:last-child{border-bottom:0}
@media (max-width:599px){.wk{grid-template-columns:minmax(0,1fr);gap:8px}}
.pill2{display:inline-flex;align-items:center;gap:6px;height:26px;padding:0 9px;border:1px solid #E2E8F0;border-radius:7px;background:#fff;font-size:12.5px;white-space:nowrap;animation:rise .45s var(--ease) both}
</style>
