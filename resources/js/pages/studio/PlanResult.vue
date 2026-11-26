<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { storeToRefs } from 'pinia';
import { useApp } from '../../stores/app.js';

const app = useApp();
const { shop, creditsTotal } = storeToRefs(app);

const wa = ref(false);
const ads = ref(false);
const mr = ref(false);
const noXmas = ref(false);
const ok = ref(false);
const toastTxt = ref('');
const chat = ref([['ai', 'Plan ready. Biggest change from today: your ₹1,500 Facebook boost becomes a 3 km click-to-WhatsApp ad - chats, not likes. Kuch badalna ho to bataiye.']]);

let tt = null;
onBeforeUnmount(() => clearTimeout(tt));

const f = (n) => '₹' + n.toLocaleString('en-IN');

const kpis = [
    { l: 'Goal · enquiries', v: '60', s: 'from ~15 today' },
    { l: 'People reached', v: '25,000', s: 'Status, Insta, ads' },
    { l: 'Clicks + scans', v: '900', s: 'via dikho.in links' },
    { l: 'Store visits', v: '35', s: 'counted by QR' },
    { l: 'Regulars back', v: '12', s: 'of 14 lapsed + new' },
];
const whys = [
    { n: '1', t: 'Lead with the exchange offer', d: 'Offer hooks got 3× more chats than greetings for you in October–November.' },
    { n: '2', t: 'Stop boosting for likes', d: 'Your ₹1,500 Facebook boost becomes a click-to-WhatsApp ad within 3 km - it pays for chats.' },
    { n: '3', t: 'Peak on wedding weekends', d: '5–6 and 12–13 Dec get the reels and ads; weekdays get trust and tips.' },
    { n: '4', t: 'Win back the 14 regulars first', d: 'Personal codes to people who already trust you - the cheapest enquiries of the month.' },
];
const funnel = [
    ['Pehchaan', 'Awareness', 30, 12, 'Reels, Google posts, Status', 'College Road ka bharosemand mobile ghar', '#0F172A'],
    ['Bharosa', 'Trust', 25, 10, 'Customer stories, tips carousels', '12 saal, hazaaron exchange', '#475569'],
    ['Kharidari', 'Buying', 30, 13, 'Offer posts, WhatsApp, SMS, ads', 'Purana phone laao, ₹2,000 extra pao', '#F2A007'],
    ['Wapsi', 'Coming back', 15, 7, 'Check-ins, birthdays, reviews', 'Phone kaisa chal raha hai?', '#94A3B8'],
].map(([n, en, p, c, fm, msg, col]) => ({ n, en, p: p + '%', c, fm, msg, col, w: (p / 30) * 100 + '%' }));
const flows = [
    { n: '30-day check-in', d: 'After every phone sale' },
    { n: 'Birthday wish + ₹100', d: '86 birthdays on file' },
    { n: 'Win-back · 14 lapsed', d: '3 messages, personal codes' },
    { n: 'Missed call → WhatsApp', d: 'Reply within 1 minute' },
];

const rows = computed(() => {
    const ig = wa.value ? [2, 8, 34] : [3, 12, 50];
    const w = wa.value ? [2, 8, 24, 1020] : [1, 4, 12, 510];
    const meta = ads.value ? 2000 : wa.value ? 1890 : 2400;
    return { ig, w, sms: 90, meta };
});
const C = computed(() => {
    const { ig, w, sms, meta } = rows.value;
    return [
        ['WhatsApp Status', 'daily', 'Be seen by everyone who has your number', 31, 12, 0],
        ['Instagram', ig[0] + ' / week', 'Reels and posts for new people', ig[1], ig[2], 0],
        ['Facebook Page', 'cross-post', 'Same posts, no boosting', '—', 0, 0],
        ['Google Business', '1 / week', 'Show up when ' + shop.value.city + ' searches “mobile shop”', 4, 8, 0],
        ['WhatsApp to customers', w[0] + ' / week', 'Offers to the 148 who said yes', w[1], w[2], w[3]],
        ['SMS · DLT', '2 / month', 'Reach people not on WhatsApp', 2, 0, sms],
        ['Meta ads · click-to-chat', '2 × 7 days', '3 km around ' + shop.value.area, 2, 40, meta],
        ['Win-back + print', 'once', '14 lapsed regulars · counter flex', 2, 18, 0],
    ];
});
const crRaw = computed(() => C.value.reduce((a, c) => a + c[4], 0));
const wTot = computed(() => C.value.reduce((a, c) => a + c[5], 0));
const crTot = computed(() => (noXmas.value ? crRaw.value - 21 : crRaw.value));
const buf = computed(() => creditsTotal.value - crTot.value);
const chans = computed(() => C.value.map(([n, fq, j, p, cr, w], i) => ({
    n, f: fq, j, p, cr: cr || '—', w: w ? f(w) : '—',
    bg: (wa.value && (i === 1 || i === 4 || i === 6)) || (ads.value && i === 6) ? '#FEF3DC' : 'transparent',
})));
const wallet = computed(() => [
    { n: 'Meta ads', v: f(rows.value.meta) },
    { n: 'WhatsApp messages', v: f(rows.value.w[3]) },
    { n: 'SMS', v: f(rows.value.sms) },
]);
const langSplit = computed(() => (mr.value ? 'Hinglish 35% · हिन्दी 25% · मराठी 40%' : 'Hinglish 45% · हिन्दी 30% · मराठी 25%'));
const weeks = computed(() => {
    const W = [
        ['Week 1', '1 – 6 DEC', 'Exchange Mela launch', [['video', 'Teaser reel'], ['post', 'Offer post'], ['camp', 'WhatsApp offer'], ['ad', 'Ad #1 starts'], ['post', 'Google offer']]],
        ['Week 2', '7 – 13 DEC', 'Wedding weekend peak', [['post', 'Shaadi combo'], ['video', 'Shop-tour reel'], ['camp', 'SMS to 148'], ['post', 'Customer story'], ['camp', 'Win-back starts']]],
        ['Week 3', '14 – 20 DEC', 'Trust and tips', [['post', '5 checks carousel'], ['video', 'Safety tips reel'], ['post', 'Google post'], ['camp', 'WhatsApp offer']]],
        noXmas.value
            ? ['Week 4', '21 – 31 DEC', 'Year-end clearance', [['post', 'Clearance list'], ['ad', 'Ad #2 · last call'], ['camp', 'Thank-you + review']]]
            : ['Week 4', '21 – 31 DEC', 'Christmas → clearance', [['post', 'Xmas tease'], ['video', 'Year-end reel'], ['free', 'Greeting'], ['post', 'Clearance list'], ['ad', 'Ad #2 · last call'], ['camp', 'Thank-you + review']]],
    ];
    return W.map(([n, d, theme, items]) => ({ n, d, theme, items: items.map(([k, t], i) => ({ k, t, dl: i * 0.05 + 's' })) }));
});
const ver = computed(() => {
    const edits = [wa.value, ads.value, mr.value, noXmas.value].filter(Boolean).length;
    return 'v' + (2 + edits) + ' · BASED ON YOUR BRIEF OF 22 NOV';
});

function add(me, ai) {
    chat.value = chat.value.concat([['me', me], ['ai', ai]]);
}
const asks = computed(() => [
    ['Instagram kam, WhatsApp zyada', wa.value, () => { add('Instagram kam karo, WhatsApp zyada', 'Done - Instagram 3 → 2 a week, WhatsApp offers 1 → 2 a week. Meta ads trimmed to ₹1,890 so the wallet stays within ₹3,000.'); wa.value = true; }],
    ['Ads ₹2,000 tak', ads.value, () => { add('Ads ka budget ₹2,000 karo', 'Done - both ads now share ₹2,000. The 3 km radius stays; each runs 7 days.'); ads.value = true; }],
    ['Marathi zyada', mr.value, () => { add('Marathi mein zyada content', 'Done - Marathi goes from 25% to 40% of pieces. Status stays bilingual.'); mr.value = true; }],
    ['Christmas hata do', noXmas.value, () => { add('Christmas wala part hata do', 'Done - week 4 is now only the year-end clearance. 21 credits freed for surprises.'); noXmas.value = true; }],
].map(([n, on, run]) => ({ n, on, run })));

function show(m) {
    toastTxt.value = m;
    clearTimeout(tt);
    tt = setTimeout(() => {
        toastTxt.value = '';
    }, 3000);
}
function approve() {
    ok.value = true;
    show('Plan approved · 42 pieces are being made in your Auto board');
}
</script>

<template>
    <div class="wrap" style="max-width: 1360px">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 440px">
                <span class="eyb"><b>PLAN MODE</b>·<span>GENERATED PLAN · {{ ver }}</span></span>
                <h1 class="h-lg">Shaadi season push · 1 – 31 December</h1>
                <p class="ts muted">Written from your brief in 40 seconds. Change anything - by editing, or by telling the AI below.</p>
            </div>
            <RouterLink class="btn bs" :to="{ name: 'studio-plan' }">Edit brief</RouterLink>
            <button v-if="!ok" type="button" class="btn bp" @click="approve">Approve &amp; make content</button>
            <RouterLink v-else class="btn bp" :to="{ name: 'studio-auto' }">
                See the 42 pieces<svg class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
            </RouterLink>
        </div>
        <div class="strip rise d1">
            <div v-for="k in kpis" :key="k.l"><span class="lm">{{ k.l }}</span><span class="num">{{ k.v }}</span><span class="tx muted">{{ k.s }}</span></div>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
            <div style="flex: 2.2 1 640px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                <section class="panel rise d2">
                    <div class="ph"><span class="eyb"><b>01</b>·<span>The idea in four lines</span></span><span class="sp"></span><span class="tag">strategy</span></div>
                    <div style="padding: 4px 18px">
                        <div v-for="w in whys" :key="w.n" class="why">
                            <span class="mt sm lt">{{ w.n }}</span>
                            <span><span class="h-sm" style="display: block">{{ w.t }}</span><span class="ts muted">{{ w.d }}</span></span>
                        </div>
                    </div>
                </section>

                <section class="panel rise d3">
                    <div class="ph"><span class="eyb"><b>02</b>·<span>Funnel · grahak ka safar</span></span><span class="sp"></span><span class="mono">42 pieces</span></div>
                    <div class="pb" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(190px, 100%), 1fr)); gap: 10px">
                        <div v-for="x in funnel" :key="x.n" class="fc">
                            <span style="display: flex; justify-content: space-between; align-items: baseline"><span class="h-sm">{{ x.n }}</span><span class="mono">{{ x.p }}</span></span>
                            <span class="tx muted">{{ x.en }} · {{ x.c }} pieces</span>
                            <div class="bar-t"><i :style="{ width: x.w, background: x.col }"></i></div>
                            <span class="ts">{{ x.fm }}</span>
                            <span class="tx" style="color: #334155; font-style: italic">“{{ x.msg }}”</span>
                        </div>
                    </div>
                </section>

                <section class="panel rise d4" style="overflow: hidden">
                    <div class="ph"><span class="eyb"><b>03</b>·<span>Channels</span></span><span class="sp"></span><span class="tx muted">{{ langSplit }}</span></div>
                    <div class="tscroll">
                        <table class="tbl">
                            <thead><tr><th>Channel</th><th>How often</th><th>Its job</th><th style="text-align: right">Pieces</th><th style="text-align: right">Credits</th><th style="text-align: right">Wallet</th></tr></thead>
                            <tbody>
                                <tr v-for="c in chans" :key="c.n" :style="{ background: c.bg }">
                                    <td><b style="font-weight: 500">{{ c.n }}</b></td>
                                    <td class="mono">{{ c.f }}</td>
                                    <td class="muted">{{ c.j }}</td>
                                    <td class="mono" style="text-align: right">{{ c.p }}</td>
                                    <td class="mono" style="text-align: right">{{ c.cr }}</td>
                                    <td class="mono" style="text-align: right">{{ c.w }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="panel rise d5">
                    <div class="ph"><span class="eyb"><b>04</b>·<span>Four weeks</span></span><span class="sp"></span><RouterLink class="btn bg sm" :to="{ name: 'calendar' }">Open in calendar</RouterLink></div>
                    <div v-for="w in weeks" :key="w.n" class="wk">
                        <span>
                            <span class="h-sm" style="display: block">{{ w.n }}</span>
                            <span class="mono muted" style="font-size: 11px">{{ w.d }}</span>
                            <span class="tx" style="display: block; margin-top: 4px; color: #334155">{{ w.theme }}</span>
                        </span>
                        <span style="display: flex; flex-wrap: wrap; gap: 6px">
                            <span v-for="i in w.items" :key="i.t" class="pill2" :style="{ animationDelay: i.dl }"><span :class="'ty ' + i.k"></span>{{ i.t }}</span>
                        </span>
                    </div>
                </section>
            </div>

            <aside style="flex: 1 1 320px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                <section class="panel rise d2">
                    <div class="ph"><span class="eyb"><b>AI</b>·<span>Isme kya badlein?</span></span></div>
                    <div class="pb" style="display: flex; flex-direction: column; gap: 12px">
                        <div class="chat"><p v-for="(m, i) in chat" :key="i" :class="m[0]">{{ m[1] }}</p></div>
                        <div class="chips"><button v-for="a in asks" :key="a.n" type="button" :class="'chip' + (a.on ? ' on' : '')" @click="a.run">{{ a.n }}</button></div>
                        <div class="iw">
                            <input class="in" placeholder="Likh ke ya bol ke batayein…" aria-label="Tell the AI what to change" />
                            <button type="button" class="btn ba ib sm" aria-label="Speak" style="right: 5px">
                                <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a3 3 0 0 1 3 3v5a3 3 0 0 1-6 0V6a3 3 0 0 1 3-3zM5 11a7 7 0 0 0 14 0M12 18v3"></path></svg>
                            </button>
                        </div>
                    </div>
                </section>
                <section class="panel rise d3">
                    <div class="ph"><span class="eyb"><b>05</b>·<span>Budget</span></span><span class="sp"></span><span class="tag cr">{{ crTot }} / {{ creditsTotal }} cr</span></div>
                    <div class="pb receipt">
                        <div v-for="w in wallet" :key="w.n" class="ln"><span class="muted">{{ w.n }}</span><span>{{ w.v }}</span></div>
                        <div class="ln tot"><span>wallet total</span><span>{{ f(wTot) }} of ₹3,000</span></div>
                        <div class="ln"><span class="muted">credits left for surprises</span><span>{{ buf }} cr</span></div>
                    </div>
                </section>
                <section class="panel rise d4">
                    <div class="ph"><span class="eyb"><b>06</b>·<span>Workflows it switches on</span></span></div>
                    <RouterLink v-for="x in flows" :key="x.n" class="row" :to="{ name: 'studio-workflows' }" style="padding: 10px 18px">
                        <span class="grow"><span class="ts" style="font-weight: 500; display: block">{{ x.n }}</span><span class="tx muted">{{ x.d }}</span></span>
                        <span class="st ok">on</span>
                    </RouterLink>
                </section>
            </aside>
        </div>
    </div>
    <div v-if="toastTxt" class="toast" role="status">{{ toastTxt }}</div>
</template>

<style scoped>
.why{display:grid;grid-template-columns:28px minmax(0,1fr);gap:12px;padding:12px 0;border-bottom:1px dashed #E2E8F0}
.why:last-child{border-bottom:0}
.fc{display:flex;flex-direction:column;gap:8px;padding:14px;border:1px solid #E2E8F0;border-radius:12px;min-width:0}
.wk{display:grid;grid-template-columns:150px minmax(0,1fr);gap:14px;padding:12px 18px;border-bottom:1px solid #E2E8F0;align-items:start}
.wk:last-child{border-bottom:0}
.pill2{display:inline-flex;align-items:center;gap:6px;height:26px;padding:0 9px;border:1px solid #E2E8F0;border-radius:7px;background:#fff;font-size:12.5px;white-space:nowrap;animation:rise .45s var(--ease) both}
.chat{display:flex;flex-direction:column;gap:10px;max-height:320px;overflow-y:auto}
.me{align-self:flex-end;background:#0F172A;color:#fff;border-radius:14px 4px 14px 14px;padding:8px 12px;font-size:13px;max-width:85%;animation:rise .4s var(--ease) both}
.ai{align-self:flex-start;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:4px 14px 14px 14px;padding:9px 12px;font-size:13px;line-height:19px;max-width:92%;animation:rise .4s var(--ease) both}
</style>
