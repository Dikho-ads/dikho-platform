<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useApp } from '../../stores/app'

const app = useApp()

const V = [['All', [18400, 742, 610, 41, 23, 17]], ['Meta ad', [11800, 410, 352, 22, 9, 7]], ['WhatsApp + SMS', [290, 85, 80, 8, 7, 5]], ['Organic', [6310, 247, 178, 11, 7, 5]]]
const L = [['Saw it', 'Status, feed, ads, Google'], ['Clicked or scanned', 'links and QR'], ['Opened shop page', 'dikho.in'], ['Enquired', 'chat or call'], ['Came to shop', 'QR at counter'], ['Bought', 'marked by you']]
const G = [
  ['Teaser Status', 'post', 1, 1], ['Offer post', 'post', 2, 1], ['Google offer post', 'post', 3, 8], ['Daily Status', 'free', 2, 7], ['Meta ad · 3 km', 'ad', 4, 7],
  ['WhatsApp to 148', 'camp', 6, 1], ['SMS to 148', 'camp', 7, 1], ['Offer reel', 'video', 9, 1], ['Reminder + win-back', 'camp', 10, 2],
]
const ROWS = [
  ['Teaser Status', 'post', 'Status · 20 Nov', '1,400', '18', '0', 'Done', 'st ink'], ['Offer post', 'post', 'Feed + Status · 21 Nov', '1,900', '96', '9', 'Done', 'st ink'], ['Google offer post', 'post', 'Google · 22 Nov', '640', '31', '2', 'Live', 'st ok'],
  ['Daily Status', 'free', 'Status · 21–27 Nov', '2,370', '102', '0', 'Live', 'st ok'], ['Meta ad · click-to-chat', 'ad', 'Insta + FB · 23–29 Nov', '11,800', '410', '22', 'Live', 'st ok live'], ['WhatsApp offer', 'camp', '148 people · 25 Nov', '142', '64', '6', 'Done', 'st ink'],
  ['SMS', 'camp', '148 people · 26 Nov', '148', '21', '2', 'Done', 'st ink'], ['Offer reel', 'video', 'Insta + Status · 28 Nov', '—', '—', '—', 'Scheduled', 'st'], ['Reminder + win-back', 'camp', 'WhatsApp · 29–30 Nov', '—', '—', '—', 'Scheduled', 'st'],
].map(([n, k, w, v, c, e, s, sc]) => ({ n, k, w, v, c, e, s, sc }))
const LINKS = [{ u: 'dkho.in/sai/2k', d: 'Posts, Status, WhatsApp, SMS', n: '332' }, { u: 'dkho.in/sai/ad', d: 'Meta ad → chat or page', n: '410' }, { u: 'QR · counter flex', d: 'Scanned in the shop', n: '23' }]
const DAYS = Array.from({ length: 11 }, (_, i) => ({ n: (20 + i) + '', c: i === 6 ? '#0F172A' : '#94A3B8' }))
const GANTT = G.map(([n, k, st, len], i) => ({ n, k, col: (st + 1) + ' / span ' + len, c: k === 'ad' ? 'repeating-linear-gradient(135deg,#0F172A 0 3px,#475569 3px 6px)' : (k === 'video' ? '#0F172A' : (k === 'camp' ? '#F2A007' : (k === 'free' ? '#CBD5E1' : '#64748B'))), dl: (0.2 + i * 0.06) + 's' }))
const AREAS = [['College Road', 38], ['Gangapur Road', 17], ['Panchavati', 12], ['CIDCO', 9], ['Indira Nagar', 7], ['Other', 17]].map(([n, p], i) => ({ n, p: p + '%', w: (p / 38 * 100) + '%', c: i === 0 ? '#F2A007' : '#0F172A', dl: (0.3 + i * 0.07) + 's' }))

const v = ref(0)
const p = ref(0)
const paused = ref(false)
const toast = ref('')
let raf = null
let tt = null

function go() {
  cancelAnimationFrame(raf)
  const t0 = performance.now()
  const tick = (t) => {
    p.value = Math.min(1, (t - t0) / 1000)
    if (p.value < 1) raf = requestAnimationFrame(tick)
  }
  raf = requestAnimationFrame(tick)
}
onMounted(go)
onBeforeUnmount(() => { cancelAnimationFrame(raf); clearTimeout(tt) })

function show(m) {
  toast.value = m
  clearTimeout(tt)
  tt = setTimeout(() => { toast.value = '' }, 3000)
}
function pause() {
  paused.value = !paused.value
  show(paused.value ? 'Campaign paused · ads stop within minutes' : 'Campaign resumed')
}
function dup() { show('Copied as “Sankranti Exchange Mela” · dates moved to 5 – 17 Jan') }
function pickView(i) { v.value = i; go() }

const arr = computed(() => V[v.value][1])
const e = computed(() => 1 - Math.pow(1 - p.value, 3))
const funnel = computed(() => arr.value.map((n, i) => ({
  l: L[i][0], s: L[i][1], v: Math.round(n * e.value).toLocaleString('en-IN'),
  h: Math.round(16 + 30 * Math.log10(Math.max(n, 1))) + 'px', c: i >= 4 ? '#F2A007' : (i === 3 ? '#0F172A' : '#CBD5E1'), dl: (i * 0.08) + 's',
})))
const conv = computed(() => arr.value.slice(1).map((n, i) => (Math.round(n / arr.value[i] * 1000) / 10) + '% →'))
const slug = computed(() => app.shop.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''))
</script>

<template>
  <div class="wrap" style="max-width: 1400px">
    <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
      <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 440px">
        <span class="eyb"><b>CAMPAIGN</b>·<span>20 – 30 NOV · TODAY IS DAY 7</span></span>
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap">
          <h1 class="h-lg">Shaadi Exchange Mela</h1>
          <span :class="paused ? 'st warn' : 'st ok live'">{{ paused ? 'Paused' : 'Live' }}</span>
        </div>
        <p class="ts muted">Goal 60 enquiries · Kharidari stage · 9 pieces across 6 channels · made in Plan mode</p>
      </div>
      <button type="button" class="btn bs" @click="pause">{{ paused ? 'Resume' : 'Pause' }}</button>
      <button type="button" class="btn bs" @click="dup">Copy for Sankranti</button>
      <RouterLink class="btn bp" :to="{ name: 'studio-plan-result' }">Open plan</RouterLink>
    </div>
    <section class="panel rise d1">
      <div class="ph" style="flex-wrap: wrap"><span class="eyb"><b>01</b>·<span>From seeing to buying</span></span><span class="sp"></span>
        <div class="seg"><button v-for="(x, i) in V" :key="x[0]" type="button" :class="v === i ? 'on' : ''" @click="pickView(i)">{{ x[0] }}</button></div>
      </div>
      <div class="pb">
        <div :key="v" style="display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 14px; align-items: end">
          <div v-for="f in funnel" :key="f.l" class="fs">
            <span class="lm">{{ f.l }}</span><span class="num">{{ f.v }}</span>
            <span class="fb" :style="{ height: f.h, background: f.c, animationDelay: f.dl }"></span>
            <span class="tx muted">{{ f.s }}</span>
          </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 14px; margin-top: 10px">
          <span></span>
          <span v-for="(c, i) in conv" :key="i" class="cv" style="justify-self: start">{{ c }}</span>
        </div>
        <p class="tx muted" style="margin-top: 14px">“Came to shop” is counted when the campaign’s QR is scanned at the counter; “bought” is what you mark in one tap. Every click and scan passes through your dikho.in page — that’s why these numbers line up.</p>
      </div>
    </section>
    <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
      <div style="flex: 2 1 620px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
        <section class="panel rise d2" style="overflow: hidden">
          <div class="ph"><span class="eyb"><b>02</b>·<span>Timeline</span></span><span class="sp"></span><span class="tx muted">Marigold line = today, Thu 26 Nov</span></div>
          <div style="position: relative">
            <div class="gt" style="background: #F8FAFC; min-height: 32px"><span class="lm" style="padding-left: 16px">Piece</span><span v-for="d in DAYS" :key="d.n" class="mono" :style="{ fontSize: '10.5px', color: d.c, textAlign: 'center' }">{{ d.n }}</span></div>
            <div v-for="g in GANTT" :key="g.n" class="gt">
              <span style="display: flex; align-items: center; gap: 8px; padding-left: 16px; font-size: 13px"><span :class="'ty ' + g.k"></span>{{ g.n }}</span>
              <span :style="{ gridColumn: g.col, padding: '0 3px' }"><span class="gbar" :style="{ display: 'block', background: g.c, animationDelay: g.dl }"></span></span>
            </div>
            <span style="position: absolute; top: 0; bottom: 0; left: calc(190px + (100% - 190px) * 6.5 / 11); width: 2px; background: #F2A007"></span>
          </div>
        </section>
        <section class="panel rise d3" style="overflow: hidden">
          <div class="ph"><span class="eyb"><b>03</b>·<span>What each piece did</span></span></div>
          <div class="tscroll">
            <table class="tbl">
              <thead><tr><th>Piece</th><th>Where</th><th style="text-align: right">Saw it</th><th style="text-align: right">Clicks + scans</th><th style="text-align: right">Enquiries</th><th>Status</th></tr></thead>
              <tbody>
                <tr v-for="r in ROWS" :key="r.n">
                  <td><span style="display: inline-flex; gap: 8px; align-items: center"><span :class="'ty ' + r.k"></span><b style="font-weight: 500">{{ r.n }}</b></span></td>
                  <td class="muted">{{ r.w }}</td>
                  <td class="mono" style="text-align: right">{{ r.v }}</td>
                  <td class="mono" style="text-align: right">{{ r.c }}</td>
                  <td class="mono" style="text-align: right">{{ r.e }}</td>
                  <td><span :class="r.sc">{{ r.s }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
      <aside style="flex: 1 1 340px; min-width: 0; display: flex; flex-direction: column; gap: 20px">
        <section class="panel rise d2" style="overflow: hidden">
          <div class="ph"><span class="eyb"><b>04</b>·<span>Your shop page</span></span><span class="sp"></span><span class="st ok live">On Google</span></div>
          <div class="url"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 10.5h15v10h-15zM8 10.5V7a4 4 0 0 1 8 0v3.5"></path></svg>dikho.in/{{ app.shop.city.toLowerCase() }}/{{ slug }}</div>
          <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px">
            <div style="display: flex; gap: 12px; align-items: center">
              <span class="mt" style="width: 44px; height: 44px; background: #1D3A8A">{{ app.shop.initials }}</span>
              <span><span class="h-md" style="display: block">{{ app.shop.name }}</span><span class="tx muted">Mobile shop · College Road, {{ app.shop.city }} · ★ 4.6 (312)</span></span>
            </div>
            <div style="background: #FFD23F; color: #1D3A8A; border-radius: 10px; padding: 10px 12px; font: 600 13.5px/1.35 var(--f)">Shaadi Exchange Mela — ₹2,000 extra on your old phone · till 29 Nov</div>
            <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px"><span class="btn bp sm">WhatsApp</span><span class="btn bs sm">Call</span><span class="btn bs sm">Directions</span></div>
            <span class="tx muted">Open now · till 9 pm · near bus stand · closed Mondays</span>
          </div>
          <div class="receipt" style="padding: 4px 16px 14px; border-top: 1px dashed #CBD5E1">
            <div class="ln"><span class="muted">page visits</span><span>610</span></div>
            <div class="ln"><span class="muted">found on Google for</span><span>mobile shop college road</span></div>
            <div class="ln"><span class="muted"></span><span>vivo exchange offer nashik</span></div>
            <div class="ln"><span class="muted"></span><span>screen repair near bus stand</span></div>
          </div>
        </section>
        <section class="panel rise d3">
          <div class="ph"><span class="eyb"><b>05</b>·<span>Where enquiries came from</span></span></div>
          <div class="pb" style="display: flex; flex-direction: column; gap: 10px">
            <div v-for="a in AREAS" :key="a.n" style="display: grid; grid-template-columns: 120px minmax(0, 1fr) 40px; gap: 10px; align-items: center">
              <span class="ts">{{ a.n }}</span>
              <span class="bar-t" style="height: 8px"><i :style="{ width: a.w, animationDelay: a.dl, background: a.c }"></i></span>
              <span class="mono" style="text-align: right">{{ a.p }}</span>
            </div>
          </div>
        </section>
        <section class="panel rise d4">
          <div class="ph"><span class="eyb"><b>06</b>·<span>Tracked links</span></span></div>
          <div v-for="l in LINKS" :key="l.u" class="row" style="padding: 10px 18px">
            <span class="grow"><span class="mono" style="display: block; font-size: 12.5px">{{ l.u }}</span><span class="tx muted">{{ l.d }}</span></span>
            <span class="mono">{{ l.n }}</span>
          </div>
        </section>
      </aside>
    </div>
    <div v-if="toast" class="toast" role="status">{{ toast }}</div>
  </div>
</template>

<style scoped>
.fs{display:flex;flex-direction:column;gap:8px;min-width:0}.fb{border-radius:8px 8px 3px 3px;transform-origin:bottom;animation:growY .9s var(--ease) both}.cv{align-self:center;font:500 11px/1 var(--fm);color:#64748B;padding:4px 6px;border:1px dashed #CBD5E1;border-radius:6px;background:#fff}.gt{display:grid;grid-template-columns:190px repeat(11,minmax(0,1fr));align-items:center;min-height:38px;border-bottom:1px solid #E2E8F0}.gt:last-child{border-bottom:0}.gbar{height:14px;border-radius:4px;transform-origin:left;animation:growX .8s var(--ease) both}.url{display:flex;align-items:center;gap:8px;padding:8px 12px;background:#F1F5F9;border-bottom:1px solid #E2E8F0;font:500 12px var(--fm);color:#334155}
</style>
