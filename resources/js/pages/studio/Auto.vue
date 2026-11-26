<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useApp } from '../../stores/app'

const app = useApp()

const C = [
  { id: 'a1', st: 0, fmt: 'REEL 15 S', ty: 'video', k: 'video', when: 'SAT 28 · 7:30 PM', ch: 'Insta + Status', lang: 'हिन्दी', lc: 'hi', pillar: 'Tyohaar', pc: 'pst r916 k', h: ['शादी में नया फ़ोन? पहले यहाँ देखो', 'शादी का तोहफ़ा — सही फ़ोन, सही दाम'] },
  { id: 'a2', st: 0, fmt: 'GOOGLE POST', ty: 'post', k: 'visual', when: 'MON 30', ch: 'Google listing', lang: 'English', lc: 'en', pillar: 'Mohalla', pc: 'pst r45 w', h: ['College Road’s trusted mobile shop, since 2014', 'Screen repair in 1 hour, near the bus stand'] },
  { id: 'a3', st: 0, fmt: 'STATUS', ty: 'post', k: 'visual', when: 'TUE 1 · 9 AM', ch: 'WhatsApp Status', lang: 'मराठी', lc: 'mr', pillar: 'Tyohaar', pc: 'pst r916 y', h: ['लग्नसराई सुरू! गिफ्टसाठी नवा फोन?', 'लग्नाचा मुहूर्त, नव्या फोनचा मुहूर्त'] },
  { id: 'b1', st: 1, fmt: 'CAROUSEL', ty: 'post', k: 'visual', when: 'THU 3 · 6 PM', ch: 'Instagram', lang: 'Hinglish', lc: 'en', pillar: 'Jaankari', pc: 'pst r45', h: ['Phone lene se pehle 5 cheezein check karein', 'Second-hand phone? Yeh 4 galtiyan mat karna'] },
  { id: 'b2', st: 1, fmt: 'CUSTOMER STORY', ty: 'post', k: 'visual', when: 'FRI 4 · 6:30 PM', ch: 'Feed + Status', lang: 'Hinglish', lc: 'en', pillar: 'Bharosa', pc: 'pst r45 w', h: ['“Exchange mein ₹4,000 zyada mile” — Priya', '“1 ghante mein screen naya” — Amol'], need: 'Confirm Priya agreed to be quoted' },
  { id: 'b3', st: 1, fmt: 'REEL SCRIPT', ty: 'video', k: 'video', when: 'SUN 6 · 8 PM', ch: 'Instagram', lang: 'हिन्दी', lc: 'hi', pillar: 'Dukaan ki kahani', pc: 'pst r916 k', h: ['दुकान के अंदर: 30 मिनट में स्क्रीन बदली', 'रमेश भाई का 12 साल का हुनर'] },
  { id: 'c1', st: 2, fmt: 'POST', ty: 'post', k: 'visual', when: 'SAT 28 · 7:30 PM', ch: 'Status + Feed', lang: 'Hinglish', lc: 'en', pillar: 'Bachat', pc: 'pst r45 y', h: ['Purana phone laao, ₹2,000 extra pao', 'Exchange Mela: ₹2,000 extra, Sunday tak'] },
  { id: 'c2', st: 2, fmt: 'WHATSAPP', ty: 'camp', k: 'text', when: 'SUN 29 · 10 AM', ch: '148 said yes', lang: 'Hinglish', lc: 'en', pillar: 'Bachat', h: ['Namaste {naam} ji! Shaadi season offer: purane phone pe ₹2,000 extra, Sunday tak. Offer dekho: dikho.in/sai/2k', '{naam} ji, ghar mein pada purana phone ab ₹2,000 zyada ka! Sirf Sunday tak — Sai Mobile, College Road.'] },
  { id: 'c3', st: 2, fmt: 'META AD', ty: 'ad', k: 'ad', when: 'TUE 1 → 7 DAYS', ch: '3 km · click-to-chat', lang: 'Hinglish', lc: 'en', pillar: 'Bachat', pc: 'pst r45 y', h: ['Exchange pe ₹2,000 extra — College Road, Nashik', 'Naya 5G phone, purane phone ke badle'], need: 'Needs ₹160 more in wallet' },
  { id: 'c4', st: 2, fmt: 'SMS · DLT', ty: 'camp', k: 'text', when: 'WED 2 · 11 AM', ch: 'SMS · 148', lang: 'Hinglish', lc: 'en', pillar: 'Bachat', h: ['SAIMOB: Purane phone pe Rs.2000 extra, Sunday tak. College Rd, near bus stand. dikho.in/sai/2k', 'SAIMOB: Exchange Mela! Rs.2000 extra on old phone till Sun. dikho.in/sai/2k'] },
  { id: 'd1', st: 3, fmt: 'WHATSAPP · AUTO', ty: 'camp', k: 'text', when: '+30 DAYS AFTER BUYING', ch: 'Workflow', lang: 'Hinglish', lc: 'en', pillar: 'Wapsi', h: ['{naam} ji, naya phone kaisa chal raha hai? Cover aur glass pe 20% off sirf aapke liye.', '{naam} ji, phone theek chal raha hai na? Koi dikkat ho to bas reply karein.'] },
  { id: 'd2', st: 3, fmt: 'BIRTHDAY WISH', ty: 'camp', k: 'text', when: 'ON THE DAY · 9 AM', ch: 'Workflow', lang: 'Hinglish', lc: 'en', pillar: 'Wapsi', h: ['Janamdin mubarak {naam} ji! Aapke liye ₹100 ka voucher — is hafte kabhi bhi aayiye.', 'Happy birthday {naam} ji! Sai Mobile parivaar ki taraf se ₹100 ka tohfa.'] },
  { id: 'd3', st: 3, fmt: 'REVIEW ASK', ty: 'free', k: 'text', when: '2 DAYS AFTER VISIT', ch: 'Workflow', lang: 'मराठी', lc: 'mr', pillar: 'Bharosa', h: ['{naam} जी, आमची सेवा कशी वाटली? 30 सेकंदात Google वर सांगा: dikho.in/sai/r', '{naam} जी, तुमचा अनुभव दोन ओळीत सांगाल? dikho.in/sai/r'] }
]
const ST = [['Pehchaan', 'Awareness', 'New people learn the shop exists'], ['Bharosa', 'Trust', 'Proof, tips and the shop’s story'], ['Kharidari', 'Buying', 'Offers with a reason to come now'], ['Wapsi', 'Coming back', 'Check-ins, wishes, reviews']]
const F = [['All', () => true], ['Visual', (k) => k.k === 'visual'], ['Video', (k) => k.k === 'video'], ['Text', (k) => k.k === 'text'], ['Ads', (k) => k.k === 'ad']]
const T = [['Apnapan', 'Warm, like talking to a regular: “ji”, “aapke liye”.'], ['Seedhi baat', 'Short and clear. Price first, no fluff.'], ['Mazedaar', 'Light humour and local idiom — never about people.']]
const SRC = [['shop', 'Shop profile', 'name, landmark, hours'], ['prod', 'Products & offer', '₹2,000 exchange, till Sun'], ['cal', 'Nashik calendar', 'wedding season, Christmas'], ['cust', 'Customers', '148 said yes to offers'], ['res', 'What worked', 'offer hooks, 7–9 pm'], ['mom', 'Moments', 'salary week, match days']]
const LANGS = ['Hinglish', 'हिन्दी', 'मराठी', 'English']
const PERIODS = ['7 days', '14 days', '30 days']
const RULES = ['I review everything', 'Auto-approve trust 90+', 'Only daily posts go alone']
const sum = [['Pieces', 38], ['Posts', 14], ['Reels', 4], ['WhatsApp', 9], ['SMS', 6], ['Google + ads', 5]].map(([l, v]) => ({ l, v }))

const src = ref(['shop', 'prod', 'cal', 'cust', 'res', 'mom'])
const period = ref(1)
const langs = ref(['Hinglish', 'हिन्दी', 'मराठी'])
const tone = ref(0)
const value = ref(60)
const rule = ref(1)
const filter = ref(0)
const ok = ref(['c1', 'a2', 'b1', 'd2'])
const alt = ref({})
const spin = ref('')
const gen = ref(false)
const step = ref(0)
const toast = ref('')
let iv = null, tt = null, rt = null

function show(m) {
  toast.value = m
  clearTimeout(tt)
  tt = setTimeout(() => { toast.value = '' }, 3000)
}

onMounted(() => {
  iv = setInterval(() => {
    if (!gen.value) return
    if (step.value >= 6) {
      gen.value = false
      show('38 pieces ready · 6 need you')
    } else step.value += 1
  }, 520)
})
onBeforeUnmount(() => { clearInterval(iv); clearTimeout(tt); clearTimeout(rt) })

const days = computed(() => [7, 14, 30][period.value])
const ready = computed(() => C.filter((k) => !k.need && !ok.value.includes(k.id)).length)
const approveTxt = computed(() => (ready.value ? 'Approve ' + ready.value + ' ready' : 'All approved'))
const toneNote = computed(() => T[tone.value][1])
const steps = computed(() => ['Reading shop info', 'Calendar & moments', 'Writing in ' + langs.value.join(', '), 'Designing in your colours', 'Trust check', 'Done'].map((n, i) => ({ n, sc: i < step.value ? 'st ok' : (i === step.value ? 'st acc live' : 'st') })))
const filters = computed(() => F.map(([n, f], i) => ({ n, c: C.filter(f).length, i })))
const cols = computed(() => {
  const fil = F[filter.value][1]
  const vis = C.filter(fil)
  let idx = 0
  return ST.map(([n, en, d], ci) => ({
    n, en, d, count: C.filter((k) => k.st === ci).length,
    cards: vis.filter((k) => k.st === ci).map((k) => {
      const done = ok.value.includes(k.id)
      return { ...k, hook: k.h[alt.value[k.id] || 0], isOk: done, needs: !!k.need && !done, status: done ? 'Approved' : (k.need ? 'Needs you' : 'Draft'), dl: (idx++ * 0.05) + 's' }
    })
  }))
})

function toggleSrc(id) { src.value = src.value.includes(id) ? src.value.filter((x) => x !== id) : src.value.concat(id) }
function toggleLang(n) { langs.value = langs.value.includes(n) ? langs.value.filter((x) => x !== n) : langs.value.concat(n) }
function approve(id) { ok.value = ok.value.includes(id) ? ok.value.filter((x) => x !== id) : ok.value.concat(id) }
function approveAll() {
  const n = ready.value
  ok.value = C.filter((k) => !k.need).map((k) => k.id).concat(ok.value.filter((x) => C.find((k) => k.id === x && k.need)))
  show(n + ' pieces approved · they’ll go out on their days')
}
function generate() {
  if (!app.spend(10)) { show('Not enough credits to regenerate everything'); return }
  gen.value = true
  step.value = 0
}
function regen(id) {
  if (spin.value) return
  if (!app.spend(2)) { show('Not enough credits to make another version'); return }
  spin.value = id
  const cur = alt.value[id] || 0
  clearTimeout(rt)
  rt = setTimeout(() => {
    spin.value = ''
    alt.value = Object.assign({}, alt.value, { [id]: cur ? 0 : 1 })
  }, 650)
}
</script>

<template>
  <div class="wrap" style="max-width: 1400px">
    <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
      <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 420px"><span class="eyb"><b>AUTO MODE</b>·<span>अपने आप · MADE 7:00 AM TODAY</span></span><h1 class="h-lg">Next {{ days }} days, made for {{ app.shop.name }}</h1><p class="ts muted">Built from your shop info, {{ app.shop.city }}’s calendar and what worked last month. Approve what you like — edit or swap the rest.</p></div>
      <div class="seg"><button type="button" class="on">Auto</button><RouterLink :to="{ name: 'studio-plan' }">Plan</RouterLink></div>
      <button type="button" class="btn bs" @click="generate"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"></path></svg>Regenerate all</button>
      <button type="button" class="btn bp" @click="approveAll">{{ approveTxt }}</button>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
      <aside class="panel rise d1" style="flex: 1 1 270px; max-width: 320px; min-width: 0">
        <div class="ph"><span class="eyb"><b>01</b>·<span>What to make</span></span></div>
        <div class="pb" style="display: flex; flex-direction: column; gap: 18px">
          <div style="display: flex; flex-direction: column"><span class="lm" style="margin-bottom: 4px">Made from</span>
            <button v-for="[id, n, d] in SRC" :key="id" type="button" class="tg" style="border: 0; background: none; cursor: pointer; text-align: left; width: 100%" @click="toggleSrc(id)"><span class="grow"><span class="ts" style="font-weight: 500; display: block">{{ n }}</span><span class="tx muted">{{ d }}</span></span><span :class="'sw' + (src.includes(id) ? ' on' : '')"></span></button></div>
          <div class="fld"><span class="lm">Period</span><div class="seg full"><button v-for="(n, i) in PERIODS" :key="n" type="button" :class="period === i ? 'on' : ''" @click="period = i">{{ n }}</button></div></div>
          <div class="fld"><span class="lm">Languages</span><div class="chips"><button v-for="n in LANGS" :key="n" type="button" :class="'chip' + (langs.includes(n) ? ' on' : '')" @click="toggleLang(n)">{{ n }}</button></div></div>
          <div class="fld"><span class="lm">Tone · andaaz</span><div class="seg full"><button v-for="(t, i) in T" :key="t[0]" type="button" :class="tone === i ? 'on' : ''" @click="tone = i">{{ t[0] }}</button></div><span class="hint">{{ toneNote }}</span></div>
          <div class="fld"><span style="display: flex; justify-content: space-between"><span class="lm">Value vs offers</span><span class="mono">{{ value }} / {{ 100 - value }}</span></span><input v-model.number="value" type="range" min="40" max="90" step="10" aria-label="Share of value content" style="accent-color: #0F172A"><span class="hint">Tips, trust and festivals keep people following; offers make them buy.</span></div>
          <div class="fld"><span class="lm">Approval</span><button v-for="(n, i) in RULES" :key="n" type="button" :class="'opt' + (rule === i ? ' on' : '')" style="padding: 9px 11px" @click="rule = i"><span class="rd"></span><span class="ts">{{ n }}</span></button></div>
        </div>
      </aside>
      <section style="flex: 4 1 640px; min-width: 0; display: flex; flex-direction: column; gap: 14px">
        <div class="strip rise d2"><div v-for="k in sum" :key="k.l" style="padding: 12px 16px; gap: 4px"><span class="lm">{{ k.l }}</span><span class="num" style="font-size: 24px">{{ k.v }}</span></div></div>
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px"><div class="tabs" style="border-bottom: 0"><button v-for="f in filters" :key="f.n" type="button" :class="filter === f.i ? 'on' : ''" @click="filter = f.i">{{ f.n }}<span class="n">{{ f.c }}</span></button></div><span class="sp"></span><span class="tx muted">Credits this period <b style="color: #0F172A">71</b> of {{ app.creditsLeft }} · wallet ≈ ₹164</span></div>
        <div v-if="gen" class="panel fade" style="padding: 14px 18px; display: flex; flex-wrap: wrap; gap: 18px; align-items: center"><svg class="i spin" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9"></path></svg><span v-for="g in steps" :key="g.n" :class="g.sc" style="font-size: 12.5px">{{ g.n }}</span></div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(230px, 100%), 1fr)); gap: 14px; align-items: start">
          <div v-for="c in cols" :key="c.n" class="col">
            <div class="colh"><span style="display: flex; justify-content: space-between; align-items: baseline"><span class="h-sm">{{ c.n }} <span class="muted" style="font-weight: 400">· {{ c.en }}</span></span><span class="mono">{{ c.count }}</span></span><span class="tx muted">{{ c.d }}</span></div>
            <template v-if="gen"><div class="sk" style="height: 150px"></div><div class="sk" style="height: 110px; animation-delay: .3s"></div><div class="sk" style="height: 150px; animation-delay: .6s"></div></template>
            <template v-else>
              <div v-for="k in c.cards" :key="k.id" :class="'cx' + (k.isOk ? ' ok' : '')" :style="`animation-delay: ${k.dl}`">
                <div style="display: flex; align-items: center; gap: 7px"><span :class="'ty ' + k.ty"></span><span class="mono" style="font-size: 11px">{{ k.fmt }}</span><span class="sp"></span><span class="mono muted" style="font-size: 10.5px">{{ k.when }}</span></div>
                <div v-if="k.k !== 'text'" style="display: flex; gap: 10px; align-items: flex-start"><div :class="k.pc" style="width: 54px; flex-shrink: 0; font-size: 3.3px"><div class="pad" style="flex: 1; justify-content: flex-end"><span class="hk">{{ k.hook }}</span></div><div class="ft"><b>{{ app.shop.short }}</b></div></div><p class="ts" :lang="k.lc" style="font-weight: 500; line-height: 19px">{{ k.hook }}</p></div>
                <p v-else class="bubx" :lang="k.lc">{{ k.hook }}</p>
                <div style="display: flex; flex-wrap: wrap; gap: 5px"><span class="tag">{{ k.lang }}</span><span class="tag ghost">{{ k.pillar }}</span><span class="tag ghost">{{ k.ch }}</span></div>
                <span v-if="k.needs" class="st warn" style="font-size: 12px">{{ k.need }}</span>
                <div style="display: flex; align-items: center; gap: 6px; border-top: 1px dashed #E2E8F0; padding-top: 9px"><button type="button" :class="'ck' + (k.isOk ? ' on' : '')" aria-label="Approve" style="width: 20px; height: 20px" @click="approve(k.id)"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg></button><span class="tx" :style="`color: ${k.isOk ? '#0F172A' : '#64748B'}`">{{ k.status }}</span><span class="sp"></span><button type="button" class="btn bg ib sm" aria-label="Make another version" @click="regen(k.id)"><svg :class="'i s' + (spin === k.id ? ' spin' : '')" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"></path></svg></button><RouterLink class="btn bs sm" :to="{ name: 'studio-editor' }">Edit</RouterLink></div>
              </div>
            </template>
          </div>
        </div>
      </section>
    </div>
    <div v-if="toast" class="toast" role="status">{{ toast }}</div>
  </div>
</template>

<style scoped>
.col{display:flex;flex-direction:column;gap:10px;min-width:0}.colh{display:flex;flex-direction:column;gap:2px;padding:12px 14px;border:1px solid #E2E8F0;border-radius:12px;background:#F8FAFC}.cx{display:flex;flex-direction:column;gap:10px;padding:12px;border:1px solid #E2E8F0;border-radius:12px;background:#fff;transition:border-color .2s,transform .25s var(--ease),box-shadow .2s;animation:rise .55s var(--ease) both}.cx:hover{border-color:#CBD5E1;transform:translateY(-2px)}.cx.ok{border-color:#0F172A}.bubx{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:3px 12px 12px 12px;padding:8px 10px;font-size:12.5px;line-height:18px;color:#334155}.sk{border-radius:12px;background:#F1F5F9;animation:breathe 1.6s ease-in-out infinite}.tg{display:flex;align-items:center;gap:10px;padding:7px 0}
</style>
