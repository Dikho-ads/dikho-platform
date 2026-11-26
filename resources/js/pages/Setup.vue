<script setup>
import { ref, computed, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useApp, PACKAGES, inr } from '@/stores/app';
import Logo from '@/components/ui/Logo.vue';

const app = useApp();
const router = useRouter();

const S = [
    ['Shop basics', 'Shop', 'Tell us about your shop', 'Picks your templates, tone and category playbook.'],
    ['Location', 'Where', 'Where is the shop?', 'Sets your local festivals, language and how far ads reach.'],
    ['Contact & hours', 'Reach', 'How customers reach you', 'Becomes the button on every post. No posts when you’re closed.'],
    ['What you sell', 'Catalogue', 'What do you sell most?', 'Real products and real prices go into your ads - never stock photos.'],
    ['Who buys', 'Audience', 'Who buys from you?', 'Shapes the hooks, the languages and who sees your ads.'],
    ['Look', 'Brand', 'Your shop’s look', 'Colours come from your signboard, so every post looks like your shop.'],
    ['Photos', 'Proof', 'Five photos of the real shop', 'Click a slot to open the guided camera. Real faces build trust.'],
    ['Goal', 'Focus', 'What do you want most right now?', 'Pick 1 or 2. Your monthly plan leans towards these.'],
    ['Customers', 'Optional', 'Bring your regular customers', 'So we can thank them - and notice when someone stops coming.'],
    ['Plan', 'Price', 'Pick a plan', 'One fixed price. Mix anything inside your credits.'],
];
const CATS = [
    ['Kirana', 'M4 9h16l-1.5 10.5a1 1 0 0 1-1 .5h-11a1 1 0 0 1-1-.5zM8 9l4-5 4 5'],
    ['Mobile & electronics', 'M8.5 2.5h7A2.5 2.5 0 0 1 18 5v14a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 6 19V5a2.5 2.5 0 0 1 2.5-2.5zM11 18.5h2'],
    ['Salon', 'M8.5 6a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0zM8.5 18a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0zM8 7.5L20 18M8 16.5L20 6'],
    ['Sweet shop', 'M3 9h18v11H3zM3 9l3-5h12l3 5M12 9v11'],
    ['Clinic', 'M6.5 3.5h11a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3h-11a3 3 0 0 1-3-3v-11a3 3 0 0 1 3-3zM12 8v8M8 12h8'],
    ['Coaching centre', 'M4 5a2 2 0 0 1 2-2h13v15H6a2 2 0 0 0-2 2zM4 20a2 2 0 0 0 2 1h13'],
];
const RAD = [['None', 0], ['1 km', 30], ['2 km', 52], ['3 km', 74], ['5 km', 104]];
const WEEK = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const TYPES = ['College students', 'Young workers', 'Families', 'Farmers', 'Senior citizens', 'Other shopkeepers'];
const SPOKEN = ['हिन्दी', 'मराठी', 'English', 'Hinglish'];
const HUBS = ['College', 'Bus stand', 'Mandi', 'Hospital', 'Temple', 'Market'];
const FEELS = ['Bold', 'Classic', 'Friendly'];
const STRETCH = ['80%', '100%', '112%'];
const G = [
    ['walk', 'More walk-ins', 'Offer posts and 3 km ads'],
    ['chat', 'More WhatsApp orders', 'Catalogue and order buttons'],
    ['back', 'Bring back old customers', 'Personal win-back offers'],
    ['new', 'Launch something new', 'Tease, launch, build'],
];
const SRCS = [
    ['Phone contacts', 'Tagged as customers'],
    ['Billing / khata app', 'Knows last visits'],
    ['Excel or CSV', 'Name, phone, last visit'],
];
const TAGS = [
    ['Sai Mobile - bharose ka naam', 'Name + promise'],
    ['Nashik ka apna mobile ghar', 'Place pride'],
    ['30 minute repair, pakka', 'Number promise'],
];
const SL = [
    ['Shop front', 'front'],
    ['Inside', 'inside'],
    ['You at the counter', 'Ramesh'],
    ['Best seller', 'product'],
    ['Second product', 'product'],
];
const SWATCHES = ['#1D3A8A', '#FFD23F', '#FFFFFF'];

const step = ref(0);
const done = ref(false);
const name = ref(app.shop.name);
const cat = ref(CATS.findIndex((c) => c[0] === app.shop.category) >= 0 ? CATS.findIndex((c) => c[0] === app.shop.category) : 1);
const years = ref(12);
const landmark = ref('Near bus stand');
const rad = ref(2);
const same = ref(true);
const off = ref('Mon');
const items = ref([
    ['Vivo 5G phones', '₹10,000 – 20,000'],
    ['Screen replacement', '₹1,200 – 4,500'],
    ['Earbuds', '₹999 – 2,499'],
    ['Covers & glass', '₹149 – 499'],
]);
const picked = reactive({ types: ['College students', 'Families'], langs: ['मराठी', 'हिन्दी'], hubs: ['College', 'Bus stand'] });
const swapped = ref(false);
const feel = ref(0);
const shots = ref(3);
const goals = ref(['walk', 'back']);
const src = ref(0);
const planId = ref(app.packageId);
const tag = ref(0);

const st = computed(() => step.value);
const cur = computed(() => ({ e: S[st.value][1], t: S[st.value][2], d: S[st.value][3] }));
const num = computed(() => String(st.value + 1).padStart(2, '0'));
const since = computed(() => 2026 - years.value);
const bars = computed(() => Array.from({ length: 10 }, (_, i) => (done.value || i < st.value ? '#0F172A' : i === st.value ? '#F2A007' : '#E2E8F0')));
const progress = computed(() => (done.value ? 'DONE' : 'STEP ' + (st.value + 1) + ' / 10'));
const steps = computed(() =>
    S.map(([n], i) => ({
        n,
        opt: i === 8,
        mark: done.value || i < st.value ? '✓' : String(i + 1).padStart(2, '0'),
        cls: 'stp' + (done.value || i < st.value ? ' done' : '') + (!done.value && i === st.value ? ' on' : ''),
    })),
);
const plan = computed(() => PACKAGES.find((p) => p.id === planId.value) || PACKAGES[1]);
const planPer = (p) => (p.price === 0 ? '' : '/mo');
const nextLabel = computed(() => (st.value === 9 ? (plan.value.price === 0 ? 'Start free' : 'Set up UPI AutoPay') : 'Continue'));
const slots = computed(() =>
    SL.map(([n, ph], i) => {
        const ok = i < shots.value;
        return { n, ph: ok ? ph : 'click to shoot', cls: ok ? 'ph-x' : '', st: ok ? 'st ok' : 'st', s: ok ? 'done' : 'empty', i };
    }),
);
const pcls = computed(() => 'pst r45' + (swapped.value ? ' y' : ''));
const facts = computed(() => [
    { k: 'kind', v: CATS[cat.value][0] },
    { k: 'delivery', v: RAD[rad.value][0] === 'None' ? 'no delivery' : RAD[rad.value][0] },
    { k: 'weekly off', v: off.value || 'open all week' },
    { k: 'posts in', v: picked.langs.slice(0, 2).join(' + ') || 'हिन्दी' },
    { k: 'goal', v: G.filter((g) => goals.value.includes(g[0])).map((g) => g[1].split(' ').slice(-2).join(' ')).join(', ') || '-' },
    { k: 'plan', v: plan.value.name + ' · ' + inr(plan.value.price) + planPer(plan.value) },
]);

const tog = (list, v) => {
    const i = list.indexOf(v);
    if (i >= 0) list.splice(i, 1);
    else list.push(v);
};
const toggleGoal = (id) => {
    goals.value = goals.value.includes(id) ? goals.value.filter((x) => x !== id) : goals.value.concat(id).slice(-2);
};
const addItem = () => {
    if (items.value.length < 5) items.value.push(['Power banks', '₹800 – 2,000']);
};
const takeShot = (i) => {
    if (i >= shots.value) shots.value = Math.max(shots.value, i + 1);
};
const next = () => (st.value === 9 ? (done.value = true) : (step.value = st.value + 1));
const prev = () => (step.value = Math.max(0, st.value - 1));
const restart = () => {
    done.value = false;
    step.value = 0;
};
const goStep = (i) => {
    step.value = i;
    done.value = false;
};

const initialsOf = (s) =>
    s
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join('');

function openDashboard() {
    const n = name.value.trim() || app.shop.name;
    app.finishSetup({
        shop: {
            name: n,
            short: n.split(/\s+/).slice(0, 2).join(' '),
            initials: initialsOf(n),
            city: app.shop.city,
            area: app.shop.area,
            category: CATS[cat.value][0],
            whatsapp: app.shop.whatsapp,
        },
        packageId: planId.value,
    });
    router.push({ name: 'home' });
}
</script>

<template>
<div class="app" style="flex-direction: column; flex-wrap: nowrap">
<header class="bar" style="padding: 10px 28px">
<span class="brand" style="padding: 0"><Logo /></span>
<span class="crumb" style="margin-left: 8px">/ <b>Shop setup</b></span>
<span class="sp"></span>
<div style="display: grid; grid-template-columns: repeat(10, 22px); gap: 3px" aria-hidden="true"><span v-for="(b, i) in bars" :key="i" :style="{ height: '4px', borderRadius: '2px', background: b, transition: 'background .4s' }"></span></div>
<span class="mono muted">{{ progress }}</span>
<button class="btn bg sm" type="button" @click="openDashboard">Finish later</button>
</header>
<div style="display: flex; flex-wrap: wrap; flex: 1">
<aside style="flex: 1 1 230px; max-width: 280px; padding: 22px 14px; border-right: 1px solid #E2E8F0; background: #F8FAFC; display: flex; flex-direction: column; gap: 2px">
<span class="lm" style="padding: 0 10px 10px">10 steps · about 3 min</span>
<button v-for="(s, i) in steps" :key="i" type="button" :class="s.cls" @click="goStep(i)"><span class="sq">{{ s.mark }}</span><span>{{ s.n }}</span><span v-if="s.opt" class="mono" style="font-size: 10px; color: #94A3B8">opt</span></button>
</aside>
<main style="flex: 3 1 480px; min-width: 0; padding: 32px 40px; display: flex; flex-direction: column; gap: 24px">
<template v-if="!done">
<div style="display: flex; flex-direction: column; gap: 8px"><span class="eyb"><b>{{ num }}</b>·<span>{{ cur.e }}</span></span><h1 class="h-lg">{{ cur.t }}</h1><p class="ts muted">{{ cur.d }}</p></div>

<div v-if="st === 0" class="rise" style="display: flex; flex-direction: column; gap: 18px">
<div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px"><div class="fld"><label class="lab" for="n1">Name on the signboard</label><input id="n1" v-model="name" class="in"></div><div class="fld"><label class="lab" for="n2">In your script</label><input id="n2" class="in" lang="hi" value="साई मोबाइल पॉइंट"></div></div>
<div class="fld"><span class="lab">Kind of shop</span><div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px"><button v-for="(c, i) in CATS" :key="c[0]" type="button" :class="'opt' + (cat === i ? ' on' : '')" @click="cat = i"><span class="mt sm lt"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path :d="c[1]"></path></svg></span><span class="ts" style="font-weight: 500">{{ c[0] }}</span></button></div></div>
<div class="panel" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px"><span class="grow"><span class="h-sm" style="display: block">Years running</span><span class="tx muted">Becomes a “since {{ since }}” trust badge</span></span><button type="button" class="btn bs ib sm" aria-label="Fewer years" @click="years = Math.max(0, years - 1)">−</button><span class="num" style="font-size: 22px; width: 32px; text-align: center">{{ years }}</span><button type="button" class="btn bs ib sm" aria-label="More years" @click="years += 1">+</button></div>
</div>

<div v-if="st === 1" class="rise" style="display: flex; flex-direction: column; gap: 18px">
<div class="panel" style="overflow: hidden"><svg viewBox="0 0 640 220" width="100%" style="display: block; background: #F1F5F9" role="img" :aria-label="'Map, ' + RAD[rad][0] + ' around the shop'"><path d="M-10 140 C 160 120, 330 160, 660 110" stroke="#fff" stroke-width="18" fill="none"></path><path d="M250 -10 L 300 240" stroke="#fff" stroke-width="12" fill="none"></path><path d="M470 -10 C 450 80, 500 160, 440 240" stroke="#fff" stroke-width="8" fill="none"></path><path d="M-10 46 L 660 70" stroke="#fff" stroke-width="6" fill="none"></path><circle cx="300" cy="132" :r="RAD[rad][1]" fill="rgba(15,23,42,.06)" stroke="#0F172A" stroke-dasharray="4 5" style="transition: r .5s cubic-bezier(.2,.7,.2,1)"></circle><circle cx="300" cy="132" r="7" fill="#F2A007" stroke="#0F172A" stroke-width="2.5"></circle><text x="16" y="128" font-family="Geist Mono" font-size="11" fill="#64748B">COLLEGE RD</text><text x="312" y="208" font-family="Geist Mono" font-size="11" fill="#64748B">BUS STAND</text></svg>
<div class="row"><span class="grow"><span class="h-sm" style="display: block">{{ app.shop.area }}, {{ app.shop.city }}</span><span class="tx muted">Nashik district · Maharashtra</span></span><button type="button" class="btn bs sm">Use my location</button></div></div>
<div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px"><div class="fld"><label class="lab" for="lm">Landmark customers know</label><input id="lm" v-model="landmark" class="in"></div><div class="fld"><span class="lab">Delivery</span><div class="seg full"><button v-for="(x, i) in RAD" :key="x[0]" type="button" :class="rad === i ? 'on' : ''" @click="rad = i">{{ x[0] }}</button></div></div></div>
</div>

<div v-if="st === 2" class="rise" style="display: flex; flex-direction: column; gap: 18px">
<div class="panel"><div class="row"><span class="mt">WA</span><span class="grow"><span class="h-sm" style="display: block">WhatsApp</span><span class="mono muted">{{ app.shop.whatsapp }}</span></span><span class="st ok">Verified</span></div><button type="button" class="row" @click="same = !same"><span class="mt lt">CL</span><span class="grow"><span class="h-sm" style="display: block">Calls on the same number</span><span class="tx muted">Turn off to add a landline</span></span><span :class="'sw' + (same ? ' on' : '')"></span></button></div>
<div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px"><div class="fld"><label class="lab" for="op">Opens</label><select id="op" class="in"><option>9:00 am</option><option selected>10:00 am</option></select></div><div class="fld"><label class="lab" for="cl">Closes</label><select id="cl" class="in"><option selected>9:00 pm</option><option>10:00 pm</option></select></div></div>
<div class="fld"><span class="lab">Weekly off</span><div class="chips"><button v-for="d in WEEK" :key="d" type="button" :class="'chip' + (off === d ? ' on' : '')" @click="off = off === d ? '' : d">{{ d }}</button></div><span class="hint">Nothing is posted on your off day unless a festival falls on it.</span></div>
</div>

<div v-if="st === 3" class="rise" style="display: flex; flex-direction: column; gap: 18px">
<div class="panel"><div v-for="(it, i) in items" :key="it[0] + i" class="row"><span class="ph-x" style="width: 40px; height: 40px; border-radius: 8px; font-size: 8px">img</span><span class="grow"><span class="h-sm" style="display: block">{{ it[0] }}</span><span class="mono muted">{{ it[1] }}</span></span><button type="button" class="btn bg ib sm" :aria-label="'Remove ' + it[0]" @click="items.splice(i, 1)"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div>
<div class="row"><button type="button" class="btn ba sm" @click="addItem"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a3 3 0 0 1 3 3v5a3 3 0 0 1-6 0V6a3 3 0 0 1 3-3zM5 11a7 7 0 0 0 14 0M12 18v3"></path></svg>Say one more</button><span class="tx muted">“Power bank, 800 se 2 hazaar” · {{ items.length }} of 5</span></div></div>
<div class="fld"><label class="lab" for="of">Offer running now</label><input id="of" class="in" value="₹2,000 extra on exchange, till Sunday"></div>
</div>

<div v-if="st === 4" class="rise" style="display: flex; flex-direction: column; gap: 20px">
<div class="fld"><span class="lab">Customers</span><div class="chips"><button v-for="x in TYPES" :key="x" type="button" :class="'chip' + (picked.types.includes(x) ? ' on' : '')" @click="tog(picked.types, x)">{{ x }}</button></div></div>
<div class="fld"><span class="lab">Languages they speak</span><div class="chips"><button v-for="x in SPOKEN" :key="x" type="button" :class="'chip' + (picked.langs.includes(x) ? ' on' : '')" @click="tog(picked.langs, x)">{{ x }}</button></div></div>
<div class="fld"><span class="lab">Busy places nearby</span><div class="chips"><button v-for="x in HUBS" :key="x" type="button" :class="'chip' + (picked.hubs.includes(x) ? ' on' : '')" @click="tog(picked.hubs, x)">{{ x }}</button></div></div>
</div>

<div v-if="st === 5" class="rise" style="display: flex; flex-direction: column; gap: 18px">
<div class="ph-x" style="height: 150px; border-radius: 12px; font-size: 11px">signboard photo · {{ name }}</div>
<div class="fld"><div style="display: flex; align-items: center"><span class="lab grow">Colours from your signboard</span><button type="button" class="lnk ts" @click="swapped = !swapped">Swap main colour</button></div>
<div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px"><div v-for="c in SWATCHES" :key="c" class="panel" style="padding: 8px; display: flex; gap: 10px; align-items: center"><span :style="{ width: '36px', height: '36px', borderRadius: '8px', background: c, boxShadow: c === '#FFFFFF' ? 'inset 0 0 0 1px #E2E8F0' : 'none' }"></span><span class="mono">{{ c }}</span></div></div></div>
<div class="fld"><span class="lab">Lettering</span><div class="seg"><button v-for="(f, i) in FEELS" :key="f" type="button" :class="feel === i ? 'on' : ''" @click="feel = i">{{ f }}</button></div></div>
</div>

<div v-if="st === 6" class="rise" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(150px, 100%), 1fr)); gap: 10px">
<button v-for="p in slots" :key="p.n" type="button" class="slot" @click="takeShot(p.i)"><span :class="p.cls" style="height: 110px; width: 100%; display: flex; align-items: flex-end; padding: 8px; font: 500 10.5px var(--fm)">{{ p.ph }}</span><span style="padding: 10px 12px; display: flex; justify-content: space-between; align-items: center; width: 100%"><span class="ts" style="font-weight: 500">{{ p.n }}</span><span :class="p.st">{{ p.s }}</span></span></button>
</div>

<div v-if="st === 7" class="rise" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px">
<button v-for="g in G" :key="g[0]" type="button" :class="'opt' + (goals.includes(g[0]) ? ' on' : '')" style="align-items: flex-start; padding: 16px" @click="toggleGoal(g[0])"><span class="ck" style="margin-top: 2px"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg></span><span style="display: flex; flex-direction: column; gap: 4px"><span class="h-sm">{{ g[1] }}</span><span class="tx muted">{{ g[2] }}</span></span></button>
</div>

<div v-if="st === 8" class="rise" style="display: flex; flex-direction: column; gap: 14px">
<div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px"><button v-for="(x, i) in SRCS" :key="x[0]" type="button" :class="'opt' + (src === i ? ' on' : '')" style="flex-direction: column; align-items: flex-start; gap: 6px" @click="src = i"><span class="h-sm">{{ x[0] }}</span><span class="tx muted">{{ x[1] }}</span></button></div>
<div class="panel pb" style="display: flex; flex-direction: column; gap: 10px"><div style="display: flex; justify-content: space-between"><span class="h-sm">212 customers found</span><span class="mono muted">contacts tagged “Shop”</span></div><div class="bar-t"><i style="width: 70%"></i></div><div style="display: flex; justify-content: space-between"><span class="st ok">148 said yes to offers</span><span class="st">64 not asked</span></div></div>
<div class="note"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7.5 3v5.5c0 4.7-3.2 8.3-7.5 9.5-4.3-1.2-7.5-4.8-7.5-9.5V6z"></path></svg><span>Only people who said yes get offers. Numbers are encrypted per field; your shop owns them, Dikho only processes them.</span></div>
</div>

<div v-if="st === 9" class="rise" style="display: flex; flex-direction: column; gap: 14px">
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(150px, 100%), 1fr)); gap: 8px"><button v-for="p in PACKAGES" :key="p.id" type="button" :class="'opt' + (planId === p.id ? ' on' : '')" style="flex-direction: column; align-items: flex-start; gap: 8px; min-height: 150px" @click="planId = p.id"><span style="display: flex; width: 100%; justify-content: space-between"><span class="h-sm">{{ p.name }}</span><span :class="'rd' + (planId === p.id ? ' on' : '')"></span></span><span style="font: 600 22px/1 var(--f); letter-spacing: -0.03em">{{ inr(p.price) }}<span class="tx muted" style="font-weight: 400">{{ planPer(p) }}</span></span><span class="tag cr">{{ p.credits.toLocaleString('en-IN') }} credits</span><span class="tx muted">{{ p.blurb }}</span></button></div>
<p class="tx muted">Prices include GST. Ad spend and WhatsApp fees come from a separate prepaid wallet at cost.</p>
</div>

<div style="display: flex; gap: 10px; padding-top: 8px; border-top: 1px solid #E2E8F0"><button type="button" class="btn bs" @click="prev">Back</button><span class="sp"></span><button v-if="st === 8" type="button" class="btn bg" @click="next">Skip</button><button type="button" class="btn bp" @click="next">{{ nextLabel }}<svg class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></button></div>
</template>

<div v-else class="rise" style="display: flex; flex-direction: column; gap: 20px; max-width: 620px">
<span class="st ok live">Setup done in 2 min 40 s</span><h1 class="h-xl">Your brand kit is ready.</h1><p class="t dim">Tomorrow at 7:00 am your first three posts will be waiting on the dashboard.</p>
<div class="panel"><button v-for="(x, i) in TAGS" :key="x[0]" type="button" class="row" @click="tag = i"><span :class="'rd' + (tag === i ? ' on' : '')"></span><span class="grow"><span class="h-sm" style="display: block">{{ x[0] }}</span><span class="tx muted">{{ x[1] }}</span></span></button></div>
<div style="display: flex; gap: 10px"><button type="button" class="btn bp lg" @click="openDashboard">Open dashboard<svg class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></button><button type="button" class="btn bs lg" @click="restart">Review answers</button></div>
</div>
</main>
<aside style="flex: 1.3 1 320px; min-width: 0; padding: 32px 28px; border-left: 1px solid #E2E8F0; background: #F8FAFC; display: flex; flex-direction: column; gap: 18px">
<span class="lm">Live preview</span>
<div :class="pcls" style="font-size: 10.5px; width: 100%; max-width: 300px"><div class="pi">{{ name }} · shop photo</div><div class="pad"><span class="hk" :style="{ fontStretch: STRETCH[feel] }">{{ TAGS[tag][0] }}</span><span class="sub">{{ landmark }} · {{ app.shop.city }}</span></div><div class="ft"><b>{{ name }}</b><span>Since {{ since }}</span></div></div>
<div class="receipt panel" style="padding: 6px 14px">
<div v-for="f in facts" :key="f.k" class="ln" style="border-bottom: 1px dashed #E2E8F0"><span class="muted">{{ f.k }}</span><span style="text-align: right">{{ f.v }}</span></div>
</div>
</aside>
</div>
</div>
</template>

<style scoped>
.stp{display:grid;grid-template-columns:28px minmax(0,1fr) 16px;gap:10px;align-items:center;width:100%;padding:9px 10px;border:0;border-radius:8px;background:none;cursor:pointer;text-align:left;font-size:13.5px;color:#64748B;transition:background .15s,color .15s}.stp:hover{background:#F1F5F9;color:#0F172A}.stp.on{background:#fff;color:#0F172A;box-shadow:0 0 0 1px #E2E8F0}.stp.done{color:#334155}.sq{width:24px;height:24px;border-radius:7px;display:grid;place-items:center;font:500 11px var(--fm);box-shadow:inset 0 0 0 1px #CBD5E1;background:#fff}.stp.done .sq{background:#0F172A;color:#fff;box-shadow:none}.stp.on .sq{box-shadow:inset 0 0 0 1.5px #0F172A;color:#0F172A}.slot{border-radius:10px;overflow:hidden;border:1px solid #E2E8F0;background:#fff;cursor:pointer;padding:0;text-align:left;display:flex;flex-direction:column;transition:border-color .2s,transform .25s var(--ease)}.slot:hover{border-color:#CBD5E1;transform:translateY(-2px)}
</style>
