<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import { storeToRefs } from 'pinia';
import { useApp } from '../../stores/app.js';

const app = useApp();
const { shop, creditsTotal } = storeToRefs(app);

const rec = ref(false);
const now = reactive({ st: 3, br: 1, ig: 1, fb: 1, gb: 0, pr: 1 });
const nowEnq = ref(15);
const goal = ref(1);
const target = ref(60);
const dur = ref(1);
const aud = ref(['Families', 'College students']);
const areas = ref(['College Road', 'Gangapur Road', 'Panchavati']);
const langs = ref(['Hinglish', 'हिन्दी', 'मराठी']);
const prods = ref(['Vivo 5G phones', 'Exchange', 'Screen repair']);
const occ = ref(['wed', 'xmas', 'yend', 'sal']);
const credits = ref(Math.min(140, app.creditsTotal));
const wallet = ref(3000);
const ch = reactive({ st: 2, ig: 1, fb: 0, gb: 1, wa: 1, sms: 0, ad: 1, pr: 0 });
const chOff = ref(['pr']);
const sc = ref([3, 1, 1]);
const stage = ref('idle');
const gs = ref(0);

let iv = null;
onMounted(() => {
    iv = setInterval(() => {
        if (stage.value === 'making') {
            if (gs.value >= 5) stage.value = 'done';
            else gs.value += 1;
        }
    }, 550);
});
onBeforeUnmount(() => clearInterval(iv));

const NOW = [['st', 'WhatsApp Status'], ['br', 'WhatsApp groups / broadcast'], ['ig', 'Instagram'], ['fb', 'Facebook'], ['gb', 'Google listing'], ['pr', 'Pamphlets and flex']];
const FREQ = ['Never', 'Sometimes', 'Weekly', 'Daily'];
const G = [['FOOTFALL', 'More walk-ins', 'People at the counter'], ['LEADS', 'WhatsApp enquiries', 'Chats and calls'], ['REPEAT', 'Old customers back', 'Win-back and wishes'], ['LAUNCH', 'Launch something', 'New stock or service'], ['AWARENESS', 'Get known', 'New areas hear of you']];
const UNITS = ['walk-ins (QR scans) / month', 'enquiries / month', 'customers back / month', 'enquiries for the launch', 'people reached / month'];
const OCC = [['wed', 'Wedding season', 'NOW – 15 DEC'], ['xmas', 'Christmas → New Year', '20 – 31 DEC'], ['yend', 'Year-end clearance', '26 – 28 DEC'], ['sank', 'Makar Sankranti', '5 – 17 JAN'], ['sal', 'Salary week', '1 – 7 EVERY MONTH'], ['match', 'Cricket match days', 'AS SCHEDULED'], ['rain', 'First rain', 'WHEN IT HAPPENS']];
const CH = [['st', 'WhatsApp Status'], ['ig', 'Instagram'], ['fb', 'Facebook'], ['gb', 'Google Business'], ['wa', 'WhatsApp to customers'], ['sms', 'SMS'], ['ad', 'Meta ads'], ['pr', 'Print - flex, pamphlet']];
const SCALES = [['Formal', 'Apnapan'], ['Serious', 'Mazedaar'], ['Budget', 'Premium']];
const CH_FREQ = ['Low', 'Med', 'High'];
const AUD = ['Families', 'College students', 'Young workers', 'Farmers', 'Senior citizens', 'Shopkeepers'];
const AREAS = ['College Road', 'Gangapur Road', 'Panchavati', 'CIDCO', 'Indira Nagar', 'Satpur'];
const LANGS = ['Hinglish', 'हिन्दी', 'मराठी', 'English'];
const PRODS = ['Vivo 5G phones', 'Exchange', 'Screen repair', 'Earbuds', 'Covers & glass'];
const DURS = ['2 weeks', '1 month', '3 months'];

const f = (n) => '₹' + n.toLocaleString('en-IN');

function tog(listRef, n) {
    const a = listRef.value;
    listRef.value = a.includes(n) ? a.filter((x) => x !== n) : a.concat(n);
}

const unit = computed(() => UNITS[goal.value]);
const mult = computed(() => Math.round((target.value / nowEnq.value) * 10) / 10);
const stretch = computed(() => goal.value === 1 && mult.value >= 3);
const walletTxt = computed(() => f(wallet.value));
const walletNote = computed(() => (wallet.value === 0
    ? 'No ads - the plan will rely on Status, Google and your customer list.'
    : 'Suggested split: ' + f(Math.round(wallet.value * 0.8)) + ' Meta ads · ' + f(Math.round(wallet.value * 0.15)) + ' WhatsApp · ' + f(Math.round(wallet.value * 0.05)) + ' SMS'));
const ready = computed(() => {
    const filled = [1, 1, aud.value.length ? 1 : 0, prods.value.length ? 1 : 0, occ.value.length ? 1 : 0, 1, 1, 1];
    return Math.round((filled.reduce((a, b) => a + b, 0) / 8) * 100);
});
const rt = computed(() => Array.from({ length: 50 }, (_, i) => ({ c: i < ready.value / 2 ? 'on' : '', d: (i * 0.01).toFixed(2) + 's' })));
const summary = computed(() => [
    { k: 'today', v: nowEnq.value + ' enquiries / mo' },
    { k: 'goal', v: target.value + ' ' + UNITS[goal.value].split(' /')[0] },
    { k: 'for', v: aud.value.slice(0, 2).join(', ') || '-' },
    { k: 'where', v: areas.value.length + ' areas of ' + shop.value.city },
    { k: 'when', v: ['2 weeks', 'December', 'Dec – Feb'][dur.value] },
    { k: 'credits', v: credits.value + ' of ' + creditsTotal.value },
    { k: 'wallet', v: f(wallet.value) },
    { k: 'channels', v: 8 - chOff.value.length + ' of 8' },
]);
const pieces = computed(() => Math.round(credits.value / 3.3));
const GS = computed(() => ['Reading your brief', 'Checking ' + shop.value.city + ' calendar', 'Splitting budget across channels', 'Writing the 4-week calendar', 'Drafting content in ' + langs.value.length + ' languages', 'Done']);
const gsteps = computed(() => GS.value.map((n, i) => ({ n, sc: i < gs.value ? 'st ok' : i === gs.value ? 'st acc live' : 'st' })));

function nowEnqDec() {
    nowEnq.value = Math.max(0, nowEnq.value - 5);
}
function chanOn(k) {
    return !chOff.value.includes(k);
}
function setScale(i, e) {
    const a = sc.value.slice();
    a[i] = +e.target.value;
    sc.value = a;
}
function make() {
    stage.value = 'making';
    gs.value = 0;
}
</script>

<template>
    <div class="wrap" style="max-width: 1320px">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 460px">
                <span class="eyb"><b>PLAN MODE</b>·<span>अपना प्लान · BRIEF</span></span>
                <h1 class="h-lg">How do you market today - and what do you want next?</h1>
                <p class="ts muted">Fill what you know, skip the rest. AI writes the plan, the calendar and every piece of content from this.</p>
            </div>
            <div class="seg"><RouterLink :to="{ name: 'studio-auto' }">Auto</RouterLink><button type="button" class="on">Plan</button></div>
        </div>
        <div class="panel rise d1 dots" style="padding: 18px 20px; display: flex; flex-wrap: wrap; gap: 18px; align-items: center; background-color: #F8FAFC">
            <button type="button" :class="'btn ' + (rec ? 'ba' : 'bs')" style="height: 48px; border-radius: 12px" @click="rec = !rec">
                <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a3 3 0 0 1 3 3v5a3 3 0 0 1-6 0V6a3 3 0 0 1 3-3zM5 11a7 7 0 0 0 14 0M12 18v3"></path></svg>{{ rec ? 'Listening… tap to stop' : 'Pura brief bol ke batayein' }}
            </button>
            <p class="ts" style="flex: 1 1 420px; font-style: italic; color: #334155">“Abhi main sirf WhatsApp Status lagata hoon, mahine ka ₹1,500 Facebook pe kharch hota hai par log nahi aate. December mein shaadi season hai - 60 enquiries chahiye, aur purane customer bhi wapas aayein.”</p>
            <span class="st ok">Brief filled from this</span>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start">
            <div style="flex: 2.2 1 620px; min-width: 0; display: flex; flex-direction: column; gap: 18px">
                <section class="panel pb sec rise d2">
                    <div class="shead"><span class="eyb"><b>01</b>·<span>Abhi kya ho raha hai</span></span><span class="tx muted">Your marketing today</span></div>
                    <div>
                        <div v-for="[k, n] in NOW" :key="k" class="crow">
                            <span class="ts" style="font-weight: 500">{{ n }}</span>
                            <span class="seg">
                                <button v-for="(o, i) in FREQ" :key="o" type="button" :class="now[k] === i ? 'on' : ''" @click="now[k] = i">{{ o }}</button>
                            </span>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(220px, 100%), 1fr)); gap: 12px">
                        <div class="fld"><label class="lab" for="sp">Spend per month now</label><div class="iw"><span class="pre">₹</span><input id="sp" class="in" value="1,500" /></div></div>
                        <div class="fld">
                            <span class="lab">Enquiries per month now</span>
                            <div style="display: flex; gap: 8px; align-items: center">
                                <button type="button" class="btn bs ib sm" aria-label="Fewer" @click="nowEnqDec">−</button>
                                <span class="num" style="font-size: 22px; width: 44px; text-align: center">{{ nowEnq }}</span>
                                <button type="button" class="btn bs ib sm" aria-label="More" @click="nowEnq += 5">+</button>
                                <span class="tx muted">roughly</span>
                            </div>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(260px, 100%), 1fr)); gap: 12px">
                        <div class="fld"><label class="lab" for="w1">What worked</label><textarea id="w1" class="in" style="height: 64px; padding: 9px 11px; resize: none; line-height: 1.45">Exchange offers on Status bring calls. Customers trust my face.</textarea></div>
                        <div class="fld"><label class="lab" for="w2">What didn’t</label><textarea id="w2" class="in" style="height: 64px; padding: 9px 11px; resize: none; line-height: 1.45">Facebook boost gave likes, not customers. Pamphlets got thrown away.</textarea></div>
                    </div>
                </section>

                <section class="panel pb sec rise d3">
                    <div class="shead"><span class="eyb"><b>02</b>·<span>Kya chahiye</span></span><span class="tx muted">One main goal</span></div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(160px, 100%), 1fr)); gap: 10px">
                        <button v-for="(g, i) in G" :key="g[1]" type="button" :class="'goal' + (goal === i ? ' on' : '')" @click="goal = i">
                            <span class="mono muted" style="font-size: 10.5px">{{ g[0] }}</span><span class="h-sm">{{ g[1] }}</span><span class="tx muted">{{ g[2] }}</span>
                        </button>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center">
                        <span class="ts">Target</span>
                        <div style="display: flex; gap: 8px; align-items: center">
                            <button type="button" class="btn bs ib sm" aria-label="Lower target" @click="target = Math.max(10, target - 10)">−</button>
                            <span class="num" style="font-size: 26px; width: 56px; text-align: center">{{ target }}</span>
                            <button type="button" class="btn bs ib sm" aria-label="Higher target" @click="target += 10">+</button>
                        </div>
                        <span class="ts muted">{{ unit }}</span>
                        <span class="sp"></span>
                        <div class="seg">
                            <button v-for="(n, i) in DURS" :key="n" type="button" :class="dur === i ? 'on' : ''" @click="dur = i">{{ n }}</button>
                        </div>
                    </div>
                    <span v-if="stretch" class="st warn">That’s {{ mult }}× today. Doable with ads, but the plan will lean on your wallet.</span>
                </section>

                <section class="panel pb sec rise d4">
                    <div class="shead"><span class="eyb"><b>03</b>·<span>Kisko</span></span><span class="tx muted">Who and where</span></div>
                    <div class="fld"><span class="lab">People</span><div class="chips"><button v-for="n in AUD" :key="n" type="button" :class="'chip' + (aud.includes(n) ? ' on' : '')" @click="tog(aud, n)">{{ n }}</button></div></div>
                    <div class="fld"><span class="lab">Areas of {{ shop.city }}</span><div class="chips"><button v-for="n in AREAS" :key="n" type="button" :class="'chip' + (areas.includes(n) ? ' on' : '')" @click="tog(areas, n)">{{ n }}</button></div></div>
                    <div class="fld"><span class="lab">Languages</span><div class="chips"><button v-for="n in LANGS" :key="n" type="button" :class="'chip' + (langs.includes(n) ? ' on' : '')" @click="tog(langs, n)">{{ n }}</button></div></div>
                </section>

                <section class="panel pb sec rise d5">
                    <div class="shead"><span class="eyb"><b>04</b>·<span>Kya bechna hai</span></span><span class="tx muted">Products and offers</span></div>
                    <div class="chips"><button v-for="n in PRODS" :key="n" type="button" :class="'chip' + (prods.includes(n) ? ' on' : '')" @click="tog(prods, n)">{{ n }}</button></div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(240px, 100%), 1fr)); gap: 12px">
                        <div class="fld"><label class="lab" for="ho">Main offer</label><input id="ho" class="in" value="₹2,000 extra on exchange" /></div>
                        <div class="fld"><label class="lab" for="fl">Never advertise below</label><div class="iw"><span class="pre">₹</span><input id="fl" class="in" value="8,999" /></div><span class="hint">AI won’t promise a price lower than this.</span></div>
                    </div>
                </section>

                <section class="panel pb sec rise d6">
                    <div class="shead"><span class="eyb"><b>05</b>·<span>Kab</span></span><span class="tx muted">Occasions and moments</span></div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(220px, 100%), 1fr)); gap: 8px">
                        <button v-for="o in OCC" :key="o[0]" type="button" :class="'opt' + (occ.includes(o[0]) ? ' on' : '')" style="padding: 10px 12px" @click="tog(occ, o[0])">
                            <span class="ck"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg></span>
                            <span class="grow"><span class="ts" style="font-weight: 500; display: block">{{ o[1] }}</span><span class="mono muted" style="font-size: 11px">{{ o[2] }}</span></span>
                        </button>
                    </div>
                </section>

                <section class="panel pb sec rise d7">
                    <div class="shead"><span class="eyb"><b>06</b>·<span>Budget</span></span><span class="tx muted">Credits and wallet</span></div>
                    <div class="fld">
                        <span style="display: flex; justify-content: space-between"><label class="lab" for="cr">Credits to use</label><span class="mono">{{ credits }} of {{ creditsTotal }}</span></span>
                        <input id="cr" v-model.number="credits" type="range" min="60" :max="creditsTotal" step="10" aria-label="Credits to use" />
                    </div>
                    <div class="fld">
                        <span style="display: flex; justify-content: space-between"><label class="lab" for="wl">Ad &amp; message wallet</label><span class="mono">{{ walletTxt }}</span></span>
                        <input id="wl" v-model.number="wallet" type="range" min="0" max="10000" step="500" aria-label="Wallet budget" />
                        <span class="hint">{{ walletNote }}</span>
                    </div>
                </section>

                <section class="panel pb sec rise d8">
                    <div class="shead"><span class="eyb"><b>07</b>·<span>Kahan</span></span><span class="tx muted">Channels and how often</span></div>
                    <div>
                        <div v-for="[k, n] in CH" :key="k" class="crow">
                            <span style="display: flex; gap: 10px; align-items: center">
                                <button type="button" :class="'sw' + (chanOn(k) ? ' on' : '')" :aria-label="'Use ' + n" @click="tog(chOff, k)"></button>
                                <span class="ts" :style="{ fontWeight: 500, opacity: chanOn(k) ? 1 : 0.45 }">{{ n }}</span>
                            </span>
                            <span class="seg" :style="{ opacity: chanOn(k) ? 1 : 0.45 }">
                                <button v-for="(o, i) in CH_FREQ" :key="o" type="button" :class="ch[k] === i ? 'on' : ''" @click="ch[k] = i">{{ o }}</button>
                            </span>
                        </div>
                    </div>
                </section>

                <section class="panel pb sec rise d8">
                    <div class="shead"><span class="eyb"><b>08</b>·<span>Kaise bolna hai</span></span><span class="tx muted">Your voice</span></div>
                    <div v-for="(x, i) in SCALES" :key="x[0]" class="scale">
                        <span class="ts muted" style="text-align: right">{{ x[0] }}</span>
                        <input type="range" min="0" max="4" step="1" :value="sc[i]" :aria-label="x[0] + ' to ' + x[1]" @input="setScale(i, $event)" />
                        <span class="ts muted">{{ x[1] }}</span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(240px, 100%), 1fr)); gap: 12px">
                        <div class="fld"><span class="lab">Always say</span><div class="chips"><span class="chip on">bill ke saath</span><span class="chip on">bharosa</span><span class="chip on">Ramesh bhai</span><span class="chip">+ add</span></div></div>
                        <div class="fld"><span class="lab">Never say</span><div class="chips"><span class="chip on">sasta</span><span class="chip on">sabse kam daam</span><span class="chip">+ add</span></div></div>
                    </div>
                </section>
            </div>

            <aside style="flex: 1 1 300px; min-width: 0; display: flex; flex-direction: column; gap: 16px" class="rise d3">
                <section class="panel">
                    <div class="ph"><span class="eyb"><b>BRIEF</b>·<span>Summary</span></span><span class="sp"></span><span class="mono">{{ ready }}%</span></div>
                    <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
                        <div class="ticks sm"><i v-for="(k, i) in rt" :key="i" :class="k.c" :style="{ animationDelay: k.d }"></i></div>
                        <div class="receipt">
                            <div v-for="x in summary" :key="x.k" class="ln" style="border-bottom: 1px dashed #E2E8F0"><span class="muted">{{ x.k }}</span><span style="text-align: right">{{ x.v }}</span></div>
                        </div>
                        <div class="note">
                            <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6zM14 3v5h5"></path></svg>
                            <span>AI will write: a strategy, a 4-week calendar, about {{ pieces }} pieces in {{ langs.length }} languages, 3 workflows and 1 campaign. Nothing goes out until you approve.</span>
                        </div>
                        <button v-if="stage === 'idle'" type="button" class="btn bp lg full" @click="make">Generate my plan</button>
                        <div v-if="stage === 'making'" class="fade" style="display: flex; flex-direction: column; gap: 8px">
                            <span v-for="g in gsteps" :key="g.n" :class="g.sc" style="font-size: 12.5px">{{ g.n }}</span>
                        </div>
                        <RouterLink v-if="stage === 'done'" class="btn bp lg full rise" :to="{ name: 'studio-plan-result' }">
                            Open your plan<svg class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                        </RouterLink>
                    </div>
                </section>
                <section class="panel pb" style="display: flex; flex-direction: column; gap: 8px">
                    <span class="lm">Earlier plans</span>
                    <RouterLink class="row" :to="{ name: 'studio-plan-result' }" style="padding: 8px 0">
                        <span class="grow"><span class="ts" style="font-weight: 500; display: block">Shaadi season push</span><span class="tx muted">Dec · v2 · approved 20 Nov</span></span>
                        <span class="st ok">Live</span>
                    </RouterLink>
                    <div class="row" style="padding: 8px 0">
                        <span class="grow"><span class="ts" style="font-weight: 500; display: block">Diwali 2026</span><span class="tx muted">Oct–Nov · ended</span></span>
                        <span class="mono muted">52 enq</span>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.sec{display:flex;flex-direction:column;gap:14px}
.shead{display:flex;align-items:baseline;gap:10px}
.goal{display:flex;flex-direction:column;gap:6px;padding:14px;border:1px solid #E2E8F0;border-radius:12px;background:#fff;text-align:left;cursor:pointer;transition:border-color .15s,box-shadow .15s,transform .2s var(--ease)}
.goal:hover{transform:translateY(-2px);border-color:#CBD5E1}
.goal.on{border-color:#0F172A;box-shadow:inset 0 0 0 1px #0F172A}
.crow{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;padding:9px 0;border-bottom:1px dashed #E2E8F0}
@media (max-width:599px){.crow{grid-template-columns:minmax(0,1fr);gap:8px}.crow .seg{display:flex;width:100%}.crow .seg button{flex:1}}
input[type=range]{accent-color:#0F172A;width:100%}
.scale{display:grid;grid-template-columns:110px minmax(0,1fr) 110px;gap:12px;align-items:center}
</style>
