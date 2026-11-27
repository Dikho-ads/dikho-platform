<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { useApp, inr } from '../stores/app';

const app = useApp();

const sel = ref(1);
const swapping = ref(false);
const opt = ref(0);
const approved = ref(false);
const toast = ref('');
const swapped = ref(0);
const items = ref({
    1: ['EMI offer reel', '15 s reel', 'video', 15], 3: ['Wedding gifts', 'AI-scene post', 'post', 6], 5: ['WhatsApp ad, 3 km', 'Click-to-WhatsApp ad', 'ad', 20], 8: ['Phones under ₹15k', 'Carousel', 'post', 5], 10: ['Win-back · 14', 'WhatsApp sequence', 'camp', 10], 12: ['Shaadi combo', 'AI-scene post', 'post', 6], 15: ['Phone safety tips', 'Social-cause reel', 'video', 15], 18: ['Customer story', 'Custom post', 'post', 2], 20: ['Christmas tease', 'AI-scene post', 'post', 6], 23: ['Year-end reel', '15 s reel', 'video', 15], 25: ['Christmas greeting', 'Daily free post', 'free', 0], 26: ['Clearance list', 'Carousel', 'post', 5], 28: ['Clearance ad', 'Click-to-WhatsApp ad', 'ad', 20], 31: ['New Year post', 'AI-scene post', 'post', 6],
});
let tt = 0;
onBeforeUnmount(() => clearTimeout(tt));

const wd = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const O = [['2 AI-scene posts + 1 WhatsApp offer', '6 + 6 + 3', 15, 'post', '2 EMI posts + offer'], ['1 carousel + 5 custom posts', '5 + 10', 15, 'post', 'EMI carousel + 5 posts'], ['1 AI-scene post + 1 flex banner', '6 + 8', 14, 'post', 'EMI post + flex']];
const types = [['video', 'Reels'], ['post', 'Posts'], ['ad', 'Ads'], ['camp', 'Campaigns']];
const rules = ['WhatsApp Status every open day — free', 'At most 2 offers a week to any customer', 'Nothing on Mondays, your weekly off', 'About 10% kept for surprises — rain, a cricket win'];
const legend = [['video', 'Video'], ['post', 'Posts'], ['ad', 'Ads'], ['camp', 'Campaigns'], ['free', 'Free']];

const dow = (d) => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][(d + 1) % 7];

const cells = computed(() => {
    const out = [{ d: 30, cls: 'cal x', has: false, off: false }];
    for (let d = 1; d <= 31; d++) {
        const it = items.value[d];
        out.push({ d, cls: 'cal', off: dow(d) === 'Mon', has: !!it, t: it ? it[0] : '', k: it ? it[2] : '', cr: it ? (it[3] ? it[3] + ' cr' : 'free') : '', ic: 'it' + (sel.value === d ? ' on' : '') + (swapped.value === d ? ' sw' : '') });
    }
    for (let d = 1; d <= 3; d++) out.push({ d, cls: 'cal x', has: false, off: false });
    return out;
});
const ledger = computed(() => types.map(([k, n]) => {
    const xs = Object.values(items.value).filter((x) => x[2] === k);
    return { k, n, c: xs.length, cr: xs.reduce((a, x) => a + x[3], 0) };
}).filter((l) => l.c));
const planned = computed(() => ledger.value.reduce((a, l) => a + l.cr, 0));
const buffer = computed(() => app.creditsTotal - planned.value);
const on = computed(() => (app.creditsTotal ? Math.round((planned.value / app.creditsTotal) * 50) : 0));
const ticks = computed(() => Array.from({ length: 50 }, (_, i) => ({ c: i < on.value ? 'on' : '', d: (0.2 + i * 0.012).toFixed(3) + 's', st: i >= on.value ? 'background: repeating-linear-gradient(135deg,#CBD5E1 0 1.5px,transparent 1.5px 3.5px)' : '' })));
const it = computed(() => items.value[sel.value]);
const cur = computed(() => (it.value ? it.value[3] : 0));
const selInfo = computed(() => (it.value ? { t: it.value[0], type: it.value[1], k: it.value[2], crTxt: it.value[3] ? it.value[3] + ' cr' : 'free', date: dow(sel.value) + ' ' + sel.value + ' Dec' } : { t: '', type: '', k: '', crTxt: '', date: '' }));
const opts = computed(() => O.map(([t, m, c], i) => {
    const n = c - cur.value;
    return { t, m: m + ' = ' + c, net: n === 0 ? 'same' : (n > 0 ? '+' : '') + n + ' cr', tc: 'tag' + (n === 0 ? ' dk' : ''), cls: 'opt' + (opt.value === i ? ' on' : '') };
}));
const stCls = computed(() => (approved.value ? 'st ok' : 'st warn'));
const stTxt = computed(() => (approved.value ? 'Approved · batches every Thursday' : 'Draft · approve by 30 Nov'));

function show(m) {
    toast.value = m;
    clearTimeout(tt);
    tt = setTimeout(() => (toast.value = ''), 3000);
}
function approve() {
    approved.value = true;
    show('December approved · week 1 batch arrives Sat 28 Nov');
}
function pick(d) {
    sel.value = d;
    swapping.value = false;
}
function openSwap() {
    swapping.value = true;
    opt.value = 0;
}
function apply() {
    const o = O[opt.value];
    items.value = Object.assign({}, items.value, { [sel.value]: [o[4], o[0], o[3], o[2]] });
    swapping.value = false;
    swapped.value = sel.value;
    show('Swapped · same price');
}
function remove() {
    const credits = it.value ? it.value[3] : 0;
    const ni = Object.assign({}, items.value);
    delete ni[sel.value];
    items.value = ni;
    show(credits + ' credits back in your buffer');
}
</script>

<template>
    <div class="wrap" style="max-width: 1320px">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 360px">
                <span class="eyb"><b>PLAN</b>·<span>DRAFTED 25 NOV · {{ app.pkg.name.toUpperCase() }}</span></span>
                <div style="display: flex; align-items: center; gap: 14px"><h1 class="h-lg">December 2026</h1><span :class="stCls">{{ stTxt }}</span></div>
            </div>
            <div class="seg"><button type="button">Nov</button><button type="button" class="on">Dec</button><button type="button">Jan</button></div>
            <button v-if="!approved" type="button" class="btn bp" @click="approve">Approve December</button>
            <span v-else class="btn bs" style="cursor: default"><span class="st ok">Approved</span></span>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
            <section class="panel rise d1" style="flex: 2.3 1 640px; min-width: 0; overflow: hidden">
                <div class="ph">
                    <span class="eyb"><b>01</b>·<span>Calendar</span></span><span class="sp"></span>
                    <span style="display: flex; gap: 14px" class="tx muted"><span v-for="l in legend" :key="l[0]" style="display: inline-flex; gap: 6px; align-items: center"><span :class="['ty', l[0]]"></span>{{ l[1] }}</span></span>
                </div>
                <div class="cal-scroll"><div class="cal-inner">
                <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border-bottom: 1px solid #E2E8F0; background: #F8FAFC"><span v-for="w in wd" :key="w" class="lm" style="padding: 9px 10px">{{ w }}</span></div>
                <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr))">
                    <div v-for="(c, i) in cells" :key="i" :class="c.cls">
                        <span style="display: flex; justify-content: space-between; align-items: center"><span class="mono" style="font-size: 12px">{{ c.d }}</span><span v-if="c.off" class="mono" style="font-size: 10px; color: #94A3B8">OFF</span></span>
                        <button v-if="c.has" type="button" :class="c.ic" @click="pick(c.d)">
                            <span style="display: flex; gap: 6px; align-items: center"><span :class="['ty', c.k]"></span><span class="mono" style="font-size: 10.5px; color: #64748B">{{ c.cr }}</span></span>
                            <span style="font: 500 12.5px/1.25 var(--f)">{{ c.t }}</span>
                        </button>
                    </div>
                </div>
                </div></div>
                <div class="row" style="border-top: 1px solid #E2E8F0"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true" style="color: #64748B"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5M12 8v.01"></path></svg><span class="tx muted">The free daily post on every open day isn’t shown. Week batches arrive 3 days early for a one-click look.</span></div>
            </section>
            <aside style="flex: 1 1 320px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
                <section class="panel rise d2">
                    <div class="ph"><span class="eyb"><b>02</b>·<span>{{ it ? 'Selected' : 'Nothing selected' }}</span></span></div>
                    <div v-if="it" class="pb fade" style="display: flex; flex-direction: column; gap: 14px">
                        <div style="display: flex; gap: 12px; align-items: flex-start"><span :class="['ty', selInfo.k]" style="width: 14px; height: 14px; margin-top: 3px"></span><span class="grow"><span class="h-md" style="display: block">{{ selInfo.t }}</span><span class="tx muted">{{ selInfo.date }} · {{ selInfo.type }}</span></span><span class="tag cr">{{ selInfo.crTxt }}</span></div>
                        <div v-if="!swapping" style="display: flex; gap: 8px"><button type="button" class="btn bs" style="flex: 1" @click="openSwap"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4L3 8l4 4M3 8h14M17 20l4-4-4-4M21 16H7"></path></svg>Swap</button><button type="button" class="btn bs" style="flex: 1" @click="remove">Remove</button></div>
                        <div v-else class="fade" style="display: flex; flex-direction: column; gap: 8px">
                            <span class="tx muted">Anything worth the same. Your {{ inr(app.pkg.price) }} never moves.</span>
                            <button v-for="(o, i) in opts" :key="o.t" type="button" :class="o.cls" @click="opt = i"><span class="rd"></span><span class="grow"><span class="ts" style="font-weight: 500; display: block">{{ o.t }}</span><span class="mono muted">{{ o.m }}</span></span><span :class="o.tc">{{ o.net }}</span></button>
                            <div style="display: flex; gap: 8px"><button type="button" class="btn bg" @click="swapping = false">Cancel</button><span class="sp"></span><button type="button" class="btn bp" @click="apply">Apply swap</button></div>
                        </div>
                    </div>
                    <div v-else class="pb"><p class="ts muted">Click any item on the calendar to swap or remove it.</p></div>
                </section>
                <section class="panel rise d3">
                    <div class="ph"><span class="eyb"><b>03</b>·<span>Credit receipt</span></span><span class="sp"></span><span class="mono muted">{{ app.pkg.name.toUpperCase() }} · {{ app.creditsTotal }}</span></div>
                    <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
                        <div class="ticks"><i v-for="(k, i) in ticks" :key="i" :class="k.c" :style="`animation-delay: ${k.d}; ${k.st}`"></i></div>
                        <div class="receipt">
                            <div v-for="l in ledger" :key="l.k" class="ln"><span style="display: inline-flex; gap: 8px; align-items: center"><span :class="['ty', l.k]"></span>{{ l.n }} × {{ l.c }}</span><span>{{ l.cr }} cr</span></div>
                            <div class="ln tot"><span>planned</span><span>{{ planned }} cr</span></div>
                            <div class="ln"><span class="muted">buffer for surprises</span><span>{{ buffer }} cr</span></div>
                            <div class="ln"><span class="muted">price this month</span><span>{{ inr(app.pkg.price) }} · fixed</span></div>
                        </div>
                    </div>
                </section>
                <section class="panel rise d4">
                    <div class="ph"><span class="eyb"><b>04</b>·<span>Rules it never breaks</span></span></div>
                    <div v-for="r in rules" :key="r" class="row" style="padding: 10px 18px"><span class="st ok"></span><span class="ts grow">{{ r }}</span></div>
                </section>
            </aside>
        </div>
    </div>
    <div v-if="toast" class="toast" role="status">{{ toast }}</div>
</template>

<style scoped>
.cal { min-height: 112px; padding: 8px; border-right: 1px solid #E2E8F0; border-bottom: 1px solid #E2E8F0; display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.cal:nth-child(7n) { border-right: 0; }
.cal.x { background: #F8FAFC; color: #94A3B8; }
.it { display: flex; flex-direction: column; gap: 5px; width: 100%; padding: 7px 8px; border: 1px solid #E2E8F0; border-radius: 8px; background: #fff; text-align: left; cursor: pointer; transition: border-color .15s, transform .2s var(--ease), box-shadow .15s; }
.it:hover { border-color: #94A3B8; transform: translateY(-1px); }
.it.on { border-color: #0F172A; box-shadow: inset 0 0 0 1px #0F172A; }
.it.sw { animation: rise .5s var(--ease) both; }
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
