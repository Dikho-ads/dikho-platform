<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import { useApp } from '../stores/app';

const app = useApp();

const fmt = ref(0);
const stage = ref('ready');
const k = ref(0);
const ratio = ref(1);
const lang = ref(0);
const step = ref(0);
const recOn = ref(false);
const tick = ref(0);
const fix = reactive({ land: false, tag: false });
const ch = ref(['st', 'bc', 'ig', 'fb']);
const when = ref(1);
const toast = ref('');
let iv = 0;
let tt = 0;

onMounted(() => {
    iv = setInterval(() => {
        if (recOn.value) tick.value++;
        if (stage.value === 'making') {
            if (step.value >= 8) stage.value = 'ready';
            else step.value++;
        }
    }, 420);
});
onBeforeUnmount(() => {
    clearInterval(iv);
    clearTimeout(tt);
});

const F = [['Post', 2], ['AI-scene post', 6], ['Carousel · 5 cards', 5], ['15 s reel', 15], ['WhatsApp offer', 3]];
const HK = [
    ['Purana phone laao, ₹2,000 extra pao', 'पुराना फ़ोन लाओ, ₹2,000 एक्स्ट्रा पाओ', 'जुना फोन आणा, ₹2,000 जास्त मिळवा'],
    ['12 saal, hazaaron exchange. Ramesh bhai ka bharosa.', '12 साल, हज़ारों एक्सचेंज। रमेश भाई का भरोसा।', '12 वर्षं, हजारो एक्सचेंज. रमेश भाऊंचा विश्वास.'],
    ['College Road waalon, purana phone ghar pe pada hai?', 'कॉलेज रोड वालों, पुराना फ़ोन घर पे पड़ा है?', 'कॉलेज रोडकरांनो, जुना फोन घरी पडलाय?'],
];
const V = [
    { k: 'A', a: 'Offer', t: 95, pc: 'y', ph: 'Ramesh with phones', foot: 'WhatsApp', top: false, bottom: true, ps: 'flex: 1; justify-content: flex-end', why: 'leads with the real number and a deadline that is true. Best on sale days.' },
    { k: 'B', a: 'Trust', t: 92, pc: '', ph: 'Ramesh at counter', foot: '★ 4.6 · 312', top: true, bottom: false, ps: '', why: 'your face and 12 years do the selling — strong with people who fear fake exchange offers.' },
    { k: 'C', a: 'Local', t: 88, pc: 'w', ph: 'Vivo 5G on counter', foot: 'Near bus stand', top: false, bottom: true, ps: 'flex: 1', why: 'names College Road, so people nearby stop scrolling.' },
];
const R = [['9:16', 'r916'], ['4:5', 'r45'], ['1:1', 'r11']];
const PIPE = [['Brief', 'occasion, product, offer'], ['Angle', 'offer, trust, local — ranked'], ['Hook', 'first line, your words'], ['Message', 'one idea, one real price'], ['Visual', 'your photos, your template'], ['Sound', 'sonic logo on video'], ['Proof + CTA', 'rating, landmark, WhatsApp'], ['Gates', 'quality and trust']];
const CH = [['st', 'WA', 'WhatsApp Status', 'free · 24 h'], ['bc', 'WB', 'WhatsApp to customers', '148 opted in'], ['ig', 'IG', 'Instagram', 'feed + story'], ['fb', 'FB', 'Facebook Page', 'feed'], ['gb', 'GB', 'Google Business', 'offer post']];
const langNames = ['Hinglish', 'हिन्दी', 'मराठी'];
const langCodes = ['en', 'hi', 'mr'];
const whenNames = ['Now', '7:30 pm · best', 'Pick'];
const base = [4, 7, 12, 18, 10, 6, 9, 15, 20, 13, 8, 5, 8, 14, 19, 16, 10, 6, 11, 17, 21, 12, 8, 5, 9, 14, 7, 4, 6, 3];

// Decorative QR: seeded pattern, same every render.
const qr = (() => {
    let seed = 11;
    const rnd = () => {
        seed = (seed * 9301 + 49297) % 233280;
        return seed / 233280;
    };
    let d = '';
    for (let y = 0; y < 21; y++)
        for (let x = 0; x < 21; x++) {
            const f = (x < 8 && y < 8) || (x > 12 && y < 8) || (x < 8 && y > 12);
            if (!f && rnd() > 0.52) d += `M${x} ${y}h1v1h-1z`;
        }
    [[0, 0], [14, 0], [0, 14]].forEach(([x, y]) => {
        d += `M${x} ${y}h7v7h-7zM${x + 1} ${y + 1}v5h5v-5zM${x + 2} ${y + 2}h3v3h-3z`;
    });
    return d;
})();

const fmts = computed(() => F.map(([n, cr], i) => ({ n, cr: cr + ' cr', cls: 'opt' + (fmt.value === i ? ' on' : '') })));
const micCls = computed(() => (recOn.value ? 'ba' : 'bs'));
const micLabel = computed(() => (recOn.value ? 'Stop' : 'Speak'));
const wave = computed(() => base.map((h, i) => (recOn.value ? Math.max(3, Math.round(h * (0.35 + Math.abs(Math.sin((i + tick.value) * 0.8))))) : Math.max(3, Math.round(h * 0.3))) + 'px'));
const makeTxt = computed(() => 'Make 3 versions · ' + F[fmt.value][1] + ' cr');
const ready = computed(() => stage.value === 'ready');
const stageHead = computed(() => ({ empty: 'Versions', making: 'Making 3 versions…', ready: '3 versions · pick one', published: 'Done' }[stage.value]));
const pipe = computed(() => PIPE.map(([t, d], i) => ({ t, d, mark: i < step.value ? '✓' : String(i + 1), cls: 'pm' + (i < step.value ? ' d' : i === step.value ? ' c' : ''), c: i <= step.value ? '#0F172A' : '#94A3B8' })));
const ratios = computed(() => R.map(([n], i) => ({ n, cls: ratio.value === i ? 'on' : '' })));
const langs = computed(() => langNames.map((n, i) => ({ n, cls: lang.value === i ? 'on' : '' })));
const vars = computed(() => V.map((v, i) => ({
    ...v,
    hook: HK[i][lang.value],
    until: i === 1 ? '₹2,000 extra · till Sun' : 'Sunday 29 Nov tak',
    land: fix.land && i === k.value,
    pcls: 'pst ' + R[ratio.value][1] + (v.pc ? ' ' + v.pc : ''),
    cls: 'vc' + (k.value === i ? ' on' : ''),
    dl: i * 0.08 + 's',
})));
const C = computed(() => [['Real shop photo', 20, true], ['Proof · ★ 4.6', 15, true], ['Honest price', 15, true], ['Terms in one line', 15, true], ['Landmark', 15, fix.land, 'land'], ['No false urgency', 10, true], ['Tagline', 5, fix.tag, 'tag'], ['AI label · not needed', 5, true]]);
const score = computed(() => C.value.reduce((a, c) => a + (c[2] ? c[1] : 0), 0));
const off = computed(() => Math.round(170 - (170 * score.value) / 100));
const trustHead = computed(() => (score.value >= 95 ? 'Strong — ready to go' : 'Passes · 2 quick fixes'));
const checks = computed(() => C.value.map(([n, p, ok, key]) => ({ n, p: p + '', ok, fix: !ok, sc: ok ? 'st ok' : 'st warn', key })));
const chans = computed(() => CH.map(([id, m, n, sub]) => ({ id, m, n, s: sub, sw: 'sw' + (ch.value.includes(id) ? ' on' : '') })));
const fee = computed(() => (ch.value.includes('bc') ? '₹127.74' : '₹0'));
const pubTxt = computed(() => (when.value === 0 ? 'Publish now' : 'Schedule for 7:30 pm'));

function show(msg) {
    toast.value = msg;
    clearTimeout(tt);
    tt = setTimeout(() => (toast.value = ''), 3000);
}
function make() {
    if (!app.spend(F[fmt.value][1])) {
        show('Not enough credits for this version set');
        return;
    }
    stage.value = 'making';
    step.value = 0;
}
function toggleCh(id) {
    ch.value = ch.value.includes(id) ? ch.value.filter((x) => x !== id) : ch.value.concat(id);
}
function publish() {
    stage.value = 'published';
}
function again() {
    stage.value = 'empty';
    fix.land = false;
    fix.tag = false;
}
</script>

<template>
    <div style="display: flex; flex-wrap: wrap; flex: 1; align-items: stretch">
        <section style="flex: 1 1 300px; min-width: 0; padding: 22px; border-right: 1px solid #E2E8F0; display: flex; flex-direction: column; gap: 18px" class="rise">
            <span class="eyb"><b>01</b>·<span>Brief</span></span>
            <div style="display: flex; flex-direction: column; gap: 4px">
                <button v-for="(f, i) in fmts" :key="f.n" type="button" :class="f.cls" style="padding: 9px 12px" @click="fmt = i"><span class="rd"></span><span class="grow ts" style="font-weight: 500">{{ f.n }}</span><span class="tag cr">{{ f.cr }}</span></button>
            </div>
            <div class="panel" style="padding: 12px; display: flex; flex-direction: column; gap: 10px">
                <div style="display: flex; align-items: center; gap: 10px">
                    <button type="button" :class="['btn', micCls, 'ib']" :aria-label="micLabel" style="width: 40px; height: 40px; border-radius: 10px" @click="recOn = !recOn"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a3 3 0 0 1 3 3v5a3 3 0 0 1-6 0V6a3 3 0 0 1 3-3zM5 11a7 7 0 0 0 14 0M12 18v3"></path></svg></button>
                    <div style="display: flex; align-items: center; gap: 2px; height: 22px; flex: 1"><span v-for="(w, i) in wave" :key="i" :style="{ flex: 1, borderRadius: '2px', background: '#0F172A', height: w, transition: 'height .12s' }"></span></div>
                </div>
                <textarea class="in" style="height: 70px; padding: 9px 11px; line-height: 1.45; resize: none; font-size: 13px" aria-label="What you said">Shaadi season mein purana phone laao, exchange pe ₹2,000 extra. Sunday tak. Vivo pe.</textarea>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px">
                <span class="lm">We understood</span>
                <label class="kv"><span class="tx muted">Occasion</span><input class="in" value="Wedding season"></label>
                <label class="kv"><span class="tx muted">Product</span><input class="in" value="Vivo 5G phones"></label>
                <label class="kv"><span class="tx muted">Offer</span><input class="in" value="₹2,000 extra on exchange"></label>
                <label class="kv"><span class="tx muted">Valid till</span><input class="in" value="Sun 29 Nov"></label>
            </div>
            <button type="button" class="btn bp lg full" @click="make">{{ makeTxt }}</button>
        </section>
        <section class="dots" style="flex: 2.2 1 520px; min-width: 0; padding: 22px; display: flex; flex-direction: column; gap: 16px; background-color: #F8FAFC">
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px">
                <span class="eyb"><b>02</b>·<span>{{ stageHead }}</span></span><span class="sp"></span>
                <template v-if="ready">
                    <div class="seg"><button v-for="(r, i) in ratios" :key="r.n" type="button" :class="r.cls" @click="ratio = i">{{ r.n }}</button></div>
                    <div class="seg"><button v-for="(r, i) in langs" :key="r.n" type="button" :class="r.cls" @click="lang = i">{{ r.n }}</button></div>
                </template>
            </div>

            <div v-if="stage === 'empty'" class="fade" style="flex: 1; display: grid; place-items: center">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 16px; text-align: center">
                    <div style="display: grid; grid-template-columns: repeat(3, 44px); gap: 6px" aria-hidden="true"><span style="height: 44px; background: #0F172A; border-radius: 44px 0 0 0"></span><span style="height: 44px; background: #CBD5E1; border-radius: 50%"></span><span style="height: 44px; background: #F2A007; border-radius: 0 44px 0 0"></span></div>
                    <p class="h-md">Your 3 versions appear here</p>
                    <p class="ts muted" style="max-width: 320px">Each takes a different angle, so every post is also a small test.</p>
                </div>
            </div>

            <div v-if="stage === 'making'" class="fade" style="display: grid; grid-template-columns: minmax(0, 1fr) 260px; gap: 20px; flex: 1">
                <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; align-content: start"><div class="sk" style="aspect-ratio: 4/5"></div><div class="sk" style="aspect-ratio: 4/5; animation-delay: .2s"></div><div class="sk" style="aspect-ratio: 4/5; animation-delay: .4s"></div></div>
                <div class="panel" style="padding: 12px 16px">
                    <div v-for="p in pipe" :key="p.t" class="ps"><span :class="p.cls">{{ p.mark }}</span><span><span class="ts" :style="{ fontWeight: 500, display: 'block', color: p.c }">{{ p.t }}</span><span class="tx muted">{{ p.d }}</span></span></div>
                </div>
            </div>

            <div v-if="ready" style="display: flex; flex-direction: column; gap: 16px">
                <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; align-items: start">
                    <button v-for="(v, i) in vars" :key="v.k" type="button" :class="v.cls" :style="{ animation: 'rise .6s var(--ease) both', animationDelay: v.dl }" @click="k = i">
                        <span :class="v.pcls" style="font-size: 9.6px; width: 100%">
                            <span v-if="v.top" class="pi">{{ v.ph }}</span>
                            <span class="pad" :style="`gap: .55em; ${v.ps}`"><span class="hk" :lang="langCodes[lang]">{{ v.hook }}</span><span class="pr">{{ v.until }}</span><span v-if="v.land" class="terms">College Road, near bus stand</span></span>
                            <span v-if="v.bottom" class="pi" style="flex: 0 0 24%">{{ v.ph }}</span>
                            <span class="ft"><b>{{ app.shop.name }}</b><span>{{ v.foot }}</span></span>
                        </span>
                        <span style="display: flex; justify-content: space-between; align-items: center; width: 100%"><span class="ts" style="font-weight: 600">{{ v.k }} · {{ v.a }}</span><span class="mono muted">trust {{ v.t }}</span></span>
                    </button>
                </div>
                <div class="note" style="background: #fff"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3z"></path></svg><span><b>Why {{ V[k].k }}:</b> {{ V[k].why }}</span></div>
            </div>

            <div v-if="stage === 'published'" class="rise panel" style="padding: 28px; display: grid; grid-template-columns: minmax(0, 1fr) 140px; gap: 28px; align-items: center">
                <div style="display: flex; flex-direction: column; gap: 12px">
                    <span class="st ok live">Scheduled</span><h2 class="h-lg">Goes out today at 7:30 pm.</h2>
                    <p class="ts muted">Every size and both languages carry the offer code <span class="mono" style="color: #0F172A">SHAADI2K</span> and its own QR. Redemptions, chats and calls land in Results. Saved to your promise log with its terms.</p>
                    <div style="display: flex; gap: 8px"><RouterLink class="btn bp" :to="{ name: 'home' }">Back to dashboard</RouterLink><button type="button" class="btn bs" @click="again">Make another</button></div>
                </div>
                <svg viewBox="0 0 21 21" width="140" height="140" shape-rendering="crispEdges" role="img" aria-label="QR code for SHAADI2K"><rect width="21" height="21" fill="#fff"></rect><path :d="qr" fill="#0F172A"></path></svg>
            </div>
        </section>
        <section :style="{ flex: '1 1 300px', minWidth: 0, padding: '22px', borderLeft: '1px solid #E2E8F0', display: 'flex', flexDirection: 'column', gap: '18px', opacity: ready ? 1 : 0.45, transition: 'opacity .3s', pointerEvents: ready ? 'auto' : 'none' }">
            <span class="eyb"><b>03</b>·<span>Trust check</span></span>
            <div style="display: flex; gap: 14px; align-items: center">
                <svg viewBox="0 0 64 64" width="64" height="64" role="img" :aria-label="`Trust score ${score}`"><circle cx="32" cy="32" r="27" fill="none" stroke="#F1F5F9" stroke-width="6"></circle><circle cx="32" cy="32" r="27" fill="none" stroke="#0F172A" stroke-width="6" stroke-linecap="round" stroke-dasharray="170" :stroke-dashoffset="off" transform="rotate(-90 32 32)" style="transition: stroke-dashoffset .8s cubic-bezier(.2,.7,.2,1)"></circle><text x="32" y="38" text-anchor="middle" font-family="Geist" font-weight="600" font-size="17" fill="#0F172A">{{ score }}</text></svg>
                <div><p class="h-sm">{{ trustHead }}</p><p class="tx muted">Publishes at 70+. False urgency blocks at any score.</p></div>
            </div>
            <div style="display: flex; flex-direction: column">
                <div v-for="c in checks" :key="c.n" style="display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 8px; align-items: center; padding: 6px 0; border-bottom: 1px dashed #E2E8F0">
                    <span :class="c.sc" style="font-size: 12.5px">{{ c.n }}</span>
                    <button v-if="c.fix" type="button" class="btn bs sm" style="height: 24px" @click="fix[c.key] = true">Fix</button>
                    <span v-if="c.ok" class="mono muted" style="font-size: 11px">{{ c.p }}</span>
                </div>
            </div>
            <span class="eyb" style="margin-top: 4px"><b>04</b>·<span>Publish</span></span>
            <div class="panel">
                <button v-for="c in chans" :key="c.id" type="button" class="row" style="padding: 9px 12px" @click="toggleCh(c.id)"><span class="mt sm lt">{{ c.m }}</span><span class="grow"><span class="ts" style="font-weight: 500; display: block">{{ c.n }}</span><span class="tx muted">{{ c.s }}</span></span><span :class="c.sw"></span></button>
            </div>
            <div class="seg full"><button v-for="(n, i) in whenNames" :key="n" type="button" :class="when === i ? 'on' : ''" @click="when = i">{{ n }}</button></div>
            <div class="receipt"><div class="ln"><span class="muted">credits</span><span>{{ F[fmt][1] }} cr</span></div><div class="ln"><span class="muted">wallet · messages</span><span>{{ fee }}</span></div></div>
            <button type="button" class="btn bp lg full" @click="publish">{{ pubTxt }}</button>
        </section>
    </div>
    <div v-if="toast" class="toast" role="status">{{ toast }}</div>
</template>

<style scoped>
.kv { display: grid; grid-template-columns: 84px minmax(0, 1fr); gap: 10px; align-items: center; }
.kv .in { height: 34px; font-size: 13px; }
.vc { display: flex; flex-direction: column; gap: 10px; padding: 10px; border: 1px solid #E2E8F0; border-radius: 12px; background: #fff; cursor: pointer; text-align: left; transition: border-color .2s, box-shadow .2s, transform .25s var(--ease); }
.vc:hover { transform: translateY(-2px); border-color: #CBD5E1; }
.vc.on { border-color: #0F172A; box-shadow: inset 0 0 0 1px #0F172A; }
.sk { border-radius: 10px; background: #E2E8F0; animation: breathe 1.6s ease-in-out infinite; }
.ps { display: grid; grid-template-columns: 22px minmax(0, 1fr); gap: 10px; align-items: center; padding: 7px 0; }
.pm { width: 20px; height: 20px; border-radius: 6px; display: grid; place-items: center; font: 500 10px var(--fm); box-shadow: inset 0 0 0 1px #CBD5E1; background: #fff; color: #64748B; }
.pm.d { background: #0F172A; color: #fff; box-shadow: none; }
.pm.c { box-shadow: inset 0 0 0 1.5px #0F172A; color: #0F172A; }
</style>
