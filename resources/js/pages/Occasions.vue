<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { useApp } from '../stores/app';

const app = useApp();

const sel = ref(1);
const filter = ref(0);
const off = ref({});
const added = ref([0, 1, 2]);
const toast = ref('');
let tt = 0;
onBeforeUnmount(() => clearTimeout(tt));

const O = [
    { n: 'Wedding season', type: 'Local event', f: 2, day: 'NOW', mon: 'NOV–DEC', t: 'Sat 12 Dec', lead: '9 days · long season', ch: 'Status, 3 km ads', hook: '“Shaadi ka gift, sahi phone”', d: 'Families buy phones as wedding gifts. Tease the gift idea, peak on the busiest weekend, thank buyers after.',
        ph: [['Tease', -9, 'Thu 3 Dec', 'Wedding gifts scene post', 'Feed, Status', 6, 'post'], ['Peak', 0, 'Sat 12 Dec', 'Shaadi combo: phone + cover + glass', 'Feed, broadcast', 6, 'post'], ['Thank-you', 3, 'Tue 15 Dec', 'Thanks + review ask to buyers', 'WhatsApp, opted-in', 3, 'camp']] },
    { n: 'Christmas to New Year', type: 'Festival', f: 1, day: '25', mon: 'DEC', t: 'Fri 25 Dec', lead: '10–14 days', ch: 'Status, reels, Meta ads', hook: '“Saal ka aakhri offer”', d: 'Year-end is when people spend what’s left of the budget. Six phases around the day, inside your credits.',
        ph: [['Tease', -5, 'Sun 20 Dec', '“Kuch bada aa raha hai” scene post', 'Status, Story', 6, 'post'], ['Launch', -2, 'Wed 23 Dec', 'Year-end offer reel with jingle', 'Instagram, Facebook', 15, 'video'], ['Peak', 0, 'Fri 25 Dec', 'Greeting · open till 10 pm', 'All', 0, 'free'], ['Build', 1, 'Sat 26 Dec', 'Clearance list carousel', 'Feed, Status', 5, 'post'], ['Last call', 3, 'Mon 28 Dec', 'Click-to-WhatsApp ad, 3 km', 'Meta ads', 20, 'ad'], ['Thank-you', 6, 'Thu 31 Dec', 'New Year thanks + review ask', 'WhatsApp, opted-in', 6, 'camp']] },
    { n: 'Year-end clearance', type: 'New offer', f: 1, day: '26', mon: 'DEC', t: 'Sat 26 Dec', lead: '3–5 days', ch: 'Broadcast, ads, poster', hook: '“Stock khatam, daam kam”', d: 'Runs inside the Christmas window: launch, build, last call — only while stock really lasts.',
        ph: [['Launch', 0, 'Sat 26 Dec', 'Clearance list carousel', 'Feed, Status', 5, 'post'], ['Build', 1, 'Sun 27 Dec', 'Status countdown', 'Status', 0, 'free'], ['Last call', 2, 'Mon 28 Dec', 'Click-to-WhatsApp ad', 'Meta ads', 20, 'ad']] },
    { n: 'Makar Sankranti', type: 'Festival', f: 1, day: '14', mon: 'JAN', t: 'Thu 14 Jan', lead: '10–14 days', ch: 'Status, reels, broadcast', hook: '“Til-gul ghya, god god bola”', d: 'Harvest money and winter gifting. Suggested from your district calendar — not in your plan yet.',
        ph: [['Tease', -9, 'Tue 5 Jan', 'Kite-season teaser', 'Status', 2, 'post'], ['Launch', -5, 'Sat 9 Jan', 'Sankranti offer reel', 'Instagram, Status', 15, 'video'], ['Build', -2, 'Tue 12 Jan', 'Gift ideas carousel', 'Feed', 5, 'post'], ['Peak', 0, 'Thu 14 Jan', 'Greeting with family photo', 'All', 0, 'free'], ['Last call', 1, 'Thu 15 Jan', 'Last day of offer', 'Status', 2, 'post'], ['Thank-you', 3, 'Sat 17 Jan', 'Thanks to buyers', 'WhatsApp, opted-in', 3, 'camp']] },
    { n: 'Republic Day', type: 'Greeting', f: 1, day: '26', mon: 'JAN', t: 'Tue 26 Jan', lead: '1 day', ch: 'Status', hook: '“Jai Hind” — no selling', d: 'A greeting only — the message leads and the shop appears as the sign-off. Goes out as a free daily post.',
        ph: [['Peak', 0, 'Tue 26 Jan', 'Greeting, shop as sign-off', 'Status', 0, 'free']] },
];
const filterNames = ['All', 'Festivals', 'Local', 'Yours'];

const vis = computed(() => O.map((o, i) => ({ o, i })).filter(({ o }) => filter.value === 0 || (filter.value === 1 && o.f === 1) || (filter.value === 2 && o.f === 2)));
const c = computed(() => O[sel.value]);
const offs = computed(() => off.value[sel.value] || []);
const total = computed(() => c.value.ph.reduce((a, p, i) => a + (offs.value.includes(i) ? 0 : p[5]), 0));
const inPlan = computed(() => added.value.includes(sel.value));
const count = computed(() => vis.value.length + ' occasions');
const none = computed(() => vis.value.length === 0);
const list = computed(() => vis.value.map(({ o, i }) => ({
    i,
    n: o.n,
    type: o.type,
    lead: o.lead,
    day: o.day,
    mon: o.mon,
    cls: 'oc' + (sel.value === i ? ' on' : ''),
    st: added.value.includes(i) ? 'In plan' : o.type === 'Greeting' ? 'Free post' : 'Suggested',
    sc: added.value.includes(i) ? 'st ok' : o.type === 'Greeting' ? 'st' : 'st acc',
})));
const axis = Array.from({ length: 22 }, (_, i) => {
    const d = i - 14;
    return { n: d === 0 ? 'T' : d % 2 === 0 ? (d > 0 ? '+' : '') + d : '', c: d === 0 ? '#0F172A' : '#94A3B8', b: d === 0 ? '#F2A007' : 'transparent' };
});
const marks = computed(() => c.value.ph.map((p, i) => ({ ph: p[0], k: p[6], x: (((p[1] + 14 + 0.5) / 22) * 100).toFixed(2) + '%', op: offs.value.includes(i) ? 0.3 : 1, dl: i * 0.07 + 's' })));
const rows = computed(() => c.value.ph.map((p, i) => {
    const on = !offs.value.includes(i);
    return { i, on, ph: p[0], date: p[2], t: p[3], ch: p[4], cr: p[5] ? p[5] : 'free', k: p[6], op: on ? 1 : 0.5, sw: 'sw' + (on ? ' on' : '') };
}));
const addTxt = computed(() => (inPlan.value ? 'Update plan · ' : 'Add to plan · ') + total.value + ' cr');

function toggleRow(r) {
    off.value = Object.assign({}, off.value, { [sel.value]: r.on ? offs.value.concat(r.i) : offs.value.filter((x) => x !== r.i) });
}
function add() {
    if (!inPlan.value) added.value = added.value.concat(sel.value);
    toast.value = c.value.n + ' · ' + total.value + ' credits in your plan';
    clearTimeout(tt);
    tt = setTimeout(() => (toast.value = ''), 3000);
}
</script>

<template>
    <div class="wrap">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 360px">
                <span class="eyb"><b>OCCASIONS</b>·<span>{{ app.shop.city.toUpperCase() }} · MAHARASHTRA</span></span>
                <h1 class="h-lg">What’s coming, and how to sell around it</h1>
                <p class="ts muted">State and district calendar, checked every year because lunar dates move. Each occasion runs the same phases around its day T.</p>
            </div>
            <div class="seg"><button v-for="(n, i) in filterNames" :key="n" type="button" :class="filter === i ? 'on' : ''" @click="filter = i">{{ n }}</button></div>
            <button type="button" class="btn bs"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>Your own event</button>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
            <section class="panel rise d1" style="flex: 1 1 340px; min-width: 0; overflow: hidden">
                <div class="ph"><span class="eyb"><b>01</b>·<span>Next 60 days</span></span><span class="sp"></span><span class="mono muted">{{ count }}</span></div>
                <div v-if="none" class="pb"><p class="ts muted">No events of your own yet. Add an anniversary, a new branch or a 1,000th customer — it gets the same phases.</p></div>
                <button v-for="o in list" :key="o.i" type="button" :class="o.cls" @click="sel = o.i">
                    <span class="dt"><span style="font: 600 18px/1 var(--f); letter-spacing: -0.02em">{{ o.day }}</span><span class="mono" style="font-size: 10px; opacity: .7">{{ o.mon }}</span></span>
                    <span style="min-width: 0"><span class="h-sm" style="display: block">{{ o.n }}</span><span class="tx muted">{{ o.type }} · lead {{ o.lead }}</span></span>
                    <span :class="o.sc">{{ o.st }}</span>
                </button>
            </section>
            <section class="panel rise d2" style="flex: 2 1 560px; min-width: 0">
                <div class="ph" style="flex-wrap: wrap"><span class="eyb"><b>02</b>·<span>{{ c.type }}</span></span><span class="tag">T = {{ c.t }}</span><span class="sp"></span><span class="tag cr">{{ total }} cr</span></div>
                <div class="pb" style="display: flex; flex-direction: column; gap: 22px">
                    <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start">
                        <div style="flex: 1 1 300px; display: flex; flex-direction: column; gap: 8px"><h2 class="h-lg">{{ c.n }}</h2><p class="ts muted">{{ c.d }}</p></div>
                        <div class="receipt" style="flex: 1 1 240px">
                            <div class="ln" style="border-bottom: 1px solid #E2E8F0"><span class="muted">lead time</span><span>{{ c.lead }}</span></div>
                            <div class="ln" style="border-bottom: 1px solid #E2E8F0"><span class="muted">channels</span><span>{{ c.ch }}</span></div>
                            <div class="ln"><span class="muted">sample hook</span><span style="text-align: right">{{ c.hook }}</span></div>
                        </div>
                    </div>
                    <div style="position: relative; overflow: hidden; height: 112px; border-top: 1px solid #E2E8F0; border-bottom: 1px solid #E2E8F0; background: #F8FAFC" class="dots" aria-label="Phase timeline">
                        <div style="position: absolute; left: 0; right: 0; top: 0; display: grid; grid-template-columns: repeat(22, minmax(0, 1fr))">
                            <span v-for="(a, i) in axis" :key="i" class="mono" :style="{ fontSize: '9.5px', color: a.c, textAlign: 'center', paddingTop: '6px', borderLeft: `1px solid ${a.b}`, height: '112px' }">{{ a.n }}</span>
                        </div>
                        <div v-for="m in marks" :key="m.ph" class="mk" :style="{ left: m.x, opacity: m.op, animation: 'rise .5s var(--ease) both', animationDelay: m.dl }">
                            <span :class="['ty', m.k]" style="width: 14px; height: 14px"></span><span class="mono" style="font-size: 10.5px; white-space: nowrap; background: #fff; border: 1px solid #E2E8F0; border-radius: 5px; padding: 3px 6px">{{ m.ph }}</span>
                        </div>
                    </div>
                    <div class="tscroll">
                        <table class="tbl" style="border: 1px solid #E2E8F0; border-radius: 10px; border-collapse: separate; border-spacing: 0; overflow: hidden">
                            <thead><tr><th>Phase</th><th>Date</th><th>Piece</th><th>Where</th><th style="text-align: right">Credits</th><th style="width: 60px">On</th></tr></thead>
                            <tbody>
                                <tr v-for="r in rows" :key="r.i" :style="{ opacity: r.op }" @click="toggleRow(r)">
                                    <td><span style="display: inline-flex; gap: 8px; align-items: center"><span :class="['ty', r.k]"></span><b style="font-weight: 500">{{ r.ph }}</b></span></td>
                                    <td class="mono">{{ r.date }}</td>
                                    <td>{{ r.t }}</td>
                                    <td class="muted">{{ r.ch }}</td>
                                    <td class="mono" style="text-align: right">{{ r.cr }}</td>
                                    <td><span :class="r.sw"></span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center">
                        <div class="note" style="flex: 1 1 360px"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7.5 3v5.5c0 4.7-3.2 8.3-7.5 9.5-4.3-1.2-7.5-4.8-7.5-9.5V6z"></path></svg><span>“Last call” runs only if the offer truly continues. No selling around sad news, no political or religious debate.</span></div>
                        <button type="button" class="btn bp lg" @click="add">{{ addTxt }}</button>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <div v-if="toast" class="toast" role="status">{{ toast }}</div>
</template>

<style scoped>
.oc { display: grid; grid-template-columns: 52px minmax(0, 1fr) auto; gap: 14px; align-items: center; width: 100%; padding: 12px 16px; border: 0; border-bottom: 1px solid #E2E8F0; background: none; text-align: left; cursor: pointer; transition: background .15s; }
.oc:hover { background: #F8FAFC; }
.oc.on { background: #F1F5F9; }
.dt { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 52px; border-radius: 10px; border: 1px solid #E2E8F0; background: #fff; }
.oc.on .dt { background: #0F172A; color: #fff; border-color: #0F172A; }
.mk { position: absolute; top: 28px; transform: translateX(-50%); display: flex; flex-direction: column; align-items: center; gap: 6px; transition: opacity .3s; }
</style>
