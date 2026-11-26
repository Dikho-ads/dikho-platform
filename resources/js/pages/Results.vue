<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const r = ref(0)
const flip = ref(0)
const hov = ref(1)
const p = ref(0)
let raf = 0

onMounted(() => {
  const t0 = performance.now()
  const tick = (t) => {
    p.value = Math.min(1, (t - t0) / 1000)
    if (p.value < 1) raf = requestAnimationFrame(tick)
  }
  raf = requestAnimationFrame(tick)
})
onBeforeUnmount(() => cancelAnimationFrame(raf))

const R = [
  { n: 'This week', range: '20 – 26 NOV', head: 'Chats and calls per day', lab: ['Fri', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu'], ch: [3, 11, 5, 4, 6, 4, 5], ca: [1, 3, 2, 1, 2, 1, 2], k: [2340, 38, 12, 9, 6, 700], d: ['+18%', '+31%', '+20%', '+50%', '+2', '+₹200'], ad: ['₹700', '22', '₹32'] },
  { n: 'Last week', range: '13 – 19 NOV', head: 'Chats and calls per day', lab: ['Fri', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu'], ch: [4, 3, 5, 4, 6, 4, 3], ca: [1, 2, 2, 1, 2, 1, 1], k: [1980, 29, 10, 6, 4, 500], d: ['', '', '', '', '', ''], ad: ['₹500', '14', '₹36'] },
  { n: 'November', range: '1 – 26 NOV', head: 'Chats and calls per week', lab: ['Wk 1', 'Wk 2', 'Wk 3', 'Wk 4'], ch: [28, 31, 34, 38], ca: [10, 12, 13, 12], k: [8210, 131, 47, 33, 21, 2100], d: ['', '', '', '', '', ''], ad: ['₹2,100', '61', '₹34'] }
]
const L = ['Saw it', 'Chats', 'Calls', 'QR used', 'Offer codes', 'Ad spend']
const HM = [[0, 1, 0, 1, 0, 1, 2], [1, 1, 0, 0, 1, 2, 1], [1, 2, 1, 1, 2, 3, 2], [2, 4, 2, 0, 3, 4, 3]]
const shade = ['#F1F5F9', '#CBD5E1', '#94A3B8', '#475569', '#0F172A']
const slots = ['9–12', '12–4', '4–7', '7–9 pm']
const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
const CH = [['Meta ad', 22], ['WhatsApp Status', 9], ['Instagram', 3], ['Facebook', 2], ['Broadcast', 2]]
const hd = ['M', 'T', 'W', 'T', 'F', 'S', 'S']
const top = [
  { k: 'ad', n: 'Exchange offer · Meta ad', w: 'Instagram, Facebook', a: 'Offer', c: 22, l: 5, q: 3, t: 95 },
  { k: 'post', n: 'Shaadi offer post', w: 'Status, Sat 21', a: 'Offer', c: 9, l: 3, q: 4, t: 95 },
  { k: 'post', n: '5G phone ₹9,999', w: 'Feed, Status', a: 'Newness', c: 6, l: 3, q: 2, t: 97 },
  { k: 'post', n: 'Customer quote — Priya', w: 'Feed', a: 'Trust', c: 1, l: 1, q: 0, t: 96 }
]
const wb = Array.from({ length: 14 }, (_, i) => ({ c: i < 3 ? '#0F172A' : '#E2E8F0', d: (0.4 + i * 0.04) + 's' }))
const chans = CH.map(([n, v], i) => ({ n, v, w: (v / 22 * 100) + '%', dl: (0.3 + i * 0.08) + 's' }))
const heat = []
HM.forEach((row, ri) => {
  heat.push({ cls: '', bg: 'transparent', l: slots[ri], t: '' })
  row.forEach((v, di) => heat.push({ cls: 'hm', bg: ri === 3 && di === 1 ? '#F2A007' : shade[v], l: '', t: slots[ri] + ' ' + days[di] }))
})

const c = computed(() => R[r.value])
const n = computed(() => c.value.ch.length)
const max = computed(() => Math.max(...c.value.ch) * 1.15)
const X = (i) => 70 + i * (660 / (n.value - 1))
const Y = (v) => 220 - (v / max.value) * 196
const path = (arr) => arr.map((v, i) => (i ? 'L' : 'M') + X(i).toFixed(1) + ' ' + Y(v).toFixed(1)).join(' ')
const hv = computed(() => Math.min(hov.value, n.value - 1))
const e = computed(() => 1 - Math.pow(1 - p.value, 3))
const fm = (v, i) => (i === 5 ? '₹' : '') + Math.round(v * e.value).toLocaleString('en-IN')

const kpis = computed(() => L.map((l, i) => ({ l, v: fm(c.value.k[i], i), s: c.value.d[i] ? c.value.d[i] + ' vs last week' : '—' })))
const aria = computed(() => c.value.head + ': ' + c.value.lab.map((l, i) => l + ' ' + c.value.ch[i]).join(', '))
const grid = computed(() => [0, 1, 2, 3].map((i) => { const v = Math.round(max.value / 3 * i); return { y: Y(v), ty: Y(v) + 4, v } }))
const chatPath = computed(() => path(c.value.ch))
const callPath = computed(() => path(c.value.ca))
const pts = computed(() => c.value.ch.map((v, i) => ({ x: X(i), y: Y(v), n: c.value.lab[i], r: i === hv.value ? 6 : 4, f: i === hv.value ? '#F2A007' : '#fff', gl: i === hv.value ? '#CBD5E1' : 'transparent', dl: (0.9 + i * 0.08) + 's', rx: X(i) - 330 / (n.value - 1), rw: 660 / (n.value - 1) })))
const tipX = computed(() => 'calc(' + ((X(hv.value) / 760) * 100).toFixed(1) + '% + 20px)')
const tip = computed(() => ({ n: c.value.lab[hv.value].toUpperCase(), c: c.value.ch[hv.value], k: c.value.ca[hv.value] }))

function pick(i) {
  r.value = i
  flip.value += 1
  hov.value = 1
}
</script>

<template>
  <div class="wrap">
    <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
      <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 360px"><span class="eyb"><b>RESULTS</b>·<span>{{ c.range }}</span></span><h1 class="h-lg">What brought people to the shop</h1><p class="ts muted">Chats, calls and QR codes shown at the counter — not likes.</p></div>
      <div class="seg"><button v-for="(x, i) in R" :key="x.n" type="button" :class="r === i ? 'on' : ''" @click="pick(i)">{{ x.n }}</button></div>
      <button type="button" class="btn bs"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v12M7 11l5 5 5-5M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"></path></svg>Export</button>
    </div>
    <div class="strip rise d1"><div v-for="k in kpis" :key="k.l"><span class="lm">{{ k.l }}</span><span class="num">{{ k.v }}</span><span class="tx muted">{{ k.s }}</span></div></div>
    <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: stretch">
      <section class="panel rise d2" style="flex: 2 1 560px; min-width: 0">
        <div class="ph"><span class="eyb"><b>01</b>·<span>{{ c.head }}</span></span><span class="sp"></span><span style="display: inline-flex; gap: 14px" class="tx muted"><span style="display: inline-flex; gap: 6px; align-items: center"><span style="width: 16px; height: 2px; background: #0F172A"></span>Chats</span><span style="display: inline-flex; gap: 6px; align-items: center"><span style="width: 16px; border-top: 2px dashed #94A3B8"></span>Calls</span></span></div>
        <div class="pb" style="position: relative">
          <svg :key="flip" viewBox="0 0 760 250" width="100%" role="img" :aria-label="aria" style="display: block; overflow: visible">
            <g v-for="g in grid" :key="g.v"><line x1="40" x2="750" :y1="g.y" :y2="g.y" stroke="#E2E8F0"></line><text x="30" :y="g.ty" text-anchor="end" font-family="Geist Mono" font-size="11" fill="#94A3B8">{{ g.v }}</text></g>
            <path class="draw" style="--len: 1400" :d="callPath" fill="none" stroke="#94A3B8" stroke-width="1.8" stroke-dasharray="5 5"></path>
            <path class="draw" style="--len: 1400" :d="chatPath" fill="none" stroke="#0F172A" stroke-width="2.2" stroke-linejoin="round"></path>
            <g v-for="(pt, i) in pts" :key="pt.n"><line :x1="pt.x" :x2="pt.x" y1="16" y2="220" :stroke="pt.gl"></line><circle :cx="pt.x" :cy="pt.y" :r="pt.r" :fill="pt.f" stroke="#0F172A" stroke-width="2" class="fade" :style="`animation-delay: ${pt.dl}`"></circle><text :x="pt.x" y="242" text-anchor="middle" font-family="Geist Mono" font-size="11" fill="#64748B">{{ pt.n }}</text><rect :x="pt.rx" y="10" :width="pt.rw" height="215" fill="transparent" @mouseenter="hov = i" @click="hov = i"></rect></g>
          </svg>
          <div class="panel" :style="`position: absolute; top: 18px; left: ${tipX}; padding: 8px 12px; box-shadow: 0 8px 20px rgba(15,23,42,.08); transition: left .25s var(--ease); pointer-events: none`"><span class="mono" style="display: block; color: #64748B">{{ tip.n }}</span><span class="ts"><b>{{ tip.c }}</b> chats · <b>{{ tip.k }}</b> calls</span></div>
        </div>
      </section>
      <section style="flex: 1 1 300px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
        <div class="panel rise d3"><div class="ph"><span class="eyb"><b>02</b>·<span>Best hook</span></span><span class="sp"></span><span class="mono muted">16 chats / 1k views</span></div>
          <div class="pb" style="display: flex; flex-direction: column; gap: 12px"><p class="h-md">“Purana phone laao, ₹2,000 extra pao”</p><p class="tx muted">Offer angle · WhatsApp Status · numbers beat greetings for your customers.</p><RouterLink class="btn bp sm" :to="{ name: 'create' }" style="align-self: flex-start"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"></path></svg>Repeat what worked</RouterLink></div></div>
        <div class="panel rise d4"><div class="ph"><span class="eyb"><b>03</b>·<span>When people reply</span></span></div>
          <div class="pb" style="display: grid; grid-template-columns: 70px repeat(7, minmax(0, 1fr)); gap: 4px; align-items: center">
            <span></span><span v-for="(h, i) in hd" :key="i" class="mono" style="font-size: 10.5px; color: #94A3B8; text-align: center">{{ h }}</span>
            <span v-for="(h, i) in heat" :key="i" :class="h.cls" :style="`background: ${h.bg}; font: 500 10.5px var(--fm); color: #64748B`" :title="h.t">{{ h.l }}</span>
          </div></div>
      </section>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr)); gap: 20px">
      <section class="panel rise d4"><div class="ph"><span class="eyb"><b>04</b>·<span>Chats by channel</span></span></div>
        <div class="pb" style="display: flex; flex-direction: column; gap: 12px"><div v-for="x in chans" :key="x.n" style="display: grid; grid-template-columns: 120px minmax(0, 1fr) 30px; gap: 10px; align-items: center"><span class="ts">{{ x.n }}</span><span class="bar-t" style="height: 8px"><i :style="`width: ${x.w}; animation-delay: ${x.dl}`"></i></span><span class="mono" style="text-align: right">{{ x.v }}</span></div></div></section>
      <section class="panel rise d5"><div class="ph"><span class="eyb"><b>05</b>·<span>Ad money</span></span><span class="sp"></span><span class="st ok live">Running</span></div>
        <div class="pb receipt"><div class="ln"><span class="muted">spent this week</span><span>{{ c.ad[0] }}</span></div><div class="ln"><span class="muted">chats from the ad</span><span>{{ c.ad[1] }}</span></div><div class="ln"><span class="muted">radius</span><span>3 km · College Road</span></div><div class="ln tot"><span>cost per chat</span><span>{{ c.ad[2] }}</span></div></div></section>
      <section class="panel rise d6"><div class="ph"><span class="eyb"><b>06</b>·<span>Win-back</span></span></div>
        <div class="pb" style="display: flex; flex-direction: column; gap: 10px"><p><span class="num">3</span><span class="muted"> of 14 came back</span></p><div style="display: grid; grid-template-columns: repeat(14, minmax(0, 1fr)); gap: 3px"><span v-for="(w, i) in wb" :key="i" class="gy" :style="`height: 22px; border-radius: 3px; background: ${w.c}; animation-delay: ${w.d}`"></span></div><p class="tx muted">Amol, Meena and Imran redeemed their personal codes. Pilot target is 15% — this is 21%.</p></div></section>
    </div>
    <section class="panel rise d6" style="overflow: hidden"><div class="ph"><span class="eyb"><b>07</b>·<span>Top pieces this week</span></span></div>
      <div class="tscroll"><table class="tbl"><thead><tr><th>Piece</th><th>Where</th><th>Hook angle</th><th style="text-align: right">Chats</th><th style="text-align: right">Calls</th><th style="text-align: right">QR</th><th style="text-align: right">Trust</th></tr></thead>
        <tbody><tr v-for="t in top" :key="t.n"><td><span style="display: inline-flex; gap: 8px; align-items: center"><span :class="'ty ' + t.k"></span><b style="font-weight: 500">{{ t.n }}</b></span></td><td class="muted">{{ t.w }}</td><td>{{ t.a }}</td><td class="mono" style="text-align: right">{{ t.c }}</td><td class="mono" style="text-align: right">{{ t.l }}</td><td class="mono" style="text-align: right">{{ t.q }}</td><td class="mono" style="text-align: right">{{ t.t }}</td></tr></tbody></table></div>
    </section>
  </div>
</template>
