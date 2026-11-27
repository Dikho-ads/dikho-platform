<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useApp } from '../stores/app';

const app = useApp();

const p = ref(0);
const lang = ref(0);
const posted = ref([]);
const batchShown = ref(false);
const approved = ref(false);
const toast = ref('');
let raf = 0;
let tt = 0;

onMounted(() => {
    const t0 = performance.now();
    const tick = (t) => {
        p.value = Math.min(1, (t - t0) / 1100);
        if (p.value < 1) raf = requestAnimationFrame(tick);
    };
    raf = requestAnimationFrame(tick);
});
onBeforeUnmount(() => {
    cancelAnimationFrame(raf);
    clearTimeout(tt);
});

const ease = computed(() => 1 - Math.pow(1 - p.value, 3));
const f = (n) => Math.round(n * ease.value).toLocaleString('en-IN');

const H = [
    ['Purana phone laao, ₹2,000 extra pao', 'पुराना फ़ोन लाओ, ₹2,000 एक्स्ट्रा पाओ', 'जुना फोन आणा, ₹2,000 जास्त मिळवा'],
    ['5G phone ₹9,999', '5G फ़ोन ₹9,999', '5G फोन ₹9,999'],
    ['“Exchange mein ₹4,000 zyada mile.”', '“एक्सचेंज में ₹4,000 ज़्यादा मिले।”', '“एक्सचेंजमध्ये ₹4,000 जास्त मिळाले.”'],
];
const P = [
    { n: 'Shaadi offer', why: 'Offer angle · deadline is real', trust: 95, pc: 'pst r45 y', ph: 'Ramesh with phones', terms: 'Sunday 29 Nov tak · value as per condition', foot: 'WhatsApp', top: false, bottom: true, padStyle: 'flex: 1; justify-content: flex-end' },
    { n: 'New arrival', why: 'Newness · MRP shown next to price', trust: 97, pc: 'pst r45', ph: 'phone on counter', terms: 'MRP ₹12,499 · offer till Sunday', foot: '★ 4.6 · 312', top: true, bottom: false, padStyle: '' },
    { n: 'Customer quote', why: 'Trust · Priya agreed to be quoted', trust: 96, pc: 'pst r45 w', ph: 'Priya, with consent', terms: '— Priya, College Road', foot: '★ 4.6', top: false, bottom: true, padStyle: 'flex: 1' },
];
const D = [['F', 3], ['S', 11], ['S', 5], ['M', 4], ['T', 6], ['W', 4], ['T', 5]];
const langNames = ['Hinglish', 'हिन्दी', 'मराठी'];
const langCodes = ['en', 'hi', 'mr'];
const batch = [['SUN 29', 'video', 'Shaadi exchange reel', '15'], ['TUE 1', 'video', 'Zero-cost EMI reel', '15'], ['THU 3', 'post', 'Wedding gifts', '6'], ['FRI 4', 'post', 'Customer story — Priya', '2'], ['SAT 5', 'camp', 'Shaadi combo · 148 people', '3']].map(([d, k, t, cr]) => ({ d, k, t, cr: cr + ' cr' }));
const occ = [
    { d: 'NOW', k: 'camp', n: 'Wedding season', s: 'Local event · tease, peak, thank-you', st: 'In plan', sc: 'st ok' },
    { d: '25 DEC', k: 'video', n: 'Christmas → New Year', s: 'Festival · 6 phases · 52 cr', st: 'In plan', sc: 'st ok' },
    { d: '26 DEC', k: 'ad', n: 'Year-end clearance', s: 'New offer · launch, last call', st: 'In plan', sc: 'st ok' },
    { d: '14 JAN', k: 'post', n: 'Makar Sankranti', s: 'Festival · harvest money', st: 'Suggested', sc: 'st acc' },
    { d: '26 JAN', k: 'free', n: 'Republic Day', s: 'Greeting · free', st: 'Free post', sc: 'st' },
];

const k0 = computed(() => f(app.creditsLeft));
const onTicks = computed(() => (app.creditsTotal ? Math.round((app.creditsLeft / app.creditsTotal) * 50) : 0));
const ticks = computed(() => Array.from({ length: 50 }, (_, i) => ({ c: i < onTicks.value ? 'on' : '', d: (0.15 + i * 0.012).toFixed(3) + 's' })));
const kpis = computed(() => [
    { l: 'Saw it', v: f(2340), d: '+18%' },
    { l: 'Chats', v: f(38), d: '+31%' },
    { l: 'Calls', v: f(12), d: '+20%' },
    { l: 'QR used', v: f(9), d: '+50%' },
]);
const posts = computed(() => P.map((x, i) => ({ ...x, hook: H[i][lang.value], open: !posted.value.includes(i), done: posted.value.includes(i) })));
const days = D.map(([n, v], i) => ({ n, v, h: Math.round((v / 11) * 112) + 'px', c: v === 11 ? '#F2A007' : '#0F172A', tc: v === 11 ? '#0F172A' : '#64748B', dl: 0.3 + i * 0.06 + 's' }));
const needCount = computed(() => (approved.value ? 3 : 4));
const batchSub = computed(() => (approved.value ? 'Approved · goes out on its days' : '2 reels, 2 posts, 1 WhatsApp offer'));
const todayLabel = 'THU 26 NOV';
const cityLabel = computed(() => app.shop.city.toUpperCase());

function show(msg) {
    toast.value = msg;
    clearTimeout(tt);
    tt = setTimeout(() => (toast.value = ''), 3200);
}
function postNow(i) {
    posted.value = posted.value.concat(i);
    show(P[i].n + ' → Status, Instagram, Facebook');
}
function approve() {
    if (!app.spend(41)) {
        show('Not enough credits to approve this batch');
        return;
    }
    approved.value = true;
    show('5 items approved for this week');
}
function undo() {
    toast.value = '';
    posted.value = posted.value.slice(0, -1);
}
</script>

<template>
    <div class="wrap">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 16px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 400px">
                <span class="eyb"><b>{{ todayLabel }}</b>·<span>{{ cityLabel }} · 7:42 AM</span></span>
                <h1 class="h-lg">Namaste, {{ app.user.name }} ji</h1>
                <p class="ts muted">Three posts are ready, five things need a look, and December’s plan is drafted.</p>
            </div>
            <RouterLink class="btn bs" :to="{ name: 'calendar' }">Review December</RouterLink>
            <RouterLink class="btn bp" :to="{ name: 'create' }"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>Create</RouterLink>
        </div>
        <div class="strip rise d1">
            <div>
                <span class="lm">Credits left</span>
                <span><span class="num">{{ k0 }}</span><span class="muted"> / {{ app.creditsTotal }}</span></span>
                <div class="ticks sm"><i v-for="(k, i) in ticks" :key="i" :class="k.c" :style="{ animationDelay: k.d }"></i></div>
            </div>
            <div v-for="k in kpis" :key="k.l">
                <span class="lm">{{ k.l }}</span>
                <span class="num">{{ k.v }}</span>
                <span class="tx muted"><b style="color: #0F172A; font-weight: 500">{{ k.d }}</b> vs last week</span>
            </div>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
            <div style="flex: 2 1 560px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                <section class="panel rise d2">
                    <div class="ph">
                        <span class="eyb"><b>01</b>·<span>Today’s ready posts</span></span><span class="tag dk">FREE</span><span class="sp"></span>
                        <div class="seg" role="group" aria-label="Post language">
                            <button v-for="(n, i) in langNames" :key="n" type="button" :class="lang === i ? 'on' : ''" @click="lang = i">{{ n }}</button>
                        </div>
                    </div>
                    <div class="pb ready-posts" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(160px, 100%), 1fr)); gap: 14px">
                        <div v-for="(x, i) in posts" :key="x.n" class="pc hov">
                            <div :class="x.pc" style="font-size: 9.4px">
                                <div v-if="x.top" class="pi">{{ x.ph }}</div>
                                <div class="pad" :style="`gap: .55em; ${x.padStyle}`"><span class="hk" :lang="langCodes[lang]">{{ x.hook }}</span><span class="terms">{{ x.terms }}</span></div>
                                <div v-if="x.bottom" class="pi" style="flex: 0 0 26%">{{ x.ph }}</div>
                                <div class="ft"><b>{{ app.shop.name }}</b><span>{{ x.foot }}</span></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center"><span class="h-sm">{{ x.n }}</span><span class="tag">trust {{ x.trust }}</span></div>
                            <span class="tx muted">{{ x.why }}</span>
                            <div style="display: flex; gap: 6px">
                                <button v-if="x.open" type="button" class="btn bp sm" style="flex: 1" @click="postNow(i)">Post now</button>
                                <span v-if="x.done" class="btn bs sm" style="flex: 1; cursor: default"><span class="st ok">Posted · 3 channels</span></span>
                                <RouterLink class="btn bs sm" :to="{ name: 'create' }">Edit</RouterLink>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="panel rise d3">
                    <div class="ph">
                        <span class="eyb"><b>02</b>·<span>This week · chats per day</span></span><span class="sp"></span>
                        <RouterLink class="btn bg sm" :to="{ name: 'results' }">All results<svg class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></RouterLink>
                    </div>
                    <div class="pb" style="display: flex; flex-wrap: wrap; gap: 28px; align-items: flex-end">
                        <div style="flex: 1 1 300px; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 10px; align-items: end; height: 150px">
                            <div v-for="(d, i) in days" :key="i" style="display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end">
                                <span class="mono" :style="{ color: d.tc }">{{ d.v }}</span>
                                <span class="gy" :style="{ width: '100%', height: d.h, background: d.c, borderRadius: '4px 4px 0 0', animationDelay: d.dl }"></span>
                                <span class="mono muted">{{ d.n }}</span>
                            </div>
                        </div>
                        <div class="receipt" style="flex: 1 1 220px">
                            <div class="ln" style="border-bottom: 1px solid #E2E8F0"><span class="muted">best day</span><span>Sat · offer post</span></div>
                            <div class="ln" style="border-bottom: 1px solid #E2E8F0"><span class="muted">best time</span><span>7–9 pm · Status</span></div>
                            <div class="ln" style="border-bottom: 1px solid #E2E8F0"><span class="muted">best hook</span><span>“₹2,000 extra pao”</span></div>
                            <div class="ln"><span class="muted">cost per chat</span><span>₹32 · Meta ad</span></div>
                        </div>
                    </div>
                </section>
            </div>
            <div style="flex: 1 1 320px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                <section class="panel rise d3">
                    <div class="ph"><span class="eyb"><b>03</b>·<span>Needs you</span></span><span class="sp"></span><span class="mono muted">{{ needCount }} open</span></div>
                    <RouterLink class="row" :to="{ name: 'calendar' }"><span class="ty camp"></span><span class="grow"><span class="h-sm" style="display: block">December plan</span><span class="tx muted">14 items · 131 of {{ app.creditsTotal }} credits</span></span><span class="st warn">by 30 Nov</span></RouterLink>
                    <button type="button" class="row" @click="batchShown = !batchShown">
                        <span class="ty video"></span><span class="grow"><span class="h-sm" style="display: block">This week’s batch</span><span class="tx muted">{{ batchSub }}</span></span>
                        <svg v-if="batchShown" class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 15l6-6 6 6"></path></svg>
                        <svg v-else class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg>
                    </button>
                    <div v-if="batchShown" class="fade" style="padding: 4px 18px 14px; border-bottom: 1px solid #E2E8F0; display: flex; flex-direction: column; gap: 2px">
                        <div v-for="b in batch" :key="b.d" style="display: grid; grid-template-columns: 58px 12px minmax(0, 1fr) auto; gap: 8px; align-items: center; padding: 6px 0">
                            <span class="mono muted">{{ b.d }}</span><span :class="['ty', b.k]"></span><span class="ts">{{ b.t }}</span><span class="mono">{{ b.cr }}</span>
                        </div>
                        <button v-if="!approved" type="button" class="btn bp sm" style="margin-top: 8px; align-self: flex-start" @click="approve">Approve 5 · 41 credits</button>
                    </div>
                    <RouterLink class="row" :to="{ name: 'customers' }"><span class="ty post"></span><span class="grow"><span class="h-sm" style="display: block">14 regulars stopped coming</span><span class="tx muted">Personal offer to each · ≈ ₹36</span></span><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"></path></svg></RouterLink>
                    <RouterLink class="row" :to="{ name: 'setup' }"><span class="ty free"></span><span class="grow"><span class="h-sm" style="display: block">Add 2 product photos</span><span class="tx muted">Unlocks reels · profile 60%</span></span><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"></path></svg></RouterLink>
                </section>
                <section class="panel rise d4">
                    <div class="ph"><span class="eyb"><b>04</b>·<span>Coming up</span></span><span class="sp"></span><RouterLink class="btn bg sm" :to="{ name: 'occasions' }">Calendar</RouterLink></div>
                    <RouterLink v-for="o in occ" :key="o.n" class="tl" :to="{ name: 'occasions' }">
                        <span class="mono muted">{{ o.d }}</span><span :class="['ty', o.k]"></span>
                        <span><span class="ts" style="font-weight: 500; display: block">{{ o.n }}</span><span class="tx muted">{{ o.s }}</span></span>
                        <span :class="o.sc">{{ o.st }}</span>
                    </RouterLink>
                </section>
            </div>
        </div>
    </div>
    <div v-if="toast" class="toast" role="status">
        <span class="st ok" style="color: #fff">Posted</span><span>{{ toast }}</span><span style="width: 1px; height: 18px; background: #334155"></span>
        <button type="button" class="lnk" style="color: #F2A007; text-decoration-color: #F2A007" @click="undo">Undo</button>
    </div>
</template>

<style scoped>
.pc { display: flex; flex-direction: column; gap: 12px; padding: 14px; border: 1px solid #E2E8F0; border-radius: 12px; min-width: 0; }
.tl { display: grid; grid-template-columns: 64px 14px minmax(0, 1fr) auto; gap: 10px; align-items: center; padding: 11px 18px; text-decoration: none; color: #0F172A; transition: background .15s; }
.tl:hover { background: #F8FAFC; }
</style>

<style scoped>
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
