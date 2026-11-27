<script setup lang="ts">
// The auto queue: pieces Dikho made on its own from today on, grouped by week and by funnel stage.
// Approve (one, or a whole week), skip, or open any piece in the editor.
definePageMeta({ layout: 'app', title: 'Auto mode' });

const auth = useAuth();
const api = useApi();
const toast = useToast();
const { confirm } = useConfirm();

const today = isoDate();
const { data, pending, error, refresh } = useApiData<{ data: any[] }>('auto-queue', 'content', {
    query: () => ({ source: 'auto', status: 'draft,ready,approved,scheduled', from: today, limit: 60 }),
});
const queue = computed<any[]>(() => (data.value?.data ?? []).filter((i) => i.status !== 'skipped'));
const shop = computed(() => auth.shop);

const KIND: Record<string, string> = { post: 'visual', status: 'visual', carousel: 'visual', google_post: 'visual', print: 'visual', reel: 'video', whatsapp: 'text', sms: 'text', review_reply: 'text', ad: 'ad' };
const kindOf = (it: any) => KIND[it.type] ?? 'visual';
const F: [string, (it: any) => boolean][] = [
    ['All', () => true],
    ['Visual', (it) => kindOf(it) === 'visual'],
    ['Video', (it) => kindOf(it) === 'video'],
    ['Text', (it) => kindOf(it) === 'text'],
    ['Ads', (it) => kindOf(it) === 'ad'],
];
const ST: [string, string, string, string][] = [
    ['awareness', 'Pehchaan', 'Awareness', 'New people learn the shop exists'],
    ['trust', 'Bharosa', 'Trust', 'Proof, tips and the shop’s story'],
    ['buying', 'Kharidari', 'Buying', 'Offers with a reason to come now'],
    ['return', 'Wapsi', 'Coming back', 'Check-ins, wishes, reviews'],
];
const ANGLE: Record<string, string> = { offer: 'Bachat', trust: 'Bharosa', local: 'Mohalla', newness: 'Naya', festival: 'Tyohaar', tip: 'Jaankari' };
const THEME: Record<string, string> = { brand: '', light: 'w', dark: 'k', festive: 'y' };
const RATIO: Record<string, string> = { '9:16': 'r916', '1:1': 'r11' };
const STATUS: Record<string, string> = { draft: 'Draft', ready: 'Ready', approved: 'Approved', scheduled: 'Scheduled' };
const waiting = (it: any) => it.status === 'draft' || it.status === 'ready';

const filter = ref(0);
const filters = computed(() => F.map(([n, f], i) => ({ n, c: queue.value.filter(f).length, i })));
const visible = computed(() => queue.value.filter(F[filter.value]![1]));

const weekStart = (iso: string) => {
    const d = new Date(iso.slice(0, 10) + 'T00:00:00');
    d.setDate(d.getDate() - ((d.getDay() + 6) % 7));
    return isoDate(d);
};
const thisWeek = weekStart(today);
const nextWeek = (() => {
    const d = new Date(thisWeek + 'T00:00:00');
    d.setDate(d.getDate() + 7);
    return isoDate(d);
})();
const weekName = (ws: string) => {
    const end = new Date(ws + 'T00:00:00');
    end.setDate(end.getDate() + 6);
    const range = `${day(ws)} – ${day(end)}`;
    return ws === thisWeek ? { n: 'This week', range } : ws === nextWeek ? { n: 'Next week', range } : { n: 'Week of ' + day(ws), range };
};

const needOf = (it: any) => {
    if (it.trust_score == null || it.trust_score >= 80) return '';
    const bad = (it.trust_checks ?? []).find((c: any) => !c.ok);
    return bad ? `Check: ${bad.label.toLowerCase()}` : `Trust ${it.trust_score}`;
};
const when = (it: any) => (it.scheduled_at ? `${eyebrowDate(it.scheduled_at)} · ${time(it.scheduled_at)}` : 'NOT SCHEDULED');
const card = (it: any) => ({
    ...it,
    fmt: (it.type_label || it.type).toUpperCase(),
    ty: it.credits ? typeDot(it.type) : 'free',
    k: kindOf(it),
    when: when(it),
    pc: ['pst', RATIO[it.ratio] ?? 'r45', THEME[it.theme] ?? ''].join(' '),
    pillar: ANGLE[it.angle] ?? it.angle,
    ch: (it.channels ?? []).map((c: string) => CHANNEL_LABEL[c] ?? c).join(' + '),
    isOk: !waiting(it),
    need: needOf(it),
    status: STATUS[it.status] ?? it.status,
    cr: it.credits ? it.credits + ' cr' : 'free',
});

// Always show the first three stages; "Coming back" only when it has pieces.
const key4 = (k: string) => k !== 'return';
const weeks = computed(() => {
    const map = new Map<string, any[]>();
    for (const it of visible.value) {
        const ws = it.scheduled_at ? weekStart(it.scheduled_at) : thisWeek;
        if (!map.has(ws)) map.set(ws, []);
        map.get(ws)!.push(it);
    }
    let idx = 0;
    return [...map.entries()].map(([ws, list]) => {
        const drafts = list.filter(waiting);
        return {
            ws,
            ...weekName(ws),
            count: list.length,
            drafts,
            credits: drafts.reduce((a, x) => a + (x.credits || 0), 0),
            cols: ST.map(([key, n, en, d]) => {
                const cards = list.filter((x) => (x.stage || 'awareness') === key);
                return { key, n, en, d, count: cards.length, cards: cards.map((x) => ({ ...card(x), dl: idx++ * 0.04 + 's' })) };
            }).filter((col) => col.count || key4(col.key)),
        };
    });
});

const days = computed(() => {
    const last = queue.value.reduce((m, x) => (x.scheduled_at && x.scheduled_at.slice(0, 10) > m ? x.scheduled_at.slice(0, 10) : m), today);
    return Math.max(1, Math.round((new Date(last + 'T00:00:00').getTime() - new Date(today + 'T00:00:00').getTime()) / 86400000) + 1);
});
const allDrafts = computed(() => visible.value.filter(waiting));
const allCredits = computed(() => allDrafts.value.reduce((a, x) => a + (x.credits || 0), 0));
const approveTxt = computed(() => (allDrafts.value.length ? `Approve ${allDrafts.value.length} waiting` : 'All approved'));
const left = computed(() => auth.account?.credits?.left ?? 0);

const GROUPS: [string, string[]][] = [['Posts', ['post', 'carousel', 'google_post', 'print']], ['Status', ['status']], ['Reels', ['reel']], ['WhatsApp + SMS', ['whatsapp', 'sms']], ['Ads', ['ad']], ['Replies', ['review_reply']]];
const sum = computed(() => [{ l: 'Pieces', v: queue.value.length }, ...GROUPS.map(([l, t]) => ({ l, v: queue.value.filter((x) => t.includes(x.type)).length })).filter((g) => g.v)]);

const occNames = computed(() => [...new Set(queue.value.map((x) => x.occasion?.name).filter(Boolean))]);
const SRC = computed(() => {
    const s = shop.value ?? {};
    return [
        { n: 'Shop profile', d: [s.name, s.area, s.city].filter(Boolean).join(' · '), to: '/app/settings' },
        { n: 'Languages', d: (s.languages ?? []).map((l: string) => LANG_LABEL[l] ?? l).join(', ') || 'Not set', to: '/app/settings' },
        { n: 'Who buys', d: (s.audience?.types ?? []).join(', ') || 'Not set', to: '/app/settings' },
        { n: 'Brand', d: s.brand?.tagline || 'No tagline yet', to: '/app/settings' },
        { n: `${s.city || 'Local'} calendar`, d: occNames.value.length ? occNames.value.slice(0, 3).join(', ') : 'No occasions in these days', to: '/app/occasions' },
    ];
});

const busy = ref<number | string | null>(null);
function replace(items: any[]) {
    const fresh = new Map(items.map((i) => [i.id, i]));
    if (data.value) data.value = { ...data.value, data: data.value.data.map((i) => fresh.get(i.id) ?? i) };
}

async function approveIds(list: any[], label: string, key: string) {
    if (!list.length || busy.value) return;
    const cr = list.reduce((a, x) => a + (x.credits || 0), 0);
    const ok = await confirm({
        title: `Approve ${list.length} piece${list.length === 1 ? '' : 's'}${label ? ' for ' + label : ''}?`,
        body: 'Each goes out on its day. You can still edit or skip it before then.',
        cost: cr ? `${cr} credits · you have ${left.value}` : 'Free · no credits',
        confirm: 'Approve',
    });
    if (!ok) return;
    busy.value = key;
    try {
        const res = await api<{ items: any[]; account: any }>('content/approve-batch', { method: 'POST', body: { ids: list.map((x) => x.id) } });
        auth.set(res.account);
        replace(res.items);
        toast.ok(`${res.items.length} approved · they’ll go out on their days`);
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        busy.value = null;
    }
}

async function approveOne(it: any) {
    if (!waiting(it) || busy.value) return;
    if (it.credits) {
        const ok = await confirm({ title: `Approve “${it.hook || it.title}”?`, body: `Goes out ${it.scheduled_at ? relative(it.scheduled_at) : 'when you share it'}.`, cost: `${it.credits} credits · you have ${left.value}`, confirm: 'Approve' });
        if (!ok) return;
    }
    busy.value = it.id;
    try {
        const res = await api<{ item: any; account: any }>(`content/${it.id}/approve`, { method: 'POST' });
        auth.set(res.account);
        replace([res.item]);
        toast.ok(res.item.status === 'scheduled' ? `Scheduled for ${eyebrowDate(res.item.scheduled_at)}` : 'Approved');
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        busy.value = null;
    }
}

async function skip(it: any) {
    if (busy.value) return;
    busy.value = it.id;
    try {
        const res = await api<{ item: any; account: any }>(`content/${it.id}/skip`, { method: 'POST' });
        auth.set(res.account);
        replace([res.item]);
        toast.info('Skipped · it won’t go out');
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        busy.value = null;
    }
}
</script>

<template>
    <div class="wrap" style="max-width: 1400px">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 420px">
                <span class="eyb"><b>AUTO MODE</b>·<span>अपने आप · {{ queue.length }} PIECE{{ queue.length === 1 ? '' : 'S' }} FROM TODAY</span></span>
                <h1 class="h-lg">Next {{ days }} day{{ days === 1 ? '' : 's' }}, made for {{ shop?.name }}</h1>
                <p class="ts muted">Built from your shop info and {{ shop?.city || 'your city' }}’s calendar. Approve what you like — edit or skip the rest. Nothing goes out until you approve it.</p>
            </div>
            <div class="seg"><button type="button" class="on">Auto</button><NuxtLink to="/app/studio/plan">Plan</NuxtLink></div>
            <NuxtLink class="btn bs" to="/app/create"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>Make one</NuxtLink>
            <button type="button" class="btn bp" :disabled="!allDrafts.length || !!busy" @click="approveIds(allDrafts, '', 'all')">{{ busy === 'all' ? 'Approving…' : approveTxt }}{{ allDrafts.length && allCredits ? ` · ${allCredits} cr` : '' }}</button>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
            <aside class="panel rise d1" style="flex: 1 1 270px; max-width: 320px; min-width: 0">
                <div class="ph"><span class="eyb"><b>01</b>·<span>What it’s made from</span></span></div>
                <div class="pb" style="display: flex; flex-direction: column; gap: 18px">
                    <div style="display: flex; flex-direction: column"><span class="lm" style="margin-bottom: 4px">Made from</span>
                        <NuxtLink v-for="s in SRC" :key="s.n" :to="s.to" class="tg"><span class="grow" style="min-width: 0"><span class="ts" style="font-weight: 500; display: block">{{ s.n }}</span><span class="tx muted" style="overflow-wrap: anywhere">{{ s.d }}</span></span><svg class="i s" viewBox="0 0 24 24" aria-hidden="true" style="color: #94A3B8; flex-shrink: 0"><path d="M9 6l6 6-6 6"></path></svg></NuxtLink></div>
                    <div class="fld"><span class="lm">Approval</span><div class="opt on" style="padding: 9px 11px; cursor: default"><span class="rd"></span><span class="ts">I review everything</span></div><span class="hint">Each piece is charged once, when you approve it. Skipping gives the credits back.</span></div>
                    <div class="fld"><span class="lm">Credits</span><span class="ts"><b>{{ num(left) }}</b> <span class="muted">of {{ num(auth.account?.credits?.total) }} left · resets {{ auth.account?.credits ? day(auth.account.credits.resets_on) : '' }}</span></span></div>
                </div>
            </aside>
            <section style="flex: 4 1 640px; min-width: 0; display: flex; flex-direction: column; gap: 14px">
                <div class="strip rise d2"><div v-for="k in sum" :key="k.l" style="padding: 12px 16px; gap: 4px"><span class="lm">{{ k.l }}</span><span class="num" style="font-size: 24px">{{ num(k.v) }}</span></div></div>
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px"><div class="tabs" style="border-bottom: 0; max-width: 100%; overflow-x: auto"><button v-for="f in filters" :key="f.n" type="button" :class="filter === f.i ? 'on' : ''" @click="filter = f.i">{{ f.n }}<span class="n">{{ f.c }}</span></button></div><span class="sp"></span><span class="tx muted">To approve <b style="color: #0F172A">{{ allCredits }}</b> cr of {{ num(left) }} left · wallet {{ inr(auth.account?.wallet_paise) }}</span></div>
                <LoadState :pending="pending" :error="error" :empty="!visible.length" :rows="6" :empty-text="queue.length ? 'Nothing of this kind in the queue' : 'The auto queue is empty'" :empty-hint="queue.length ? 'Pick another tab.' : 'New pieces appear here as Dikho makes them. You can make one yourself meanwhile.'" @retry="refresh">
                    <template #empty><NuxtLink v-if="!queue.length" class="btn bp sm" to="/app/create">Make a post</NuxtLink></template>
                    <div v-for="w in weeks" :key="w.ws" class="wk">
                        <div class="wkh"><span class="h-md">{{ w.n }}</span><span class="tx muted">{{ w.range }} · {{ w.count }} piece{{ w.count === 1 ? '' : 's' }}</span><span class="sp"></span>
                            <button v-if="w.drafts.length" type="button" class="btn bs sm" :disabled="!!busy" @click="approveIds(w.drafts, w.n.toLowerCase(), w.ws)">{{ busy === w.ws ? 'Approving…' : `Approve ${w.drafts.length} · ${w.credits} cr` }}</button>
                            <span v-else class="st ok">All approved</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(230px, 100%), 1fr)); gap: 14px; align-items: start">
                            <div v-for="c in w.cols" :key="c.key" class="col">
                                <div class="colh"><span style="display: flex; justify-content: space-between; align-items: baseline"><span class="h-sm">{{ c.n }} <span class="muted" style="font-weight: 400">· {{ c.en }}</span></span><span class="mono">{{ c.count }}</span></span><span class="tx muted">{{ c.d }}</span></div>
                                <p v-if="!c.cards.length" class="tx muted" style="padding: 4px 14px">Nothing this week.</p>
                                <div v-for="k in c.cards" :key="k.id" :class="'cx' + (k.isOk ? ' ok' : '')" :style="`animation-delay: ${k.dl}`">
                                    <div style="display: flex; align-items: center; gap: 7px"><span :class="'ty ' + k.ty"></span><span class="mono" style="font-size: 11px">{{ k.fmt }}</span><span class="sp"></span><span class="mono muted" style="font-size: 10.5px; white-space: nowrap">{{ k.when }}</span></div>
                                    <div v-if="k.k !== 'text'" style="display: flex; gap: 10px; align-items: flex-start"><div :class="k.pc" style="width: 54px; flex-shrink: 0; font-size: 3.3px"><div class="pad" style="flex: 1; justify-content: flex-end"><span class="hk" :lang="k.language">{{ k.hook }}</span></div><div class="ft"><b>{{ shop?.short_name }}</b></div></div><p class="ts" :lang="k.language" style="font-weight: 500; line-height: 19px; min-width: 0; overflow-wrap: anywhere">{{ k.hook }}</p></div>
                                    <p v-else class="bubx" :lang="k.language">{{ k.caption || k.hook }}</p>
                                    <div style="display: flex; flex-wrap: wrap; gap: 5px"><span class="tag">{{ k.language_label }}</span><span class="tag ghost">{{ k.pillar }}</span><span v-if="k.ch" class="tag ghost">{{ k.ch }}</span><span v-if="k.occasion" class="tag acc">{{ k.occasion.name }}</span></div>
                                    <span v-if="k.need && !k.isOk" class="st warn" style="font-size: 12px">{{ k.need }}</span>
                                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px dashed #E2E8F0; padding-top: 9px">
                                        <button type="button" :class="'ck' + (k.isOk ? ' on' : '')" :aria-label="k.isOk ? 'Approved' : 'Approve'" :disabled="k.isOk || !!busy" style="width: 20px; height: 20px" @click="approveOne(k)"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg></button>
                                        <span class="tx" :style="`color: ${k.isOk ? '#0F172A' : '#64748B'}`">{{ busy === k.id ? 'Saving…' : k.status }} · {{ k.cr }}</span><span class="sp"></span>
                                        <button type="button" class="btn bg ib sm" aria-label="Skip this piece" title="Skip" :disabled="!!busy" @click="skip(k)"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>
                                        <NuxtLink class="btn bs sm" :to="{ path: '/app/studio/editor', query: { id: k.id } }" title="Edit or rewrite">Edit</NuxtLink>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </LoadState>
            </section>
        </div>
    </div>
</template>

<style scoped>
.wk{display:flex;flex-direction:column;gap:12px}.wk+.wk{margin-top:10px;padding-top:18px;border-top:1px solid #E2E8F0}.wkh{display:flex;flex-wrap:wrap;align-items:center;gap:6px 12px}
.col{display:flex;flex-direction:column;gap:10px;min-width:0}.colh{display:flex;flex-direction:column;gap:2px;padding:12px 14px;border:1px solid #E2E8F0;border-radius:12px;background:#F8FAFC}.cx{display:flex;flex-direction:column;gap:10px;padding:12px;border:1px solid #E2E8F0;border-radius:12px;background:#fff;transition:border-color .2s,transform .25s var(--ease),box-shadow .2s;animation:rise .55s var(--ease) both}.cx:hover{border-color:#CBD5E1;transform:translateY(-2px)}.cx.ok{border-color:#0F172A}.bubx{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:3px 12px 12px 12px;padding:8px 10px;font-size:12.5px;line-height:18px;color:#334155;white-space:pre-line;overflow-wrap:anywhere;display:-webkit-box;-webkit-line-clamp:6;-webkit-box-orient:vertical;overflow:hidden}.tg{display:flex;align-items:center;gap:10px;padding:7px 0;text-decoration:none;color:inherit}
</style>
