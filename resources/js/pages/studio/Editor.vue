<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { storeToRefs } from 'pinia';
import { useApp } from '../../stores/app.js';

const app = useApp();
const { shop, user } = storeToRefs(app);

const tab = ref(0);
const lang = ref(0);
const tone = ref(0);
const adv = ref(true);
const short = ref(false);
const big = ref(false);
const festive = ref(true);
const land = ref(false);
const log = ref(['Tyohaar touch do - toran added']);
const custom = ref('');
const sc = ref(0);
const playing = ref(false);
const ab = ref(0);
const ok = ref([]);
const toastTxt = ref('');

let iv = null;
let tt = null;
onMounted(() => {
    iv = setInterval(() => {
        if (playing.value) sc.value = (sc.value + 1) % 5;
    }, 1500);
});
onBeforeUnmount(() => {
    clearInterval(iv);
    clearTimeout(tt);
});

const L = [
    { n: 'Hinglish', lc: 'en', hook: 'Purana phone laao, ₹2,000 extra pao', sh: '₹2,000 extra, purane phone pe', until: 'Sunday 29 Nov tak', terms: 'Exchange value as per phone condition',
        wa: ['Namaste {naam} ji! Shaadi season mein Sai Mobile ki taraf se: purana phone laaiye, ₹2,000 extra paaiye. Offer Sunday 29 Nov tak.', '{naam} ji, purane phone pe ₹2,000 extra. Sirf Sunday 29 Nov tak. Sai Mobile Point, College Road.', '{naam} ji, drawer mein pada purana phone ab ₹2,000 zyada ka hai! Sunday tak hi chalega.'],
        sms: 'SAIMOB: Purane phone pe Rs.2000 extra, Sun 29 Nov tak. Sai Mobile, College Rd. dikho.in/sai/2k', ad: 'Shaadi season special - purana phone laao, ₹2,000 extra pao. College Road, bus stand ke paas. Sunday tak.' },
    { n: 'हिन्दी', lc: 'hi', hook: 'पुराना फ़ोन लाओ, ₹2,000 एक्स्ट्रा पाओ', sh: 'पुराने फ़ोन पर ₹2,000 एक्स्ट्रा', until: 'रविवार 29 नवं तक', terms: 'एक्सचेंज वैल्यू फ़ोन की हालत के हिसाब से',
        wa: ['नमस्ते {naam} जी! शादी के सीज़न में पुराना फ़ोन लाइए, ₹2,000 एक्स्ट्रा पाइए। ऑफ़र रविवार 29 नवं तक।'], sms: 'SAIMOB: पुराने फ़ोन पर ₹2000 एक्स्ट्रा, रविवार तक। dikho.in/sai/2k', ad: 'शादी का सीज़न - पुराना फ़ोन लाओ, ₹2,000 एक्स्ट्रा पाओ। कॉलेज रोड, बस स्टैंड के पास।' },
    { n: 'मराठी', lc: 'mr', hook: 'जुना फोन आणा, ₹2,000 जास्त मिळवा', sh: 'जुन्या फोनवर ₹2,000 जास्त', until: 'रविवार 29 नोव्हें पर्यंत', terms: 'एक्सचेंज किंमत फोनच्या स्थितीनुसार',
        wa: ['नमस्कार {naam} जी! लग्नसराईत जुना फोन आणा, ₹2,000 जास्त मिळवा. ऑफर रविवार 29 नोव्हें पर्यंत.'], sms: 'SAIMOB: जुन्या फोनवर ₹2000 जास्त, रविवारपर्यंत. dikho.in/sai/2k', ad: 'लग्नसराई स्पेशल - जुना फोन आणा, ₹2,000 जास्त मिळवा. कॉलेज रोड, बस स्टँडजवळ.' },
    { n: 'English', lc: 'en', hook: 'Bring your old phone, get ₹2,000 extra', sh: '₹2,000 extra on your old phone', until: 'Till Sunday, 29 Nov', terms: 'Exchange value depends on phone condition',
        wa: ['Hello {naam}! This wedding season, bring your old phone and get ₹2,000 extra. Offer till Sunday, 29 Nov.'], sms: 'SAIMOB: Rs.2000 extra on your old phone till Sun 29 Nov. Sai Mobile, College Rd. dikho.in/sai/2k', ad: 'Wedding season special - bring your old phone, get ₹2,000 extra. College Road, near the bus stand.' },
];
const TABS = [['Poster', '4:5'], ['Status', '9:16'], ['Reel', '15 s'], ['Carousel', '5'], ['WhatsApp', 'msg'], ['SMS', 'DLT'], ['Google', 'post'], ['Ad', 'Meta']];
const SC = [
    ['0:00', 'Purana phone drawer mein?', 'पुराना फ़ोन घर में पड़ा है?', 'close-up: old phone in a drawer', 1.5],
    ['0:03', 'Naya 5G, stock mein', 'साई मोबाइल पर नए 5G फ़ोन आ गए हैं', 'your shelf of phones', 2],
    ['0:07', '₹2,000 extra', 'पुराना लाओ, दो हज़ार एक्स्ट्रा पाओ', 'Ramesh at the counter', 1.5],
    ['0:10', '★ 4.6 · 312 reviews', 'बारह साल का भरोसा', 'customer quote card', 1.5],
    ['0:13', 'College Road · Sunday tak', 'कॉलेज रोड - साई… साई मोबाइल!', 'shop front + sonic logo', 1],
];
const CAPS = ['Feed post · 1080 × 1350 · your signboard colours', 'WhatsApp Status · 1080 × 1920 · text kept in the safe zone', 'Reel · 15 s · captions burned in · ends on your sonic logo', 'Carousel · 5 cards · teaches how the exchange works', 'WhatsApp template · goes only to the 148 who said yes', 'SMS · DLT-registered header and template · STOP included', 'Google Business offer post · links to your dikho.in page', 'Meta ad · click-to-WhatsApp · 3 km · two versions tested'];
const SRC = ['feed', 'status', 'reel', 'carousel', 'whatsapp', 'sms', 'google', 'meta'];
const CARDS = [['₹2,000 extra kaise?', 'Swipe karke dekho', 'pst r45 y'], ['Purana phone laao', 'Koi bhi brand, chalu ho', 'pst r45'], ['5 minute mein jaanch', 'Saamne, aapki aankhon ke aage', 'pst r45 w'], ['Value + ₹2,000', 'Bill ke saath, likh ke', 'pst r45'], ['Naya 5G le jao', 'Sunday 29 Nov tak · College Road', 'pst r45 y']]
    .map(([t, s, pc], i) => ({ n: i + 1, t, s, pc, dl: i * 0.07 + 's' }));

const l = computed(() => L[lang.value]);
const hook = computed(() => custom.value || (short.value ? l.value.sh : l.value.hook));
const wa = computed(() => l.value.wa[Math.min(tone.value, l.value.wa.length - 1)]);
const hs = computed(() => (big.value ? '2em' : '2.2em'));
const ps = computed(() => (big.value ? '2.1em' : '1.45em'));
const scene = computed(() => ({ t: SC[sc.value][0], txt: SC[sc.value][1], shot: SC[sc.value][3] }));
const segs = computed(() => SC.map((z, i) => ({ f: z[4], c: i <= sc.value ? '#F2A007' : 'rgba(255,255,255,.3)' })));
const scenes = computed(() => SC.map((z, i) => ({ t: z[0], txt: z[1], vo: z[2], cls: 'sc' + (sc.value === i ? ' on' : ''), i })));
const playIcon = computed(() => (playing.value ? 'M8 5v14M16 5v14' : 'M7 4.5v15l13-7.5z'));
const abs = computed(() => [['A', l.value.hook], ['B', 'Shaadi gift? Naya 5G, purane ke badle']].map(([k, t], i) => ({ k, t, cls: 'opt' + (ab.value === i ? ' on' : ''), i })));
const dev = computed(() => /[ऀ-ॿ]/.test(l.value.sms));
const smsCount = computed(() => {
    const per = dev.value ? 70 : 160;
    const n = l.value.sms.length;
    return n + ' / ' + per + (dev.value ? ' · Unicode' : '') + ' · ' + Math.ceil(n / per) + ' SMS';
});
const bodyLabel = computed(() => (tab.value === 5 ? 'SMS text' : tab.value === 4 ? 'Message' : 'Caption'));
const body = computed(() => (tab.value === 5 ? l.value.sms : tab.value === 7 ? l.value.ad : wa.value));
const score = computed(() => (land.value ? 100 : 85));
const approveTxt = computed(() => (ok.value.length === 8 ? 'All 8 approved' : 'Approve all 8 formats'));
const beads = Array.from({ length: 16 }, (_, i) => {
    const x = 10 + i * 21.3;
    const y = 2 + 12 * (1 - Math.pow((x - 170) / 170, 2)) + 6;
    return { x: x.toFixed(1), y: y.toFixed(1), leaf: `M${x - 5} ${y - 6}l5 -5 5 5z` };
});
const qr = (() => {
    let seed = 5;
    const rnd = () => {
        seed = (seed * 9301 + 49297) % 233280;
        return seed / 233280;
    };
    let d = '';
    for (let y = 0; y < 21; y++) {
        for (let x = 0; x < 21; x++) {
            const f = (x < 8 && y < 8) || (x > 12 && y < 8) || (x < 8 && y > 12);
            if (!f && rnd() > 0.52) d += `M${x} ${y}h1v1h-1z`;
        }
    }
    [[0, 0], [14, 0], [0, 14]].forEach(([x, y]) => {
        d += `M${x} ${y}h7v7h-7zM${x + 1} ${y + 1}v5h5v-5zM${x + 2} ${y + 2}h3v3h-3z`;
    });
    return d;
})();

const levels = ['Simple', 'Advanced'];
const langNames = L.map((x) => x.n);
const toneNames = ['Apnapan', 'Seedhi', 'Mazedaar'];
const hashtags = ['#Nashik', '#CollegeRoad', '#ShaadiSeason', '#ExchangeOffer'];

function show(m) {
    toastTxt.value = m;
    clearTimeout(tt);
    tt = setTimeout(() => {
        toastTxt.value = '';
    }, 2800);
}
function addLog(m) {
    log.value = [m].concat(log.value).slice(0, 3);
}
const cmds = computed(() => [
    ['Thoda chhota karo', short.value, () => { addLog('Shorter hook'); short.value = !short.value; custom.value = ''; }],
    ['Marathi mein karo', lang.value === 2, () => { addLog('Switched to मराठी'); lang.value = 2; custom.value = ''; }],
    ['Aur apnapan', tone.value === 0, () => { addLog('Warmer tone'); tone.value = 0; }],
    ['Price bada dikhao', big.value, () => { addLog('Price made bigger'); big.value = !big.value; }],
    ['Tyohaar touch do', festive.value, () => { addLog(festive.value ? 'Toran removed' : 'Toran added'); festive.value = !festive.value; }],
    ['Landmark daalo', land.value, () => { addLog('Landmark added'); land.value = !land.value; }],
].map(([n, on, run]) => ({ n, on, run })));

function pickLang(i) {
    lang.value = i;
    custom.value = '';
}
function approveAll() {
    ok.value = [0, 1, 2, 3, 4, 5, 6, 7];
    show('8 formats approved · scheduled with the campaign');
}
function save() {
    show('Draft saved · v3');
}
function onHook(e) {
    custom.value = e.target.value;
}
</script>

<template>
    <div class="wrap" style="max-width: 1400px">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 420px">
                <span class="eyb"><b>IDEA</b>·<span>SHAADI EXCHANGE MELA · KHARIDARI · MADE BY AUTO MODE</span></span>
                <h1 class="h-lg" :lang="l.lc">{{ hook }}</h1>
            </div>
            <div class="seg" role="group" aria-label="Detail level">
                <button v-for="(n, i) in levels" :key="n" type="button" :class="(adv ? 1 : 0) === i ? 'on' : ''" @click="adv = i === 1">{{ n }}</button>
            </div>
            <button type="button" class="btn bs" @click="save">Save draft</button>
            <button type="button" class="btn bp" @click="approveAll">{{ approveTxt }}</button>
        </div>
        <div class="tabs rise d1" style="overflow-x: auto">
            <button v-for="(t, i) in TABS" :key="t[0]" type="button" :class="tab === i ? 'on' : ''" @click="tab = i">
                <span :class="ok.includes(i) ? 'st ok' : 'st'" style="font-size: 0; gap: 0"></span>{{ t[0] }}<span class="n">{{ t[1] }}</span>
            </button>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
            <aside class="panel rise d2" style="flex: 1 1 260px; max-width: 300px; min-width: 0">
                <div class="ph"><span class="eyb"><b>01</b>·<span>Ask AI · bol ke ya likh ke</span></span></div>
                <div class="pb" style="display: flex; flex-direction: column; gap: 16px">
                    <div class="iw">
                        <input class="in" placeholder="e.g. thoda chhota karo" aria-label="Tell AI what to change" />
                        <button type="button" class="btn ba ib sm" aria-label="Speak" style="right: 5px">
                            <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a3 3 0 0 1 3 3v5a3 3 0 0 1-6 0V6a3 3 0 0 1 3-3zM5 11a7 7 0 0 0 14 0M12 18v3"></path></svg>
                        </button>
                    </div>
                    <div class="chips">
                        <button v-for="c in cmds" :key="c.n" type="button" :class="'chip cmd' + (c.on ? ' on' : '')" @click="c.run">{{ c.n }}</button>
                    </div>
                    <div v-if="log.length" style="display: flex; flex-direction: column; gap: 6px">
                        <span v-for="(g, i) in log" :key="g + i" class="st ok fade" style="font-size: 12.5px">{{ g }}</span>
                    </div>
                    <div class="fld">
                        <span class="lm">Language · bhasha</span>
                        <div class="seg full">
                            <button v-for="(n, i) in langNames" :key="n" type="button" :class="lang === i ? 'on' : ''" @click="pickLang(i)">{{ n }}</button>
                        </div>
                    </div>
                    <div class="fld">
                        <span class="lm">Tone · andaaz</span>
                        <div class="seg full">
                            <button v-for="(n, i) in toneNames" :key="n" type="button" :class="tone === i ? 'on' : ''" @click="tone = i">{{ n }}</button>
                        </div>
                    </div>
                    <div class="receipt" style="border-top: 1px dashed #CBD5E1; padding-top: 6px">
                        <div class="ln"><span class="muted">offer</span><span>₹2,000 on exchange</span></div>
                        <div class="ln"><span class="muted">product</span><span>Vivo 5G phones</span></div>
                        <div class="ln"><span class="muted">for</span><span>families, students</span></div>
                        <div class="ln"><span class="muted">occasion</span><span>wedding season</span></div>
                        <div class="ln"><span class="muted">stage</span><span>Kharidari · buying</span></div>
                    </div>
                </div>
            </aside>

            <section style="flex: 2.1 1 480px; min-width: 0; display: flex; flex-direction: column; gap: 12px">
                <div class="stage dots rise d3" style="background-color: #F8FAFC">
                    <div v-if="tab === 0" class="pst r45 y" style="width: 340px; font-size: 12px; position: relative; box-shadow: 0 24px 48px -28px rgba(15,23,42,.45)">
                        <svg v-if="festive" class="toran fade" viewBox="0 0 340 22" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M0 2 Q170 14 340 2" stroke="#1D3A8A" stroke-width="1.5" fill="none"></path>
                            <g v-for="(b, i) in beads" :key="i"><path :d="b.leaf" fill="#1D3A8A"></path><circle :cx="b.x" :cy="b.y" r="4.2" fill="#E8820C"></circle></g>
                        </svg>
                        <div class="pad" style="flex: 1; justify-content: flex-end; padding-top: 2.4em">
                            <span class="badge" style="top: 2.2em">Shaadi season</span>
                            <span class="hk" :lang="l.lc" :style="{ fontSize: hs }">{{ hook }}</span>
                            <span class="pr" :style="{ fontSize: ps }">{{ l.until }}</span>
                            <span v-if="land" class="terms">College Road, near bus stand</span>
                            <span class="terms" :lang="l.lc">{{ l.terms }}</span>
                        </div>
                        <div class="pi" style="flex: 0 0 26%">{{ user.name }} with exchange phones · your photo</div>
                        <div class="ft"><b>{{ shop.name }}</b><span class="wa">WhatsApp</span></div>
                    </div>
                    <div v-if="tab === 1" class="pst r916" style="width: 250px; font-size: 10px">
                        <div class="pi">{{ user.name }} at counter · your photo</div>
                        <div class="pad">
                            <span class="hk" :lang="l.lc" :style="{ fontSize: hs }">{{ hook }}</span>
                            <span class="pr" :style="{ fontSize: ps }">{{ l.until }}</span>
                            <span class="terms" :lang="l.lc">{{ l.terms }}</span>
                        </div>
                        <div class="ft"><b>{{ shop.name }}</b><span>dikho.in/sai/2k</span></div>
                    </div>
                    <div v-if="tab === 2" style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap; justify-content: center">
                        <div style="display: flex; flex-direction: column; gap: 10px; align-items: center">
                            <div class="pst r916 k" style="width: 230px; font-size: 9px">
                                <div class="pi" style="flex: 1">{{ scene.shot }}</div>
                                <div style="position: absolute; left: 8%; right: 8%; bottom: 20%; display: flex; flex-direction: column; gap: .5em; align-items: center; text-align: center">
                                    <span v-for="z in [sc]" :key="z" class="hk fade" style="color: #fff">{{ scene.txt }}</span>
                                </div>
                                <div style="position: absolute; left: 8px; right: 8px; top: 8px; display: flex; gap: 3px">
                                    <span v-for="(g, i) in segs" :key="i" :style="{ flex: g.f, height: '3px', borderRadius: '2px', background: g.c, transition: 'background .3s' }"></span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center">
                                <button type="button" class="btn bp ib" :aria-label="playing ? 'Pause' : 'Play'" @click="playing = !playing">
                                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path :d="playIcon"></path></svg>
                                </button>
                                <span class="mono">{{ scene.t }} / 0:15</span>
                            </div>
                        </div>
                        <div class="panel" style="width: 300px; overflow: hidden">
                            <div class="ph" style="padding: 10px 12px; min-height: 0"><span class="lm">Script · 15 s · band-baaja brass 110 BPM</span></div>
                            <button v-for="z in scenes" :key="z.i" type="button" :class="z.cls" @click="sc = z.i">
                                <span class="mono" style="font-size: 11px">{{ z.t }}</span><span>{{ z.txt }}</span><span class="muted" lang="hi">{{ z.vo }}</span>
                            </button>
                        </div>
                    </div>
                    <div v-if="tab === 3" style="display: flex; gap: 10px; overflow-x: auto; max-width: 100%; padding: 4px">
                        <div v-for="c in CARDS" :key="c.n" :class="c.pc" :style="{ width: '150px', flexShrink: 0, fontSize: '7px', animation: 'rise .5s var(--ease) both', animationDelay: c.dl }">
                            <span class="badge">{{ c.n }} / 5</span>
                            <div class="pad" style="flex: 1; justify-content: flex-end"><span class="hk">{{ c.t }}</span><span class="sub">{{ c.s }}</span></div>
                            <div class="ft"><b>{{ shop.short }}</b><span>swipe →</span></div>
                        </div>
                    </div>
                    <div v-if="tab === 4" class="phone">
                        <div class="scr2" style="height: 470px">
                            <div style="display: flex; gap: 8px; align-items: center; padding: 10px 12px; background: #fff; border-bottom: 1px solid #E2E8F0">
                                <span class="mt sm">{{ shop.initials }}</span>
                                <span><span class="ts" style="font-weight: 600; display: block; line-height: 1.1">{{ shop.name }}</span><span class="tx muted">Business account</span></span>
                            </div>
                            <div style="padding: 12px; display: flex; flex-direction: column; gap: 8px; flex: 1">
                                <span class="mono" style="align-self: center; font-size: 10px; color: #64748B">SUN 29 NOV · 10:00</span>
                                <div class="msg">
                                    <div class="pst r45 y" style="width: 100%; font-size: 5.4px; border-radius: 0">
                                        <div class="pad" style="flex: 1; justify-content: flex-end"><span class="hk">{{ hook }}</span></div>
                                        <div class="ft"><b>{{ shop.short }}</b></div>
                                    </div>
                                    <p style="padding: 8px 10px" :lang="l.lc">{{ wa }}</p>
                                    <span class="mbtn">Offer dekho</span><span class="mbtn">Call karo</span><span class="mbtn" style="color: #64748B">Stop offers</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="tab === 5" class="phone">
                        <div class="scr2" style="height: 380px">
                            <div style="padding: 10px 12px; background: #fff; border-bottom: 1px solid #E2E8F0"><span class="mono" style="font-weight: 500">VM-SAIMOB</span></div>
                            <div style="padding: 14px 12px; display: flex; flex-direction: column; gap: 8px">
                                <div class="msg" style="padding: 9px 11px; background: #E2E8F0; border: 0" :lang="l.lc">{{ l.sms }}</div>
                                <span class="mono" style="font-size: 10.5px; color: #64748B">{{ smsCount }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="tab === 6" class="panel" style="width: 380px; overflow: hidden; box-shadow: 0 20px 40px -24px rgba(15,23,42,.3)">
                        <div style="display: flex; gap: 10px; align-items: center; padding: 12px 14px">
                            <span class="mt sm">{{ shop.initials }}</span>
                            <span><span class="ts" style="font-weight: 600; display: block">{{ shop.name }}</span><span class="tx muted">{{ shop.category }} · {{ shop.area }}, {{ shop.city }} · ★ 4.6</span></span>
                        </div>
                        <div class="pst r169 y" style="aspect-ratio: 16 / 9; border-radius: 0; font-size: 9px">
                            <div class="pad" style="flex: 1; justify-content: center"><span class="hk" :lang="l.lc">{{ hook }}</span><span class="pr">{{ l.until }}</span></div>
                        </div>
                        <div style="padding: 12px 14px; display: flex; flex-direction: column; gap: 10px">
                            <span class="tag" style="align-self: flex-start">OFFER · valid till 29 Nov</span>
                            <p class="ts" :lang="l.lc">{{ wa }}</p>
                            <span class="btn bs sm" style="align-self: flex-start">Learn more → dikho.in</span>
                        </div>
                    </div>
                    <div v-if="tab === 7" style="display: flex; gap: 18px; flex-wrap: wrap; justify-content: center; align-items: flex-start">
                        <div class="panel" style="width: 320px; overflow: hidden">
                            <div style="display: flex; gap: 10px; align-items: center; padding: 10px 12px">
                                <span class="mt sm">{{ shop.initials }}</span>
                                <span><span class="ts" style="font-weight: 600; display: block">{{ shop.name }}</span><span class="tx muted">Sponsored · 3 km around {{ shop.area }}</span></span>
                            </div>
                            <p class="ts" style="padding: 0 12px 10px" :lang="l.lc">{{ l.ad }}</p>
                            <div class="pst r45 y" style="border-radius: 0; font-size: 8.5px">
                                <div class="pad" style="flex: 1; justify-content: flex-end"><span class="hk" :lang="l.lc">{{ hook }}</span><span class="pr">{{ l.until }}</span></div>
                                <div class="ft"><b>{{ shop.name }}</b><span>{{ shop.area }}</span></div>
                            </div>
                            <div style="display: flex; align-items: center; padding: 10px 12px; gap: 8px; background: #F8FAFC">
                                <span class="grow"><span class="ts" style="font-weight: 600; display: block">₹2,000 extra on exchange</span><span class="tx muted">{{ shop.name }} · {{ shop.city }}</span></span>
                                <span class="btn bs sm">Send message</span>
                            </div>
                        </div>
                        <div class="panel" style="width: 240px; padding: 14px; display: flex; flex-direction: column; gap: 10px">
                            <span class="lm">A/B test · 50 / 50</span>
                            <button v-for="a in abs" :key="a.k" type="button" :class="a.cls" style="flex-direction: column; align-items: flex-start; gap: 4px" @click="ab = a.i">
                                <span class="mono">{{ a.k }}</span><span class="ts">{{ a.t }}</span>
                            </button>
                            <span class="tx muted">After 2 days the winner gets the rest of the budget.</span>
                        </div>
                    </div>
                </div>
                <p class="tx muted" style="text-align: center">{{ CAPS[tab] }}</p>
            </section>

            <aside style="flex: 1.1 1 290px; min-width: 0; display: flex; flex-direction: column; gap: 16px" class="rise d4">
                <section class="panel">
                    <div class="ph"><span class="eyb"><b>02</b>·<span>Words</span></span><span class="sp"></span><span class="tag">{{ TABS[tab][0].toUpperCase() }}</span></div>
                    <div class="pb" style="display: flex; flex-direction: column; gap: 12px">
                        <div class="fld"><label class="lab" for="hk">Hook · pehli line</label><input id="hk" class="in" :value="hook" :lang="l.lc" @change="onHook" /></div>
                        <div class="fld">
                            <label class="lab" for="bd">{{ bodyLabel }}</label>
                            <textarea id="bd" class="in" style="height: 92px; padding: 9px 11px; line-height: 1.45; resize: none; font-size: 13px" :lang="l.lc" :value="body"></textarea>
                            <span class="hint">Use <span class="mono">{naam}</span>, <span class="mono">{offer}</span>, <span class="mono">{link}</span> - filled for each customer.</span>
                        </div>
                        <div class="fld">
                            <label class="lab" for="cta">Button</label>
                            <select id="cta" class="in"><option>WhatsApp karo</option><option>Call karo</option><option>Dukaan ka raasta</option><option>Offer dekho</option></select>
                        </div>
                        <template v-if="adv">
                            <div class="fld fade"><span class="lab">Hashtags</span><div class="chips"><span v-for="h in hashtags" :key="h" class="chip">{{ h }}</span></div></div>
                            <div class="receipt fade" style="border-top: 1px dashed #CBD5E1; padding-top: 4px">
                                <div class="ln"><span class="muted">WhatsApp template</span><span>shaadi_exchange_v1 · Marketing · approved</span></div>
                                <div class="ln"><span class="muted">SMS header</span><span>SAIMOB · DLT approved</span></div>
                            </div>
                        </template>
                    </div>
                </section>
                <section class="panel">
                    <div class="ph"><span class="eyb"><b>03</b>·<span>Tracking via your shop page</span></span></div>
                    <div class="pb" style="display: grid; grid-template-columns: 84px minmax(0, 1fr); gap: 14px; align-items: center">
                        <svg viewBox="0 0 21 21" width="84" height="84" shape-rendering="crispEdges" role="img" aria-label="QR code for dikho.in/sai/2k"><rect width="21" height="21" fill="#fff"></rect><path :d="qr" fill="#0F172A"></path></svg>
                        <div style="display: flex; flex-direction: column; gap: 6px">
                            <span class="mono" style="font-size: 13px">dikho.in/sai/2k</span>
                            <span class="tx muted">Opens dikho.in/nashik/sai-mobile-point with this offer on top. Counts every click, scan and enquiry - and which locality it came from.</span>
                            <span v-if="adv" class="mono muted" style="font-size: 11px">src={{ SRC[tab] }} · camp=shaadi-mela</span>
                        </div>
                    </div>
                </section>
                <section class="panel">
                    <div class="ph"><span class="eyb"><b>04</b>·<span>Trust check</span></span><span class="sp"></span><span class="mono">{{ score }} / 100</span></div>
                    <div class="pb" style="display: flex; flex-direction: column; gap: 7px">
                        <span class="st ok">Real shop photo · real price</span>
                        <span class="st ok">Deadline is true: 29 Nov</span>
                        <span :class="land ? 'st ok' : 'st warn'">{{ land ? 'Landmark: near bus stand' : 'Add the landmark (+15)' }}</span>
                        <span class="st ok">Opt-in only for WhatsApp &amp; SMS</span>
                    </div>
                </section>
            </aside>
        </div>
    </div>
    <div v-if="toastTxt" class="toast" role="status">{{ toastTxt }}</div>
</template>

<style scoped>
.stage{border:1px solid #E2E8F0;border-radius:14px;min-height:560px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;padding:24px}
.phone{width:290px;border:1px solid #CBD5E1;border-radius:28px;background:#fff;padding:10px;box-shadow:0 20px 40px -24px rgba(15,23,42,.3)}
.scr2{border-radius:20px;background:#F1F5F9;overflow:hidden;display:flex;flex-direction:column}
.msg{align-self:flex-start;max-width:88%;background:#fff;border:1px solid #E2E8F0;border-radius:4px 14px 14px 14px;overflow:hidden;font-size:13px;line-height:19px}
.mbtn{display:block;text-align:center;padding:8px;border-top:1px solid #E2E8F0;font-weight:500;font-size:13px;color:#1D4ED8}
.sc{display:grid;grid-template-columns:62px 1fr 1fr;gap:10px;padding:8px 10px;border:0;border-bottom:1px solid #E2E8F0;background:none;text-align:left;cursor:pointer;font-size:12.5px;width:100%;transition:background .15s}
.sc.on{background:#F1F5F9}
.toran{position:absolute;left:0;right:0;top:0;height:22px;z-index:2}
.cmd{transition:all .15s}
</style>
