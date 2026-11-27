<script setup lang="ts">
// Content Studio overview: what the AI knows about the shop (business brain), this month's mix, the two
// modes with live counts, every format with its credit price, and what is running.
definePageMeta({ layout: 'app', title: 'Content Studio' });

const auth = useAuth();
const { data, pending, error, refresh } = useApiData<any>('studio', 'studio');

const pad = (n: number) => String(n).padStart(2, '0');
const now = new Date();
const thisMonth = `${now.getFullYear()}-${pad(now.getMonth() + 1)}`;
const nextMonth = (() => {
    const d = new Date(now.getFullYear(), now.getMonth() + 1, 1);
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;
})();
// Latest plan: next month's if there is one, else this month's.
const api = useApi();
const planData = useAsyncData('studio-plan-latest', async () => {
    const [next, cur] = await Promise.all([api<{ plan: any }>(`plans/${nextMonth}`), api<{ plan: any }>(`plans/${thisMonth}`)]);
    return next.plan ?? cur.plan ?? null;
});
const plan = computed(() => planData.data.value ?? null);

const shop = computed(() => auth.shop);
const account = computed(() => auth.account);
const counts = computed(() => data.value?.counts ?? {});
const month = computed(() => data.value?.month ?? null);

const slug = (s: string) => String(s || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
const shopUrl = computed(() => `dikho.in/${slug(shop.value?.city)}/${shop.value?.slug || slug(shop.value?.name)}`);

const ic: Record<string, string> = {
    post: 'M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zM4 16l5-5 4 4 2-2 5 5',
    status: 'M8 3h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zM10 17h4',
    carousel: 'M7 5h10v14H7zM3 7v10M21 7v10',
    reel: 'M6 5h12a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3zM10 9.5v5l4.5-2.5z',
    whatsapp: 'M4 20l1.3-3.9A8 8 0 1 1 8 19z',
    sms: 'M4 5h16v11H8l-4 4zM8 9h8M8 12h5',
    google_post: 'M4 9.5V20h16V9.5M3 4h18l-1.5 5.5a2.6 2.6 0 0 1-5 0 2.6 2.6 0 0 1-5 0 2.6 2.6 0 0 1-5 0z',
    ad: 'M3 10v4a1 1 0 0 0 1 1h3l6 4V5L7 9H4a1 1 0 0 0-1 1zM17 8.5a5 5 0 0 1 0 7',
    print: 'M7 9V3h10v6M7 17H4a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-3M7 14h10v7H7z',
    review_reply: 'M12 3.5l2.6 5.4 5.9.8-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8L3.5 9.7l5.9-.8z',
};
const SUB: Record<string, string> = {
    post: 'Feed', status: 'WhatsApp Status', carousel: 'Swipe-through', reel: 'Hook to CTA', ad: 'Copy + creative', whatsapp: 'Template + buttons',
    google_post: 'Offer on your listing', print: 'Flex, pamphlet', sms: 'DLT-ready, 160 chars', review_reply: 'Polite, in your language',
};
const TO_MESSAGES = ['whatsapp', 'sms', 'review_reply'];
const formats = computed(() => (data.value?.formats ?? []).map((f: any) => ({
    type: f.type,
    n: f.label,
    s: [SUB[f.type], f.ratio && f.ratio !== '1:1' ? f.ratio : ''].filter(Boolean).join(', '),
    cr: f.credits ? `${f.credits} cr` : 'free',
    tc: f.credits ? 'tag cr' : 'tag dk',
    d: ic[f.type] ?? ic.post,
    to: TO_MESSAGES.includes(f.type) ? { path: '/app/studio/messages' } : { path: '/app/create', query: { type: f.type } },
})));

// Business brain: what each item is, described from the shop's own data where we have it.
const describe = (key: string): string => {
    const s = shop.value ?? {};
    const join = (xs: any[]) => xs.filter(Boolean).join(' · ');
    switch (key) {
        case 'basics': return join([s.category_label, s.since_year ? `since ${s.since_year}` : '']);
        case 'location': return join([s.area, s.city, s.landmark]);
        case 'contact': return join([s.whatsapp, s.opens_at && s.closes_at ? `${s.opens_at}–${s.closes_at}` : '']);
        case 'audience': return join([(s.audience?.types ?? []).join(', '), (s.languages ?? []).map((l: string) => LANG_LABEL[l] ?? l).join(', ')]);
        case 'brand': return s.brand?.tagline ?? '';
        default: return '';
    }
};
const initials = (label: string) => label.split(/\s+/).filter((w) => /^[A-Za-z]/.test(w)).slice(0, 2).map((w) => w[0]!.toUpperCase()).join('');
const brain = computed(() => (data.value?.brain ?? []).map((b: any) => ({
    ...b,
    m: initials(b.label),
    d: describe(b.key),
    s: b.done ? 'Complete' : 'Needs you',
    sc: b.done ? 'st ok' : 'st warn',
    mt: b.done ? 'mt sm lt' : 'mt sm',
})));
const brainPct = computed(() => data.value?.brain_percent ?? 0);
const ticks = computed(() => {
    const on = Math.round(brainPct.value / 2);
    return Array.from({ length: 50 }, (_, i) => ({ c: i < on ? 'on' : '', d: (0.2 + i * 0.01).toFixed(2) + 's' }));
});

// This month's mix.
const STAGES = [['awareness', 'Pehchaan', 'Awareness', '#0F172A'], ['trust', 'Bharosa', 'Trust', '#475569'], ['buying', 'Kharidari', 'Buying', '#F2A007'], ['return', 'Wapsi', 'Return', '#94A3B8']];
const funnel = computed(() => {
    const by = month.value?.by_stage ?? {};
    const total = Object.values(by).reduce((a: number, b: any) => a + Number(b || 0), 0) as number;
    return STAGES.map(([k, n, en, c], i) => {
        const v = total ? Math.round(((by[k!] ?? 0) / total) * 100) : 0;
        return { k, n, en, v: v + '%', count: by[k!] ?? 0, w: v + '%', c, d: 0.3 + i * 0.1 + 's' };
    });
});
const byType = computed(() => {
    const xs = [...(month.value?.by_type ?? [])].sort((a: any, b: any) => b.count - a.count);
    const max = Math.max(1, ...xs.map((x: any) => x.count));
    return xs.map((x: any, i: number) => ({ n: x.label, v: x.count, w: (x.count / max) * 100 + '%', d: 0.3 + i * 0.07 + 's' }));
});

const credits = computed(() => account.value?.credits);
const planGoal = computed(() => {
    const p = plan.value;
    if (!p) return '';
    const g = p.strategy?.goal;
    return g?.target ? `${g.target} ${String(g.label || '').toLowerCase()}` : p.brief?.goal_label || g?.label || '';
});
const planStatus = computed(() => {
    const p = plan.value;
    if (!p) return '';
    return `v${p.version} · ${p.status === 'approved' ? 'approved' + (p.approved_at ? ' ' + day(p.approved_at) : '') : p.status === 'review' ? 'waiting for review' : p.status}`;
});
const planMonth = computed(() => (plan.value ? `${monthName(plan.value.month + '-01')} ${plan.value.month.slice(0, 4)}` : ''));

const running = computed(() => [
    { k: 'Workflows', st: `${counts.value.workflows_active ?? 0} live`, live: !!counts.value.workflows_active, t: 'Messages that send themselves', a: 'switched on', av: `${num(counts.value.workflows_active)} of ${num(counts.value.workflows)}`, b: 'set up', bv: num(counts.value.workflows), to: '/app/studio/workflows' },
    { k: 'Campaigns', st: `${counts.value.campaigns_live ?? 0} live`, live: !!counts.value.campaigns_live, t: 'Offers with a start and an end', a: 'running now', av: num(counts.value.campaigns_live), b: 'credits left', bv: num(credits.value?.left), to: '/app/studio/campaigns' },
    { k: 'Messages', st: `${counts.value.messages ?? 0} messages`, live: !!counts.value.messages, t: 'WhatsApp & SMS to your customers', a: 'messages', av: num(counts.value.messages), b: 'wallet', bv: inr(account.value?.wallet_paise), to: '/app/studio/messages' },
]);
const flow = [
    { k: '01 · KNOWS', t: 'Business brain', d: 'shop, products, customers', bc: '#E2E8F0' },
    { k: '02 · MAKES', t: 'Auto or Plan', d: 'every format, your languages', bc: '#E2E8F0' },
    { k: '03 · RUNS', t: 'Campaigns & workflows', d: 'scheduled, triggered', bc: '#E2E8F0' },
    { k: '04 · SENDS', t: 'Every channel', d: 'Status, Insta, FB, Google, SMS, print', bc: '#E2E8F0' },
    { k: '05 · ROUTES', t: 'Dikho shop page', d: 'every link and QR', bc: '#F2A007' },
    { k: '06 · COUNTS', t: 'Results', d: 'clicks, enquiries, visits', bc: '#0F172A' },
];
const langNames = computed(() => (shop.value?.languages ?? []).map((l: string) => LANG_LABEL[l] ?? l).join(', ') || 'your language');
const planLines = [76, 58, 88, 64, 48].map((w, i) => ({ w: w + '%', d: 0.4 + i * 0.12 + 's' }));
</script>

<template>
    <div class="wrap">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 420px"><span class="eyb"><b>CONTENT STUDIO</b>·<span>कंटेंट स्टूडियो</span></span><h1 class="h-lg">Your shop’s content, made every day</h1><p class="ts muted">AI writes and designs posts, reels, messages and ads from what it knows about {{ shop?.name }} — in {{ langNames }}. You decide what goes out.</p></div>
            <NuxtLink class="btn bs" to="/app/create">Quick create</NuxtLink><NuxtLink class="btn bp" to="/app/studio/auto">Open today’s content<svg class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></NuxtLink>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(460px, 100%), 1fr)); gap: 20px">
            <div class="mode rise d1">
                <div style="display: flex; align-items: center; gap: 10px"><span class="tag dk">MODE A</span><span class="h-md">Auto mode · अपने आप</span><span class="sp"></span><span v-if="counts.auto_waiting" class="st warn live">{{ counts.auto_waiting }} waiting</span></div>
                <div class="mgrid">
                    <div style="display: flex; flex-direction: column; gap: 12px; min-width: 0"><p class="ts dim">Content is made automatically from your shop information and local calendar. You approve, edit or skip anything.</p>
                        <div class="receipt">
                            <div class="ln" style="border-bottom: 1px dashed #E2E8F0"><span class="muted">waiting for you</span><span>{{ pending ? '…' : num(counts.auto_waiting) }}</span></div>
                            <div class="ln" style="border-bottom: 1px dashed #E2E8F0"><span class="muted">drafts in all</span><span>{{ pending ? '…' : num(counts.drafts) }}</span></div>
                            <div class="ln" style="border-bottom: 1px dashed #E2E8F0"><span class="muted">scheduled</span><span>{{ pending ? '…' : num(counts.scheduled) }}</span></div>
                            <div class="ln"><span class="muted">credits left</span><span>{{ num(credits?.left) }} of {{ num(credits?.total) }}</span></div>
                        </div>
                        <NuxtLink class="btn bp" to="/app/studio/auto" style="align-self: flex-start">Open auto board<svg class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></NuxtLink></div>
                    <div class="mart" style="position: relative; height: 190px" aria-hidden="true">
                        <div class="pst r45 y feed" style="position: absolute; width: 112px; left: 0; top: 0; font-size: 5.4px"><div class="pad" style="flex: 1; justify-content: flex-end"><span class="hk">{{ shop?.brand?.tagline || shop?.name }}</span></div><div class="ft"><b>{{ shop?.short_name }}</b></div></div>
                        <div class="panel feed" style="position: absolute; width: 132px; right: 0; top: 40px; padding: 8px 10px; animation-delay: 1.5s"><span class="mono" style="font-size: 9.5px; color: #64748B">WHATSAPP</span><p style="font-size: 11px; line-height: 15px; margin-top: 4px">Namaste ji, {{ shop?.short_name }}, {{ shop?.area }} se…</p></div>
                        <div class="pst r45 feed" style="position: absolute; width: 96px; left: 30px; top: 92px; font-size: 4.6px; animation-delay: 3s"><div class="pi">reel</div><div class="pad"><span class="hk">{{ shop?.area || shop?.city }}</span></div><div class="ft"><b>{{ shop?.short_name }}</b></div></div>
                    </div>
                </div>
            </div>
            <div class="mode rise d2">
                <div style="display: flex; align-items: center; gap: 10px"><span class="tag">MODE B</span><span class="h-md">Plan mode · अपना प्लान</span></div>
                <div class="mgrid">
                    <div style="display: flex; flex-direction: column; gap: 12px; min-width: 0"><p class="ts dim">Tell us how you market today and what you want next — goal, budget, audience, tone. AI writes the strategy, calendar and every piece; you change anything.</p>
                        <div v-if="plan" class="receipt"><div class="ln" style="border-bottom: 1px dashed #E2E8F0"><span class="muted">last plan</span><span>{{ planMonth }}</span></div><div class="ln" style="border-bottom: 1px dashed #E2E8F0"><span class="muted">goal</span><span style="text-align: right">{{ planGoal || '—' }}</span></div><div class="ln"><span class="muted">status</span><span>{{ planStatus }}</span></div></div>
                        <div v-else class="receipt"><div class="ln"><span class="muted">last plan</span><span>{{ planData.pending.value ? '…' : 'none yet' }}</span></div></div>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px"><NuxtLink class="btn bp" to="/app/studio/plan">Start a plan</NuxtLink><NuxtLink v-if="plan" class="btn bs" :to="{ path: '/app/studio/plan/result', query: { id: plan.id } }">{{ plan.status === 'review' ? 'Review plan' : 'Open last plan' }}</NuxtLink></div></div>
                    <div class="panel mart" style="padding: 12px; display: flex; flex-direction: column; gap: 7px" aria-hidden="true">
                        <span class="mono" style="font-size: 9.5px; color: #64748B">PLAN · {{ plan ? monthName(plan.month + '-01', 'short').toUpperCase() : monthName(nextMonth + '-01', 'short').toUpperCase() }}</span>
                        <span v-for="(l, i) in planLines" :key="i" style="display: flex; gap: 6px; align-items: center"><span class="ck on" style="width: 12px; height: 12px; border-radius: 3px"><svg class="i" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg></span><span class="gx" :style="`height: 6px; border-radius: 3px; background: #CBD5E1; width: ${l.w}; animation-delay: ${l.d}`"></span></span>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
            <section class="panel rise d3" style="flex: 1.25 1 480px; min-width: 0">
                <div class="ph"><span class="eyb"><b>01</b>·<span>Business brain — what the AI knows</span></span><span class="sp"></span><span class="mono">{{ pending ? '' : brainPct + '%' }}</span></div>
                <LoadState :pending="pending" :error="error" :rows="6" @retry="refresh">
                    <div style="padding: 14px 18px 4px"><div class="ticks sm"><i v-for="(k, i) in ticks" :key="i" :class="k.c" :style="`animation-delay: ${k.d}`"></i></div></div>
                    <NuxtLink v-for="b in brain" :key="b.key" class="row" :to="b.link" style="padding: 10px 18px"><span :class="b.mt">{{ b.m }}</span><span class="grow" style="min-width: 0"><span class="ts" style="font-weight: 500; display: block">{{ b.label }}</span><span v-if="b.d" class="tx muted">{{ b.d }}</span></span><span :class="b.sc">{{ b.s }}</span></NuxtLink>
                </LoadState>
            </section>
            <section class="panel rise d4" style="flex: 1 1 380px; min-width: 0">
                <div class="ph"><span class="eyb"><b>02</b>·<span>{{ month?.label || 'This month' }}’s mix</span></span><span class="sp"></span><span v-if="month" class="tag">{{ month.posted }} of {{ month.total }} posted</span></div>
                <LoadState :pending="pending" :error="error" :empty="!month?.total" empty-text="Nothing made this month yet" empty-hint="Open the auto board or make a post to get started." :rows="5" @retry="refresh">
                    <div class="pb" style="display: flex; flex-direction: column; gap: 18px">
                        <div style="display: flex; flex-direction: column; gap: 8px"><span class="lm">Funnel · grahak ka safar</span>
                            <div style="display: flex; height: 14px; gap: 2px; border-radius: 4px; overflow: hidden"><span v-for="f in funnel" :key="f.k" class="gx" :style="`width: ${f.w}; background: ${f.c}; animation-delay: ${f.d}`"></span></div>
                            <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px"><span v-for="f in funnel" :key="f.k" style="display: flex; flex-direction: column; min-width: 0"><span class="ts" style="font-weight: 600">{{ f.n }}</span><span class="tx muted">{{ f.en }} · {{ f.v }}</span></span></div></div>
                        <div style="display: flex; flex-direction: column; gap: 9px"><span class="lm">Formats</span>
                            <div v-for="p in byType" :key="p.n" class="tyrow"><span class="ts" style="min-width: 0"><b style="font-weight: 600">{{ p.n }}</b></span><span class="bar-t"><i :style="`width: ${p.w}; animation-delay: ${p.d}`"></i></span><span class="mono" style="text-align: right">{{ p.v }}</span></div></div>
                    </div>
                </LoadState>
            </section>
        </div>

        <section class="rise d5" style="display: flex; flex-direction: column; gap: 14px">
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px"><span class="eyb"><b>03</b>·<span>Make something now · {{ formats.length }} formats</span></span><span class="sp"></span><span class="tx muted">Every format comes in your language, with your colours, link and QR.</span></div>
            <LoadState :pending="pending" :error="error" :rows="3" @retry="refresh">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(150px, 100%), 1fr)); gap: 10px">
                    <NuxtLink v-for="f in formats" :key="f.type" class="fmt" :to="f.to"><span style="display: flex; justify-content: space-between; align-items: flex-start"><span class="mt sm lt"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path :d="f.d"></path></svg></span><span :class="f.tc">{{ f.cr }}</span></span><span style="display: flex; flex-direction: column"><span class="ts" style="font-weight: 600">{{ f.n }}</span><span class="tx muted">{{ f.s }}</span></span></NuxtLink>
                </div>
            </LoadState>
        </section>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr)); gap: 20px">
            <NuxtLink v-for="r in running" :key="r.k" class="panel hov rise d6" :to="r.to" style="padding: 18px; display: flex; flex-direction: column; gap: 10px; text-decoration: none; color: #0F172A"><span style="display: flex; align-items: center; gap: 8px"><span class="lm">{{ r.k }}</span><span class="sp"></span><span :class="r.live ? 'st ok live' : 'st'">{{ pending ? '…' : r.st }}</span></span><span class="h-md">{{ r.t }}</span><span class="receipt"><span class="ln" style="display: flex"><span class="muted">{{ r.a }}</span><span>{{ pending ? '…' : r.av }}</span></span><span class="ln" style="display: flex"><span class="muted">{{ r.b }}</span><span>{{ pending ? '…' : r.bv }}</span></span></span></NuxtLink>
        </div>

        <section class="panel rise d7">
            <div class="ph"><span class="eyb"><b>04</b>·<span>How every piece travels — and why every result is counted</span></span></div>
            <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
                <div class="flow">
                    <div class="flow-line"><span class="runner"></span><span class="runner" style="animation-delay: 3s"></span></div>
                    <div v-for="n in flow" :key="n.k" class="node" :style="`border-color: ${n.bc}`"><span class="mono" style="font-size: 10.5px; color: #64748B">{{ n.k }}</span><span class="ts" style="font-weight: 600">{{ n.t }}</span><span class="tx muted">{{ n.d }}</span></div>
                </div>
                <div class="note"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"></path></svg><span>Every link and QR in your content opens <span class="mono" style="color: #0F172A; overflow-wrap: anywhere">{{ shopUrl }}</span> — your free, Google-ready shop page. That is how a scan in {{ shop?.area || 'your area' }} or a click from Instagram becomes a number you can trust.</span></div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.mode{display:flex;flex-direction:column;gap:16px;padding:22px;border:1px solid #E2E8F0;border-radius:14px;background:#fff;min-width:0;transition:border-color .2s,transform .25s var(--ease)}.mode:hover{border-color:#CBD5E1;transform:translateY(-2px)}
.mgrid{display:grid;grid-template-columns:minmax(0,1fr) 150px;gap:20px;align-items:center}
.fmt{display:flex;flex-direction:column;gap:10px;padding:14px;border:1px solid #E2E8F0;border-radius:12px;background:#fff;text-decoration:none;color:#0F172A;min-height:112px;transition:border-color .2s,transform .25s var(--ease)}.fmt:hover{border-color:#0F172A;transform:translateY(-2px)}
.tyrow{display:grid;grid-template-columns:150px minmax(0,1fr) 34px;gap:10px;align-items:center}
.flow{position:relative;display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px;align-items:stretch}
.flow-line{position:absolute;left:4%;right:4%;top:50%;height:1.5px;background:#CBD5E1}
.node{position:relative;z-index:1;display:flex;flex-direction:column;gap:4px;padding:10px 12px;border:1px solid #E2E8F0;border-radius:10px;background:#fff;min-width:0}
@keyframes travel{0%{left:0;opacity:0}8%{opacity:1}92%{opacity:1}100%{left:100%;opacity:0}}.runner{position:absolute;top:-4px;width:9px;height:9px;border-radius:2px;background:#F2A007;transform:rotate(45deg);animation:travel 6s linear infinite}
@keyframes slideup{0%{transform:translateY(16px);opacity:0}15%,70%{transform:none;opacity:1}85%,100%{transform:translateY(-10px);opacity:0}}.feed{animation:slideup 4.5s var(--ease) infinite}
/* Narrow screens: drop the decorative art beside each mode, stack the flow, shrink type labels. */
@media (max-width: 560px){.mgrid{grid-template-columns:minmax(0,1fr)}.mart{display:none!important}.tyrow{grid-template-columns:110px minmax(0,1fr) 30px}}
@media (max-width: 860px){.flow{grid-template-columns:repeat(3,minmax(0,1fr))}.flow-line{display:none}}
@media (max-width: 480px){.flow{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
