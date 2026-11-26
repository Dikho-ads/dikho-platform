<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useApp, PACKAGES, inr } from '@/stores/app';

const app = useApp();
const router = useRouter();

const TABS = [
    ['Plan & credits', 'M4 6h16v12H4zM4 10h16'],
    ['Wallet', 'M3 6h18v14H3zM3 10h18M16 15h2'],
    ['Team', 'M9 11.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM2.5 20c.8-3.6 3.4-5.5 6.5-5.5s5.7 1.9 6.5 5.5M16 4.6a3.5 3.5 0 0 1 0 6.8'],
    ['Connections', 'M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1'],
    ['Shop & brand', 'M4 9.5V20h16V9.5M3 4h18l-1.5 5.5a2.6 2.6 0 0 1-5 0 2.6 2.6 0 0 1-5 0 2.6 2.6 0 0 1-5 0z'],
    ['Security', 'M12 3l7.5 3v5.5c0 4.7-3.2 8.3-7.5 9.5-4.3-1.2-7.5-4.8-7.5-9.5V6z'],
];
const AMTS = [500, 1000, 2000];
const ROLES = [
    ['Owner', 'Everything, including plan, wallet and team'],
    ['Editor', 'Make, approve and post - can’t pay or change the plan'],
    ['Viewer', 'See the plan and results - can’t post'],
];
const WEIGHTS = [
    ['3 ready posts every day', 'free'],
    ['Extra sizes, Status, Story', 'free'],
    ['Custom post', '2 cr'],
    ['WhatsApp offer creative', '3 cr'],
    ['Carousel, 5 cards', '5 cr'],
    ['AI-scene post', '6 cr'],
    ['Print design', '8 cr'],
    ['Win-back, 3 messages', '10 cr'],
    ['15 s reel', '15 cr'],
    ['Meta ad + 7 days watching', '20 cr'],
    ['30 s reel + voiceover', '25 cr'],
    ['Jingle + sonic logo · 1/mo', '40 cr'],
    ['Yaadein pack · 1/mo', '45 cr'],
];
const WAVE = [6, 10, 16, 22, 14, 8, 12, 20, 22, 18, 10, 6, 9, 15, 21, 17, 11, 7, 12, 18, 14, 9, 6, 4];
const BASE_TX = [
    ['25 NOV', 'WhatsApp offer', '148 customers × ₹0.86', '− ₹127.74'],
    ['23 NOV', 'Meta ad · exchange', '7 days · 22 chats', '− ₹700'],
    ['18 NOV', 'Win-back messages', '14 customers', '− ₹12.08'],
    ['12 NOV', 'Added by UPI', 'ramesh@okaxis', '+ ₹2,000'],
];

const tab = ref(0);
const packs = ref(1);
const amt = ref(1);
const added = ref([]);
const warn = ref(true);
const invited = ref([]);
const email = ref('pooja.saimobile@gmail.com');
const role = ref(1);
const gb = ref(false);
const pin = ref(false);
const playing = ref(false);
const showPlans = ref(false);
const toastTxt = ref('');
let tt = null;
let pt = null;

const ownerEmail = computed(() => app.user.email || 'ramesh.saimobile@gmail.com');
const statusMap = computed(() => [
    ['st ok live', 'Active · ' + app.pkg.name],
    ['st ok', 'Balance ' + inr(app.wallet)],
    ['st', 2 + invited.value.length + ' people'],
    ['st ok', (gb.value ? 4 : 3) + ' of 4 connected'],
    ['st ok', 'Brand kit locked'],
    ['st ok', '2 devices'],
]);
const ticks = computed(() => {
    const on = Math.round((app.creditsPct / 100) * 50);
    return Array.from({ length: 50 }, (_, i) => ({ c: i < on ? 'on' : '', d: (0.1 + i * 0.012).toFixed(3) + 's' }));
});
const tx = computed(() =>
    added.value
        .map((a) => ['26 NOV', 'Added by UPI', 'ramesh@okaxis', '+ ' + inr(a)])
        .concat(BASE_TX)
        .map(([d, n, s, a]) => ({ d, n, s, a })),
);
const team = computed(() =>
    [
        [app.user.name + ' (you)', ownerEmail.value, 'Owner', 'tag dk', app.user.initials],
        ['Suresh', 'suresh.sai@gmail.com', 'Editor', 'tag', 'SU'],
    ]
        .concat(invited.value.map(([e, r]) => [e.split('@')[0], e, r + ' · invited', 'tag acc', e.slice(0, 2).toUpperCase()]))
        .map(([n, e, r, tc, ini]) => ({ n, e, r, tc, ini, can: ROLES.find((x) => r.startsWith(x[0]))[1] })),
);
const roleNote = computed(() => ROLES[role.value][0] + ': ' + ROLES[role.value][1] + '.');
const conns = computed(() => [
    { m: 'WA', mt: 'mt', n: 'WhatsApp Business', d: app.shop.whatsapp + ' · status, broadcast, catalogue', st: 'Connected', sc: 'st ok live', b: 'Manage', bc: 'btn bs sm' },
    { m: 'IG', mt: 'mt', n: 'Instagram', d: '@saimobilepoint · business account', st: 'Connected', sc: 'st ok', b: 'Manage', bc: 'btn bs sm' },
    { m: 'FB', mt: 'mt', n: 'Facebook Page + Meta ads', d: app.shop.name + ' · ad account linked', st: 'Connected', sc: 'st ok', b: 'Manage', bc: 'btn bs sm' },
    {
        m: 'GB',
        mt: gb.value ? 'mt' : 'mt lt',
        n: 'Google Business Profile',
        d: gb.value ? app.shop.name + ', ' + app.shop.city + ' · offer posts on' : 'Post offers on your Google listing',
        st: gb.value ? 'Connected' : 'Not linked',
        sc: gb.value ? 'st ok' : 'st',
        b: gb.value ? 'Manage' : 'Connect',
        bc: gb.value ? 'btn bs sm' : 'btn bp sm',
        gb: true,
    },
]);
const sessions = [
    { m: 'LP', n: 'This laptop · Chrome', d: 'Nashik · active now', s: 'This device', sc: 'st ok live' },
    { m: 'PH', n: 'Android phone · Dikho app', d: 'Nashik · 2 hours ago', s: 'Signed in', sc: 'st' },
];
const playIcon = computed(() => (playing.value ? 'M8 5v14M16 5v14' : 'M7 4.5v15l13-7.5z'));
const bars = computed(() => WAVE.map((h, i) => ({ h: h + 'px', c: playing.value ? (i < 14 ? '#F2A007' : '#0F172A') : '#CBD5E1' })));

function show(m) {
    toastTxt.value = m;
    clearTimeout(tt);
    tt = setTimeout(() => (toastTxt.value = ''), 3000);
}
onBeforeUnmount(() => {
    clearTimeout(tt);
    clearTimeout(pt);
});

function pickPlan(id) {
    app.setPackage(id);
    showPlans.value = false;
    show('Plan changed to ' + app.pkg.name);
}
function buy() {
    app.addCredits(packs.value * 50);
    show(packs.value * 50 + ' credits added · ₹' + packs.value * 79 + ' by UPI');
}
function addMoney() {
    const a = AMTS[amt.value];
    app.topUp(a);
    added.value.unshift(a);
    show(inr(a) + ' added to wallet');
}
function invite() {
    if (!email.value.includes('@')) return;
    invited.value.push([email.value, ROLES[role.value][0]]);
    show('Invite sent to ' + email.value);
    email.value = '';
}
function connectGb() {
    if (gb.value) return;
    gb.value = true;
    show('Google Business Profile connected');
}
function play() {
    const was = playing.value;
    playing.value = !was;
    clearTimeout(pt);
    if (!was) pt = setTimeout(() => (playing.value = false), 2500);
}
function signOut() {
    app.logout();
    router.push({ name: 'login' });
}
</script>

<template>
<div class="wrap">
<div class="rise" style="display: flex; flex-direction: column; gap: 8px"><span class="eyb"><b>SETTINGS</b>·<span>{{ app.shop.name.toUpperCase() }}</span></span><h1 class="h-lg">Settings</h1></div>
<div class="panel rise d1" style="display: flex; flex-wrap: wrap; overflow: hidden; min-height: 640px">
<nav style="flex: 1 1 210px; max-width: 250px; background: #F8FAFC; border-right: 1px solid #E2E8F0; padding: 14px 10px; display: flex; flex-direction: column; gap: 2px" aria-label="Settings sections"><button v-for="(t, i) in TABS" :key="t[0]" type="button" :class="'stab' + (tab === i ? ' on' : '')" @click="tab = i"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path :d="t[1]"></path></svg>{{ t[0] }}</button></nav>
<div style="flex: 999 1 520px; min-width: 0; display: flex; flex-direction: column">
<div style="display: flex; align-items: center; gap: 12px; padding: 14px 22px; border-bottom: 1px solid #E2E8F0; background: #fff"><span class="h-sm">{{ TABS[tab][0] }}</span><span class="sp"></span><span :class="statusMap[tab][0]">{{ statusMap[tab][1] }}</span></div>

<div v-if="tab === 0" class="fade">
<div class="srow"><span style="display: flex; gap: 12px; align-items: center"><span class="mt">{{ app.pkg.name.slice(0, 2).toUpperCase() }}</span><span><span class="h-sm" style="display: block">{{ app.pkg.name }} · {{ inr(app.pkg.price) }} / month</span><span class="tx muted">{{ app.creditsTotal }} credits · renews {{ app.resetsOn }} by UPI AutoPay · prices include GST</span></span></span><button type="button" class="btn bs sm" @click="showPlans = !showPlans">Change plan</button></div>
<div v-if="showPlans" class="srow"><span class="seg" role="group" aria-label="Plans"><button v-for="p in PACKAGES" :key="p.id" type="button" :class="app.packageId === p.id ? 'on' : ''" @click="pickPlan(p.id)">{{ p.name }} · {{ inr(p.price) }}</button></span><span class="tx muted">{{ app.pkg.blurb }}</span></div>
<div class="srow"><span style="display: flex; flex-direction: column; gap: 10px"><span><span class="h-sm">{{ app.creditsLeft }} credits left</span><span class="tx muted"> · up to 25% of unused credits roll over one month</span></span><span class="ticks" style="max-width: 560px"><i v-for="(k, i) in ticks" :key="i" :class="k.c" :style="{ animationDelay: k.d }"></i></span></span><span class="mono muted">{{ app.creditsUsed }} used</span></div>
<div class="srow"><span><span class="h-sm" style="display: block">Top up · 50 credits for ₹79</span><span class="tx muted">Always priced above plan rates. {{ packs }} × 50 = {{ packs * 50 }} credits</span></span><span style="display: inline-flex; gap: 8px; align-items: center"><button type="button" class="btn bs ib sm" aria-label="Fewer packs" @click="packs = Math.max(1, packs - 1)">−</button><span class="mono" style="width: 20px; text-align: center">{{ packs }}</span><button type="button" class="btn bs ib sm" aria-label="More packs" @click="packs = Math.min(6, packs + 1)">+</button><button type="button" class="btn bp sm" @click="buy">Buy · ₹{{ packs * 79 }}</button></span></div>
<div style="padding: 16px 22px 22px; display: flex; flex-direction: column; gap: 10px"><span class="lm">What each thing costs · any mix, same price</span>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(260px, 100%), 1fr)); column-gap: 28px" class="receipt"><div v-for="w in WEIGHTS" :key="w[0]" class="ln" style="border-bottom: 1px dashed #E2E8F0"><span>{{ w[0] }}</span><span>{{ w[1] }}</span></div></div>
<p class="tx muted">Ad spend and WhatsApp fees never use credits - they come from the wallet at cost. Weights change at most once a quarter, never mid-month.</p></div>
</div>

<div v-if="tab === 1" class="fade">
<div class="srow"><span><span class="lm">Wallet balance</span><span style="display: block; font: 600 40px/1.1 var(--f); letter-spacing: -0.04em; margin-top: 6px">{{ inr(app.wallet) }}</span><span class="tx muted">Only for Meta ad spend and WhatsApp message fees, passed through at cost.</span></span><span style="display: inline-flex; gap: 8px; align-items: center"><span class="seg"><button v-for="(a, i) in AMTS" :key="a" type="button" :class="amt === i ? 'on' : ''" @click="amt = i">{{ inr(a) }}</button></span><button type="button" class="btn bp" @click="addMoney">Add by UPI</button></span></div>
<button type="button" class="srow" style="width: 100%; background: none; border-left: 0; border-right: 0; border-top: 0; text-align: left; cursor: pointer" @click="warn = !warn"><span><span class="h-sm" style="display: block">Warn me below ₹200</span><span class="tx muted">Ads pause before the wallet runs out - you never overspend.</span></span><span :class="'sw' + (warn ? ' on' : '')"></span></button>
<table class="tbl"><thead><tr><th>Date</th><th>What</th><th>Detail</th><th style="text-align: right">Amount</th></tr></thead><tbody><tr v-for="(t, i) in tx" :key="i"><td class="mono">{{ t.d }}</td><td>{{ t.n }}</td><td class="muted">{{ t.s }}</td><td class="mono" style="text-align: right">{{ t.a }}</td></tr></tbody></table>
</div>

<div v-if="tab === 2" class="fade">
<table class="tbl"><thead><tr><th>Person</th><th>Email</th><th>Role</th><th>Can</th></tr></thead><tbody><tr v-for="m in team" :key="m.e"><td><span style="display: inline-flex; gap: 10px; align-items: center"><span class="av">{{ m.ini }}</span><b style="font-weight: 500">{{ m.n }}</b></span></td><td class="mono">{{ m.e }}</td><td><span :class="m.tc">{{ m.r }}</span></td><td class="muted">{{ m.can }}</td></tr></tbody></table>
<div style="padding: 18px 22px; border-top: 1px solid #E2E8F0; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end"><div class="fld" style="flex: 1 1 260px"><label class="lab" for="iv">Invite by email</label><input id="iv" v-model="email" class="in"></div><div class="fld"><span class="lab">Role</span><span class="seg"><button v-for="(r, i) in ROLES" :key="r[0]" type="button" :class="role === i ? 'on' : ''" @click="role = i">{{ r[0] }}</button></span></div><button type="button" class="btn bp" @click="invite">Send invite</button></div>
<p class="tx muted" style="padding: 0 22px 18px">{{ roleNote }}</p>
</div>

<div v-if="tab === 3" class="fade"><div v-for="c in conns" :key="c.m" class="srow"><span style="display: flex; gap: 12px; align-items: center"><span :class="c.mt">{{ c.m }}</span><span><span class="h-sm" style="display: block">{{ c.n }}</span><span class="mono muted">{{ c.d }}</span></span></span><span style="display: inline-flex; gap: 12px; align-items: center"><span :class="c.sc">{{ c.st }}</span><button type="button" :class="c.bc" @click="c.gb && connectGb()">{{ c.b }}</button></span></div></div>

<div v-if="tab === 4" class="fade">
<div class="srow"><span><span class="lm">Shop</span><span class="h-sm" style="display: block; margin-top: 4px">{{ app.shop.name }} · साई मोबाइल पॉइंट</span><span class="tx muted">{{ app.shop.category }} · {{ app.shop.area }}, near bus stand · 10 am – 9 pm · off Mondays</span></span><RouterLink class="btn bs sm" :to="{ name: 'setup' }">Edit</RouterLink></div>
<div class="srow"><span><span class="lm">Tagline</span><span class="h-sm" style="display: block; margin-top: 4px">Sai Mobile - bharose ka naam</span><span class="tx muted">Locked until 24 Feb so customers remember it</span></span><span class="tag">locked</span></div>
<div class="srow"><span><span class="lm">Colours from the signboard</span><span style="display: flex; gap: 6px; margin-top: 8px"><span style="width: 28px; height: 28px; border-radius: 7px; background: #1D3A8A"></span><span style="width: 28px; height: 28px; border-radius: 7px; background: #FFD23F"></span><span style="width: 28px; height: 28px; border-radius: 7px; background: #fff; box-shadow: inset 0 0 0 1px #E2E8F0"></span></span></span><span class="mono muted">#1D3A8A · #FFD23F</span></div>
<div class="srow"><span style="display: flex; gap: 14px; align-items: center"><button type="button" class="btn bp ib" aria-label="Play sonic logo" @click="play"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path :d="playIcon"></path></svg></button><span><span class="h-sm" style="display: block">Sonic logo · “Sai… Sai Mobile!”</span><span class="tx muted">2.5 s · dhol + shehnai · yours forever</span></span></span><span style="display: flex; gap: 3px; align-items: center; height: 26px"><span v-for="(b, i) in bars" :key="i" :style="{ width: '3px', borderRadius: '2px', height: b.h, background: b.c, transition: 'background .2s' }"></span></span></div>
</div>

<div v-if="tab === 5" class="fade">
<div v-for="x in sessions" :key="x.m" class="srow"><span style="display: flex; gap: 12px; align-items: center"><span class="mt lt">{{ x.m }}</span><span><span class="h-sm" style="display: block">{{ x.n }}</span><span class="mono muted">{{ x.d }}</span></span></span><span :class="x.sc">{{ x.s }}</span></div>
<div class="srow"><span><span class="h-sm" style="display: block">Signed in for 30 days per device</span><span class="tx muted">Lost a phone? Sign every device out at once.</span></span><button type="button" class="btn bdg sm" @click="show('Signed out on all other devices')">Log out everywhere</button></div>
<button type="button" class="srow" style="width: 100%; background: none; border-left: 0; border-right: 0; border-top: 0; text-align: left; cursor: pointer" @click="pin = !pin"><span><span class="h-sm" style="display: block">4-digit PIN on shared devices</span><span class="tx muted">For a family phone or the shop laptop</span></span><span :class="'sw' + (pin ? ' on' : '')"></span></button>
<div class="srow"><span><span class="h-sm" style="display: block">Customer consent records</span><span class="tx muted">Who said yes, when and how - kept from day one under the DPDP rules.</span></span><button type="button" class="btn bs sm">Download</button></div>
<div class="srow"><span><span class="h-sm" style="display: block">Delete shop data</span><span class="tx muted">Removes customers, posts and results. Can’t be undone.</span></span><button type="button" class="btn bdg sm">Delete…</button></div>
<div class="srow"><span><span class="h-sm" style="display: block">Sign out</span><span class="tx muted">Signed in as {{ ownerEmail }}</span></span><button type="button" class="btn bs" @click="signOut">Sign out</button></div>
</div>
</div>
</div>
<div v-if="toastTxt" class="toast" role="status">{{ toastTxt }}</div>
</div>
</template>

<style scoped>
.stab{display:flex;align-items:center;gap:10px;width:100%;height:36px;padding:0 10px;border:0;border-radius:8px;background:none;text-align:left;cursor:pointer;font:500 13.5px var(--f);color:#334155;transition:background .15s}.stab:hover{background:#F1F5F9}.stab.on{background:#fff;color:#0F172A;box-shadow:0 0 0 1px #E2E8F0}.srow{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:20px;align-items:center;padding:16px 22px;border-bottom:1px solid #E2E8F0}.srow:last-child{border-bottom:0}
</style>
