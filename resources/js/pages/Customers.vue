<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import { useApp } from '../stores/app'

const app = useApp()

const G = [['all', 'All', 212, 'everyone'], ['new', 'New', 22, '1 visit so far'], ['reg', 'Regular', 140, '2nd visit in 30 d'], ['risk', 'At risk', 27, 'gap > 1.5× usual'], ['lap', 'Lapsed', 14, 'gap > 3× usual'], ['lost', 'Lost', 9, 'no reply to 3 tries']]
const SC = { new: ['st ink', 'New'], reg: ['st ok', 'Regular'], risk: ['st warn', 'At risk'], lap: ['st bad', 'Lapsed'], lost: ['st', 'Lost'] }
const C = [
  ['Priya Deshmukh', '12 OCT', '~30 d', 'Vivo 5G + glass', 'risk', 'yes', '0 / 2'], ['Amol Kale', '03 SEP', '~25 d', 'Screen repair', 'lap', 'yes', '0 / 2'],
  ['Sneha Patil', '22 NOV', '~20 d', 'Earbuds', 'reg', 'yes', '1 / 2'], ['Rahul Jadhav', '25 NOV', '—', 'First visit', 'new', 'not asked', '—'],
  ['Meena Kulkarni', '30 AUG', '~25 d', 'Covers, glass', 'lap', 'yes', '0 / 2'], ['Vikas Shinde', '18 NOV', '~30 d', 'Battery change', 'reg', 'yes', '0 / 2'],
  ['Farida Shaikh', '02 OCT', '~20 d', 'Samsung A-series', 'risk', 'yes', '0 / 2'], ['Kavita Joshi', '20 NOV', '~15 d', 'Recharge, cover', 'reg', 'yes', '1 / 2'],
  ['Imran Pathan', '09 SEP', '~30 d', 'Charging port repair', 'lap', 'yes', '0 / 2'], ['Ganesh More', '14 JUN', '—', 'Recharge', 'lost', 'said STOP', '—']
]
const LIFE = ['new', 'reg', 'risk', 'lap', 'lost']
const P = [['Amol', 'AMOL'], ['Meena', 'MEENA'], ['Imran', 'IMRAN']]

const seg = ref('all')
const picked = ref([])
const open = ref(-1)
const win = ref(false)
const who = ref(0)
const visited = ref([])
const toast = ref('')
let tt = null

const ini = (name) => name.split(' ').map((w) => w[0]).join('')
const stageOf = (i) => (visited.value.includes(i) ? 'reg' : C[i][4])

function show(m) {
  toast.value = m
  clearTimeout(tt)
  tt = setTimeout(() => { toast.value = '' }, 3000)
}
onBeforeUnmount(() => clearTimeout(tt))

const stages = computed(() => G.map(([id, n, c, rule], i) => ({ id, n, c, rule, arrow: i > 0 && i < 5 })))
const rows = computed(() => C.map((c, i) => i).filter((i) => seg.value === 'all' || stageOf(i) === seg.value).map((i) => {
  const c = C[i], sg = stageOf(i)
  return { i, n: c[0], ini: ini(c[0]), last: visited.value.includes(i) ? 'TODAY' : c[1], gap: c[2], bought: c[3], sc: SC[sg][0], stage: SC[sg][1], consent: c[5] === 'yes' ? 'opted in' : c[5], wk: c[6] }
}))
const hasPicked = computed(() => picked.value.length > 0)
const winCount = computed(() => picked.value.length || 14)
const winFee = computed(() => '₹' + Math.round(winCount.value * 3 * 0.8631))

const d = computed(() => {
  const o = open.value >= 0 ? C[open.value] : C[0]
  const st = open.value >= 0 ? stageOf(open.value) : 'risk'
  const vis = visited.value.includes(open.value)
  return {
    n: o[0], ini: ini(o[0]), bought: o[3], last: vis ? 'today' : o[1].toLowerCase(), gap: o[2], wk: o[6],
    consentLong: o[5] === 'yes' ? 'Opted in at the counter · consent logged' : (o[5] === 'said STOP' ? 'Said STOP · no offers, ever' : 'Not asked yet · no offers'),
    since: vis ? 'came in today' : 'last came ' + o[1].toLowerCase(),
    life: LIFE.map((k, i) => ({ n: SC[k][1], c: k === st ? '#0F172A' : (LIFE.indexOf(st) > i ? '#CBD5E1' : '#F1F5F9'), tc: k === st ? '#0F172A' : '#64748B', fw: k === st ? 600 : 400 })),
    visitTxt: vis ? 'Marked as visited today' : 'Came in today'
  }
})

const seq = computed(() => {
  const [pn, pc] = P[who.value]
  return [
    { k: '1', n: 'Nudge', when: 'when at risk', msg: `Namaste ${pn} ji, kaafi din ho gaye! Is hafte naye 5G phones aaye hain — ek baar zaroor dekhiye.` },
    { k: '2', n: 'Offer', when: 'tomorrow 10 am', msg: `${pn} ji, sirf aapke liye: kisi bhi repair pe tempered glass free, 6 Dec tak. Code ${pc}G.` },
    { k: '3', n: 'Close', when: '+7 days', msg: 'Hum aapka intezaar karenge. Ghar baithe phone check karwana ho to reply karein: CALL.' }
  ]
})

function togglePick(i) {
  picked.value = picked.value.includes(i) ? picked.value.filter((x) => x !== i) : picked.value.concat(i)
}
function visit() {
  const i = open.value
  if (!visited.value.includes(i)) {
    visited.value = visited.value.concat(i)
    show(C[i][0] + ' moved back to Regular')
  }
}
function startWin() {
  win.value = false
  if (!app.spend(10)) {
    show('Not enough credits to start the win-back')
    return
  }
  picked.value = []
  show('Win-back started · personal offers go out tomorrow 10 am')
}
</script>

<template>
  <div class="wrap">
    <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
      <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 360px"><span class="eyb"><b>212</b>·<span>CUSTOMERS · 148 SAID YES TO OFFERS</span></span><h1 class="h-lg">Your regulars, and who stopped coming</h1></div>
      <button type="button" class="btn bs"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"></path></svg>Import</button>
      <button type="button" class="btn bp" @click="win = true">Win back 14</button>
    </div>
    <div class="panel rise d1" style="display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); overflow: hidden">
      <button v-for="g in stages" :key="g.id" type="button" :class="'lc' + (seg === g.id ? ' on' : '')" style="border-right: 1px solid #E2E8F0" @click="seg = g.id"><span class="lm">{{ g.n }}</span><span class="num" style="font-size: 26px">{{ g.c }}</span><span class="tx muted">{{ g.rule }}</span><span v-if="g.arrow" class="arw"><svg viewBox="0 0 24 24" width="8" height="8" aria-hidden="true" style="fill: none; stroke: currentColor; stroke-width: 3"><path d="M9 5l7 7-7 7"></path></svg></span></button>
    </div>
    <section class="panel rise d2" style="overflow: hidden">
      <div class="ph" style="flex-wrap: wrap"><label class="search" style="width: 280px; background: #fff"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4"></path></svg><input placeholder="Name, number or item" aria-label="Search customers"></label><span class="sp"></span>
        <span v-if="hasPicked" class="fade" style="display: inline-flex; gap: 10px; align-items: center"><span class="mono">{{ picked.length }} selected</span><button type="button" class="btn bp sm" @click="win = true">Send win-back</button><button type="button" class="btn bg sm" @click="picked = []">Clear</button></span>
        <span v-else class="tx muted">Judged against each person’s own visit cycle</span></div>
      <div class="tscroll"><table class="tbl">
        <thead><tr><th style="width: 40px"></th><th>Customer</th><th>Last visit</th><th>Usual gap</th><th>Bought</th><th>Stage</th><th>Offers</th><th style="text-align: right">This week</th></tr></thead>
        <tbody><tr v-for="r in rows" :key="r.i" :class="open === r.i ? 'sel' : ''" @click="open = r.i">
          <td><button type="button" :class="'ck' + (picked.includes(r.i) ? ' on' : '')" :aria-label="'Select ' + r.n" @click.stop="togglePick(r.i)"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg></button></td>
          <td><span style="display: inline-flex; gap: 10px; align-items: center"><span class="av">{{ r.ini }}</span><b style="font-weight: 500">{{ r.n }}</b></span></td>
          <td class="mono">{{ r.last }}</td><td class="mono muted">{{ r.gap }}</td><td>{{ r.bought }}</td><td><span :class="r.sc">{{ r.stage }}</span></td><td class="muted">{{ r.consent }}</td><td class="mono" style="text-align: right">{{ r.wk }}</td>
        </tr></tbody>
      </table></div>
    </section>

    <template v-if="open >= 0">
      <div class="scrim" style="background: rgba(15,23,42,.12)" @click="open = -1"></div>
      <aside class="drawer" aria-label="Customer details">
        <div class="ph"><span class="av">{{ d.ini }}</span><span class="grow"><span class="h-sm" style="display: block">{{ d.n }}</span><span class="tx muted">{{ d.consentLong }}</span></span><button type="button" class="btn bg ib sm" aria-label="Close" @click="open = -1"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div>
        <div class="pb" style="display: flex; flex-direction: column; gap: 18px">
          <div style="display: flex; flex-direction: column; gap: 10px"><div style="display: flex; justify-content: space-between"><span class="lm">Stage</span><span class="mono muted">{{ d.since }}</span></div>
            <div style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 4px"><span v-for="l in d.life" :key="l.n" style="display: flex; flex-direction: column; gap: 6px"><span :style="`height: 6px; border-radius: 3px; background: ${l.c}; transition: background .4s`"></span><span class="tx" :style="`color: ${l.tc}; font-weight: ${l.fw}`">{{ l.n }}</span></span></div>
            <button type="button" class="btn bs full" @click="visit"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg>{{ d.visitTxt }}</button></div>
          <div class="receipt"><div class="ln" style="border-bottom: 1px solid #E2E8F0"><span class="muted">bought</span><span>{{ d.bought }} · {{ d.last }}</span></div><div class="ln" style="border-bottom: 1px solid #E2E8F0"><span class="muted">usual gap</span><span>{{ d.gap }}</span></div><div class="ln" style="border-bottom: 1px solid #E2E8F0"><span class="muted">offers this week</span><span>{{ d.wk }}</span></div><div class="ln"><span class="muted">quiet hours</span><span>9 pm – 9 am</span></div></div>
          <div style="display: flex; flex-direction: column; gap: 8px"><span class="lm">Moments</span>
            <div class="panel"><div class="row"><span class="mono muted" style="width: 52px">+30 D</span><span class="grow ts">“Phone kaisa chal raha hai?” + cover offer</span><span class="st ok">sent</span></div><div class="row"><span class="mono muted" style="width: 52px">BDAY</span><span class="grow ts">Wish + ₹100 voucher</span><span class="st">auto</span></div><div class="row"><span class="mono muted" style="width: 52px">+1 YR</span><span class="grow ts">Free battery &amp; speed check</span><span class="st">auto</span></div></div></div>
          <button type="button" class="btn bp lg full" @click="win = true">Send a personal offer</button>
        </div>
      </aside>
    </template>

    <template v-if="win">
      <div class="scrim" @click="win = false"></div>
      <div class="modal" role="dialog" aria-label="Win-back">
        <div class="ph"><span class="eyb"><b>WIN-BACK</b>·<span>{{ winCount }} PEOPLE · ALL OPTED IN</span></span><span class="sp"></span><button type="button" class="btn bg ib sm" aria-label="Close" @click="win = false"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div>
        <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
          <div style="display: flex; gap: 6px; align-items: center"><span class="tx muted">Preview for</span><button v-for="(p, i) in P" :key="p[0]" type="button" :class="'chip' + (who === i ? ' on' : '')" style="height: 28px" @click="who = i">{{ p[0] }}</button></div>
          <div v-for="q in seq" :key="q.k" style="display: grid; grid-template-columns: 120px minmax(0, 1fr); gap: 14px; align-items: start"><span style="display: flex; flex-direction: column; gap: 4px"><span class="h-sm">{{ q.k }} · {{ q.n }}</span><span class="mono muted" style="font-size: 11px">{{ q.when }}</span></span><p class="bub" lang="hi">{{ q.msg }}</p></div>
          <div class="receipt" style="border-top: 1px dashed #CBD5E1; padding-top: 6px"><div class="ln"><span class="muted">set-up</span><span>10 cr</span></div><div class="ln"><span class="muted">{{ winCount }} × up to 3 × ₹0.86</span><span>≈ {{ winFee }} wallet</span></div></div>
          <p class="tx muted">Max 2 offers a week per person · nothing 9 pm – 9 am · STOP on every message · anyone who comes back stops getting the rest.</p>
          <div style="display: flex; gap: 8px; justify-content: flex-end"><button type="button" class="btn bs" @click="win = false">Cancel</button><button type="button" class="btn bp" @click="startWin">Start win-back</button></div>
        </div>
      </div>
    </template>
    <div v-if="toast" class="toast" role="status">{{ toast }}</div>
  </div>
</template>

<style scoped>
.lc{display:flex;flex-direction:column;gap:6px;padding:14px 16px;border:0;background:#fff;text-align:left;cursor:pointer;position:relative;transition:background .15s;min-width:0}.lc:hover{background:#F8FAFC}.lc.on{background:#F1F5F9}.lc.on::after{content:"";position:absolute;left:16px;right:16px;bottom:0;height:2px;background:#0F172A;animation:growX .35s var(--ease) both;transform-origin:left}.arw{position:absolute;right:-7px;top:50%;transform:translateY(-50%);width:14px;height:14px;background:#fff;border:1px solid #E2E8F0;border-radius:50%;display:grid;place-items:center;z-index:1;color:#94A3B8}.bub{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:4px 14px 14px 14px;padding:10px 12px;font-size:13.5px;line-height:20px}
</style>
