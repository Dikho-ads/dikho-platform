<script setup lang="ts">
// Home: today at a glance from GET dashboard — ready posts, the week's chats, what needs the
// owner, and the occasions coming up.
definePageMeta({ layout: 'app', title: 'Home' });

const auth = useAuth();
const api = useApi();
const toast = useToast();
const { confirm } = useConfirm();
const { data, pending, error, refresh } = useApiData<any>('dashboard', 'dashboard');

// The time in the eyebrow: taken once on the server so the client hydrates the same text.
const nowIso = useState('home-now', () => new Date().toISOString());
onMounted(() => (nowIso.value = new Date().toISOString()));

const g = computed(() => data.value?.greeting);
const eyebrow = computed(() => (g.value ? eyebrowDate(g.value.date) : eyebrowDate(new Date())));
const place = computed(() => [g.value?.city?.toUpperCase(), time(nowIso.value)].filter(Boolean).join(' · '));

// Header: review next month's plan when one is waiting, else open the calendar.
const planNeed = computed(() => (data.value?.needs_you || []).find((n: any) => n.kind === 'plan'));
const reviewLabel = computed(() => (planNeed.value ? 'Review ' + String(planNeed.value.title).replace(/\s*plan$/i, '') : 'Open calendar'));
const reviewTo = computed(() => planNeed.value?.link || '/app/calendar');

// Credits strip + KPIs.
const credits = computed(() => auth.account?.credits);
const onTicks = computed(() => (credits.value?.total ? Math.round((credits.value.left / credits.value.total) * 50) : 0));
const ticks = computed(() => Array.from({ length: 50 }, (_, i) => ({ c: i < onTicks.value ? 'on' : '', d: (0.15 + i * 0.012).toFixed(3) + 's' })));
const KPI_LABEL: Record<string, string> = { views: 'Saw it', chats: 'Chats', calls: 'Calls', qr_scans: 'QR used' };
const kpis = computed(() =>
    (data.value?.kpis || []).map((k: any) => ({
        key: k.key,
        l: KPI_LABEL[k.key] || k.key,
        v: num(k.value),
        d: k.change == null ? null : pct(k.change),
    })),
);

// 01 · Today's ready posts. The language switch filters by the languages these posts are in.
const ready = computed<any[]>(() => data.value?.ready || []);
const langs = computed(() => [...new Set(ready.value.map((x) => x.language))]);
const lang = ref<string | null>(null);
const shown = computed(() => (lang.value ? ready.value.filter((x) => x.language === lang.value) : ready.value));
const allFree = computed(() => ready.value.length > 0 && ready.value.every((x) => !x.credits));
const busy = ref<number | null>(null);

const channelsText = (item: any) => (item.channels || []).map((c: string) => CHANNEL_LABEL[c] || c).join(', ');

function replaceItem(item: any) {
    // useAsyncData data is a shallow ref: replace the object so the page re-renders.
    if (!data.value) return;
    const posted = (list: any[]) => list.filter((x) => x.status === 'posted').length;
    const ready = data.value.ready.map((x: any) => (x.id === item.id ? item : x));
    const delta = posted(ready) - posted(data.value.ready);
    data.value = { ...data.value, ready, posted_today: Math.max(0, (data.value.posted_today || 0) + delta) };
}

function moneyError(e: any) {
    if (e.code === 'insufficient_credits') {
        toast.error(e.message, { label: 'Top up', run: () => navigateTo('/app/settings?tab=billing') });
    } else {
        toast.error(e.message);
    }
}

async function postNow(item: any) {
    if (item.credits > 0) {
        const left = credits.value?.left ?? 0;
        const ok = await confirm({
            title: `Post “${item.title}” now?`,
            body: `Goes to ${channelsText(item)}.`,
            cost: `${item.credits} credits · ${left - item.credits} left after`,
            confirm: 'Post now',
        });
        if (!ok) return;
    }
    busy.value = item.id;
    try {
        const res = await api<any>(`content/${item.id}/publish`, { method: 'POST', body: { channels: item.channels } });
        auth.set(res.account);
        replaceItem(res.item);
        toast.ok(`${item.title} → ${channelsText(res.item)}`, { label: 'Undo', run: () => undo(res.item) });
    } catch (e: any) {
        moneyError(e);
    } finally {
        busy.value = null;
    }
}

async function undo(item: any) {
    try {
        const res = await api<any>(`content/${item.id}/unpublish`, { method: 'POST' });
        auth.set(res.account);
        replaceItem(res.item);
        toast.info('Undone · back in today’s ready posts');
    } catch (e: any) {
        toast.error(e.message);
    }
}

// 02 · Chats per day.
const days = computed(() => {
    const rows = data.value?.chats_per_day || [];
    const max = Math.max(0, ...rows.map((r: any) => r.chats));
    return rows.map((r: any, i: number) => {
        const top = max > 0 && r.chats === max;
        return {
            key: r.date,
            n: weekday(r.date, 'narrow'),
            title: `${day(r.date)} · ${num(r.chats)} chats · ${num(r.views)} saw it`,
            v: num(r.chats),
            h: max ? Math.max(2, Math.round((r.chats / max) * 112)) + 'px' : '2px',
            c: top ? '#F2A007' : '#0F172A',
            tc: top ? '#0F172A' : '#64748B',
            dl: 0.3 + i * 0.06 + 's',
        };
    });
});
const noChats = computed(() => (data.value?.chats_per_day || []).every((r: any) => !r.chats));
const ins = computed(() => data.value?.insights || {});

// 03 · Needs you.
const needs = computed<any[]>(() => data.value?.needs_you || []);
const NEED_DOT: Record<string, string> = { plan: 'camp', batch: 'video', winback: 'post', photos: 'free' };
const batchShown = ref(false);
const approving = ref(false);

async function approveBatch(n: any) {
    const ids = (n.items || []).map((x: any) => x.id);
    if (!ids.length) return;
    const cost = n.credits || 0;
    const left = credits.value?.left ?? 0;
    const ok = await confirm({
        title: `Approve ${ids.length} pieces?`,
        body: 'Each one goes out on its own day. You can still edit or skip any of them before then.',
        cost: cost ? `${cost} credits · ${left - cost} left after` : 'No credits needed',
        confirm: `Approve ${ids.length}`,
    });
    if (!ok) return;
    approving.value = true;
    try {
        const res = await api<any>('content/approve-batch', { method: 'POST', body: { ids } });
        auth.set(res.account);
        toast.ok(`${res.items?.length ?? ids.length} pieces approved for this week`);
        batchShown.value = false;
        await refresh();
    } catch (e: any) {
        moneyError(e);
    } finally {
        approving.value = false;
    }
}

const batchDate = (v: string | null) => {
    if (!v) return '—';
    const x = new Date(v);
    return (weekday(x) + ' ' + x.getDate()).toUpperCase();
};

// 04 · Coming up.
const KIND_DOT: Record<string, string> = { festival: 'video', local: 'camp', season: 'camp', national: 'free', moment: 'post' };
const KIND_LABEL: Record<string, string> = { festival: 'Festival', local: 'Local event', season: 'Season', national: 'National day', moment: 'Moment' };
const occ = computed(() =>
    (data.value?.coming_up || []).slice(0, 6).map((o: any) => {
        const st = o.planned_items > 0 ? ['In plan', 'st ok'] : o.relevance >= 0.7 ? ['Suggested', 'st acc'] : ['Free post', 'st'];
        const when = o.days_away <= 0 ? `on till ${day(o.ends_on)}` : relative(o.starts_on);
        const planned = o.planned_items > 0 ? ` · ${o.planned_items} planned` : '';
        return {
            id: o.id,
            d: o.days_away <= 0 ? 'NOW' : day(o.starts_on).toUpperCase(),
            k: KIND_DOT[o.kind] || 'post',
            n: o.name,
            s: `${KIND_LABEL[o.kind] || 'Occasion'} · ${when}${planned}`,
            st: st[0],
            sc: st[1],
        };
    }),
);
</script>

<template>
    <div class="wrap">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 16px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 400px; min-width: 0">
                <span class="eyb"><b>{{ eyebrow }}</b>·<span>{{ place }}</span></span>
                <h1 class="h-lg">Namaste, {{ g?.name || auth.user?.first_name }} ji</h1>
                <p v-if="g?.summary" class="ts muted">{{ g.summary }}</p>
                <p v-else-if="pending" class="ts muted">Getting today ready…</p>
            </div>
            <NuxtLink class="btn bs" :to="reviewTo">{{ reviewLabel }}</NuxtLink>
            <NuxtLink class="btn bp" to="/app/create"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>Create</NuxtLink>
        </div>

        <LoadState v-if="error" :error="error" @retry="refresh" />

        <template v-else>
            <div class="strip rise d1">
                <div>
                    <span class="lm">Credits left</span>
                    <span><span class="num">{{ num(credits?.left ?? 0) }}</span><span class="muted"> / {{ num(credits?.total ?? 0) }}</span></span>
                    <div class="ticks sm"><i v-for="(k, i) in ticks" :key="i" :class="k.c" :style="{ animationDelay: k.d }"></i></div>
                </div>
                <template v-if="kpis.length">
                    <div v-for="k in kpis" :key="k.key">
                        <span class="lm">{{ k.l }}</span>
                        <span class="num">{{ k.v }}</span>
                        <span v-if="k.d" class="tx muted"><b style="color: #0f172a; font-weight: 500">{{ k.d }}</b> vs last week</span>
                        <span v-else class="tx muted">no data last week</span>
                    </div>
                </template>
                <template v-else-if="pending">
                    <div v-for="i in 4" :key="i"><span class="lm">&nbsp;</span><span class="hsk" style="width: 60%; height: 22px"></span><span class="hsk" style="width: 80%"></span></div>
                </template>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
                <div style="flex: 2 1 560px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                    <section class="panel rise d2">
                        <div class="ph">
                            <span class="eyb"><b>01</b>·<span>Today’s ready posts</span></span><span v-if="allFree" class="tag dk">FREE</span><span class="sp"></span>
                            <div v-if="langs.length > 1" class="seg" role="group" aria-label="Show posts in">
                                <button type="button" :class="lang === null ? 'on' : ''" @click="lang = null">All</button>
                                <button v-for="c in langs" :key="c" type="button" :class="lang === c ? 'on' : ''" @click="lang = c">{{ LANG_LABEL[c] || c }}</button>
                            </div>
                            <span v-else-if="langs.length === 1" class="tag">{{ LANG_LABEL[langs[0]] || langs[0] }}</span>
                        </div>
                        <LoadState :pending="pending && !data" :empty="!ready.length" :rows="4" empty-text="Nothing is waiting for today" :empty-hint="data?.posted_today ? `${data.posted_today} posted today already. Make one more, or line up tomorrow in the calendar.` : 'Make a post now, or let Auto mode line up the week.'">
                            <template #empty>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap">
                                    <NuxtLink class="btn bp sm" to="/app/create">Create a post</NuxtLink>
                                    <NuxtLink class="btn bs sm" to="/app/studio/auto">Open Auto mode</NuxtLink>
                                </div>
                            </template>
                            <div class="pb ready-posts" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(160px, 100%), 1fr)); gap: 14px">
                                <div v-for="x in shown" :key="x.id" class="pc hov">
                                    <PostCard :item="x" style="font-size: 9.4px" />
                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px">
                                        <span class="h-sm" style="min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap" :title="x.title">{{ x.title }}</span>
                                        <span class="tag" style="flex-shrink: 0">trust {{ x.trust_score }}</span>
                                    </div>
                                    <span v-if="x.why" class="tx muted">{{ x.why }}</span>
                                    <div style="display: flex; gap: 6px; margin-top: auto">
                                        <button v-if="x.status !== 'posted'" type="button" class="btn bp sm" style="flex: 1" :disabled="busy === x.id" @click="postNow(x)">{{ busy === x.id ? 'Posting…' : 'Post now' }}</button>
                                        <span v-else class="btn bs sm" style="flex: 1; cursor: default"><span class="st ok">Posted · {{ x.channels?.length || 0 }} {{ x.channels?.length === 1 ? 'channel' : 'channels' }}</span></span>
                                        <NuxtLink class="btn bs sm" :to="`/app/studio/editor?id=${x.id}`">Edit</NuxtLink>
                                    </div>
                                </div>
                            </div>
                        </LoadState>
                    </section>

                    <section class="panel rise d3">
                        <div class="ph">
                            <span class="eyb"><b>02</b>·<span>This week · chats per day</span></span><span class="sp"></span>
                            <NuxtLink class="btn bg sm" to="/app/results">All results<svg class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></NuxtLink>
                        </div>
                        <LoadState :pending="pending && !data" :rows="4">
                            <div class="pb" style="display: flex; flex-wrap: wrap; gap: 28px; align-items: flex-end">
                                <div style="flex: 1 1 300px; min-width: 0; display: flex; flex-direction: column; gap: 8px">
                                    <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 10px; align-items: end; height: 150px">
                                        <div v-for="d in days" :key="d.key" :title="d.title" style="display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end">
                                            <span class="mono" :style="{ color: d.tc }">{{ d.v }}</span>
                                            <span class="gy" :style="{ width: '100%', height: d.h, background: d.c, borderRadius: '4px 4px 0 0', animationDelay: d.dl }"></span>
                                            <span class="mono muted">{{ d.n }}</span>
                                        </div>
                                    </div>
                                    <span v-if="noChats" class="tx muted">No chats counted this week yet. They show up here as people message you from your posts.</span>
                                </div>
                                <div class="receipt" style="flex: 1 1 220px; min-width: 0">
                                    <div class="ln" style="border-bottom: 1px solid #e2e8f0"><span class="muted">best day</span><span>{{ ins.best_day || '—' }}</span></div>
                                    <div class="ln" style="border-bottom: 1px solid #e2e8f0"><span class="muted">best time</span><span>{{ ins.best_time || '—' }}</span></div>
                                    <div class="ln" style="border-bottom: 1px solid #e2e8f0"><span class="muted">best hook</span><span>{{ ins.best_hook ? `“${ins.best_hook}”` : '—' }}</span></div>
                                    <div class="ln"><span class="muted">cost per chat</span><span>{{ ins.cost_per_chat_paise ? inr(ins.cost_per_chat_paise) + ' · ads' : '—' }}</span></div>
                                </div>
                            </div>
                        </LoadState>
                    </section>
                </div>

                <div style="flex: 1 1 320px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                    <section class="panel rise d3">
                        <div class="ph"><span class="eyb"><b>03</b>·<span>Needs you</span></span><span class="sp"></span><span v-if="data" class="mono muted">{{ needs.length }} open</span></div>
                        <LoadState :pending="pending && !data" :empty="!needs.length" empty-text="All clear" empty-hint="Nothing needs you right now. New plans, batches and win-back ideas show up here.">
                            <template v-for="(n, i) in needs" :key="n.kind + i">
                                <template v-if="n.kind === 'batch'">
                                    <button type="button" class="row" :aria-expanded="batchShown" @click="batchShown = !batchShown">
                                        <span class="ty video"></span><span class="grow"><span class="h-sm" style="display: block">{{ n.title }}</span><span class="tx muted">{{ n.detail }}</span></span>
                                        <span v-if="n.badge" class="st warn">{{ n.badge }}</span>
                                        <svg v-if="batchShown" class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 15l6-6 6 6"></path></svg>
                                        <svg v-else class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg>
                                    </button>
                                    <div v-if="batchShown" class="fade" style="padding: 4px 18px 14px; border-bottom: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 2px">
                                        <NuxtLink v-for="b in n.items || []" :key="b.id" :to="`/app/studio/editor?id=${b.id}`" class="bt-row">
                                            <span class="mono muted">{{ batchDate(b.scheduled_at) }}</span><span :class="['ty', typeDot(b.type)]"></span><span class="ts" style="min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap">{{ b.title }}</span><span class="mono">{{ b.credits }} cr</span>
                                        </NuxtLink>
                                        <button v-if="n.items?.length" type="button" class="btn bp sm" style="margin-top: 8px; align-self: flex-start" :disabled="approving" @click="approveBatch(n)">
                                            {{ approving ? 'Approving…' : `Approve ${n.items.length} · ${n.credits || 0} credits` }}
                                        </button>
                                    </div>
                                </template>
                                <NuxtLink v-else class="row" :to="n.link || '/app'">
                                    <span :class="['ty', NEED_DOT[n.kind] || 'post']"></span><span class="grow"><span class="h-sm" style="display: block">{{ n.title }}</span><span class="tx muted">{{ n.detail }}</span></span>
                                    <span v-if="n.badge" class="st warn">{{ n.badge }}</span>
                                    <svg v-else class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"></path></svg>
                                </NuxtLink>
                            </template>
                        </LoadState>
                    </section>

                    <section class="panel rise d4">
                        <div class="ph"><span class="eyb"><b>04</b>·<span>Coming up</span></span><span class="sp"></span><NuxtLink class="btn bg sm" to="/app/occasions">Calendar</NuxtLink></div>
                        <LoadState :pending="pending && !data" :empty="!occ.length" empty-text="No occasions in the next few months" empty-hint="Add your own shop days on the Occasions page.">
                            <NuxtLink v-for="o in occ" :key="o.id" class="tl" to="/app/occasions">
                                <span class="mono muted">{{ o.d }}</span><span :class="['ty', o.k]"></span>
                                <span style="min-width: 0"><span class="ts" style="font-weight: 500; display: block">{{ o.n }}</span><span class="tx muted">{{ o.s }}</span></span>
                                <span :class="o.sc">{{ o.st }}</span>
                            </NuxtLink>
                        </LoadState>
                    </section>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.pc {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    min-width: 0;
}
.tl {
    display: grid;
    grid-template-columns: 64px 14px minmax(0, 1fr) auto;
    gap: 10px;
    align-items: center;
    padding: 11px 18px;
    text-decoration: none;
    color: #0f172a;
    transition: background 0.15s;
}
.tl:hover {
    background: #f8fafc;
}
.bt-row {
    display: grid;
    grid-template-columns: 58px 12px minmax(0, 1fr) auto;
    gap: 8px;
    align-items: center;
    padding: 6px 0;
    color: #0f172a;
    text-decoration: none;
}
.bt-row:hover .ts {
    text-decoration: underline;
}
.hsk {
    display: block;
    height: 12px;
    border-radius: 6px;
    background: #eef2f7;
    animation: breathe 1.6s ease-in-out infinite;
}

/* Phones: ready posts scroll sideways as in the Mobile v2 home. */
@media (max-width: 599px) {
    .ready-posts {
        display: flex !important;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        scrollbar-width: none;
    }
    .ready-posts::-webkit-scrollbar {
        display: none;
    }
    .ready-posts > * {
        flex: 0 0 68%;
        scroll-snap-align: start;
    }
}
</style>
