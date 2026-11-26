<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'

const K = { TRIGGER: ['#0F172A', '#fff'], WAIT: ['#F1F5F9', '#334155'], CHECK: ['#fff', '#0F172A'], SEND: ['#FEF3DC', '#8A5300'], END: ['#F1F5F9', '#64748B'] }
const R = [
  { n: 'Missed call → WhatsApp', stat: '37 calls caught this month', nodes: [['TRIGGER', 'Missed call on your shop number', '+91 98765 43210', '37'], ['CHECK', 'Shop open right now?', 'Uses your hours: 10 am – 9 pm', ''], ['SEND', 'Reply on WhatsApp within 1 minute', 'Namaste! Abhi call nahi utha paaye. Kya chahiye? 1 Naya phone · 2 Repair · 3 Offer', '37 sent'], ['WAIT', 'Wait for their reply', 'up to 2 hours', ''], ['SEND', 'Answer their choice', 'With a link to your dikho.in page', '29 sent'], ['END', 'Saved as a lead in Customers', 'You get one ping', '29 leads']] },
  { n: 'Welcome new customer', stat: '22 welcomed this month', nodes: [['TRIGGER', 'First bill or first QR scan', 'Marked by you or by the QR', '22'], ['CHECK', 'Said yes to messages?', 'Asked at the counter', ''], ['SEND', 'Thank-you + what we fix', 'Dhanyavaad {naam} ji! Phone, repair, accessories — sab ek jagah.', '22 sent'], ['END', 'Added to New', '', '']] },
  { n: '30-day check-in', stat: '64 sent · 18 replied', nodes: [['TRIGGER', 'Phone sold', 'Marked in your bill or by you', '64'], ['WAIT', '30 days', 'Counted from the bill date', ''], ['CHECK', 'Said yes to messages?', 'No → stop here', ''], ['SEND', 'How is the new phone?', '{naam} ji, naya phone kaisa chal raha hai? Cover aur glass pe 20% off sirf aapke liye — dkho.in/sai/cg', '64 · 51 read'], ['WAIT', '2 days', 'If they reply, you see it first', ''], ['CHECK', 'Replied or came in?', 'Yes → review ask · No → end', ''], ['SEND', 'Ask for a Google review', 'dkho.in/sai/r — 30 seconds', '18 sent'], ['END', 'Moves to Regular', '', '']] },
  { n: 'Birthday wish + ₹100', stat: '9 wishes this month', nodes: [['TRIGGER', 'Birthday in the customer book', '86 birthdays on file', '9'], ['SEND', 'Wish + ₹100 voucher', 'Janamdin mubarak {naam} ji! ₹100 ka voucher is hafte.', '9 sent'], ['END', 'Voucher counted when used', '', '3 used']] },
  { n: 'Review request', stat: '41 asked · 12 reviews', nodes: [['TRIGGER', 'Visit marked or QR redeemed', '', '41'], ['WAIT', '2 days', '', ''], ['SEND', 'Ask for a review', 'Aapka anubhav Google pe batayein: dkho.in/sai/r', '41 sent'], ['CHECK', 'Rated 4 or 5?', 'Yes → thank-you · No → you are told privately', ''], ['END', '12 new reviews', '', '★ 4.6']] },
  { n: 'Win-back · lapsed', stat: 'Starts tomorrow · 14 people', nodes: [['TRIGGER', 'Missed 3 usual visits', 'Judged on each person’s own cycle', '14'], ['SEND', 'Nudge', 'Kaafi din ho gaye! Naye 5G phones aaye hain.', ''], ['WAIT', '4 days', '', ''], ['SEND', 'Personal offer code', 'Tempered glass free with any repair · code AMOLG', ''], ['END', 'Stops the moment they come back', '', '']] },
  { n: 'Festival countdown', stat: 'Suggested for Sankranti', nodes: [['TRIGGER', '7 days before a festival', 'From the Nashik calendar', ''], ['SEND', 'Countdown Status', '7 din baaki…', ''], ['END', 'Hands over to the campaign', '', '']] },
  { n: 'New stock alert', stat: 'Off · 31 people asked', nodes: [['TRIGGER', 'You add new stock', 'Photo + price', ''], ['CHECK', 'Asked about it before?', 'From WhatsApp chats', ''], ['SEND', 'Tell them it’s here', 'Jo aapne poocha tha, aa gaya hai!', ''], ['END', 'Lead saved', '', '']] },
]
const HELP = { TRIGGER: 'What starts this workflow. It watches your bills, QR scans, missed calls and customer book — you don’t have to do anything.', WAIT: 'A pause, so messages arrive at a natural moment — never between 9 pm and 9 am.', CHECK: 'A rule. People who never said yes are always skipped; anyone who replies STOP leaves every workflow.', END: 'Where the workflow stops. The customer’s stage updates and you get a single ping, not a flood.' }
const LANGS = ['Hinglish', 'हिन्दी', 'मराठी']
const LCS = ['en', 'hi', 'mr']
const REWRITES = [['Aur apnapan', 'Warmer · saved'], ['Chhota karo', 'Shorter · saved'], ['Marathi bhi', 'Marathi copy added']]
const ML = [null, '{naam} जी, नया फ़ोन कैसा चल रहा है? कवर और ग्लास पर 20% छूट सिर्फ़ आपके लिए।', '{naam} जी, नवा फोन कसा चालतोय? कव्हर आणि ग्लासवर 20% सूट फक्त तुमच्यासाठी.']

const sel = ref(2)
const node = ref(3)
const live = ref([0, 1, 2, 3, 4])
const lang = ref(0)
const toast = ref('')
let tt = null

function show(m) {
  toast.value = m
  clearTimeout(tt)
  tt = setTimeout(() => { toast.value = '' }, 2800)
}
onBeforeUnmount(() => clearTimeout(tt))

const r = computed(() => R[sel.value])
const ni = computed(() => Math.min(node.value, r.value.nodes.length - 1))
const nd = computed(() => r.value.nodes[ni.value])
const isSend = computed(() => nd.value[0] === 'SEND')
const on = computed(() => live.value.includes(sel.value))
const isDemoMsg = computed(() => sel.value === 2 && ni.value === 3)
const msg = computed(() => lang.value === 0 || !isDemoMsg.value ? nd.value[2] : ML[lang.value])
const nodes = computed(() => r.value.nodes.map(([kind, t, d, stat], i) => ({
  kind, t, d, stat, link: i > 0, lc: /[ऀ-ॿ]/.test(d) ? 'hi' : 'en', kb: K[kind][0], kc: K[kind][1], dl: (i * 0.06) + 's', branch: kind === 'CHECK',
})))
const nstats = computed(() => {
  const d = nd.value
  return isSend.value
    ? [{ l: 'Sent', v: (d[3] || '0').split(' ')[0] }, { l: 'Read', v: isDemoMsg.value ? '51' : '—' }, { l: 'Replied', v: isDemoMsg.value ? '18' : '—' }]
    : [{ l: 'This month', v: (d[3] || '—').split(' ')[0] || '—' }, { l: 'Skipped', v: d[0] === 'CHECK' ? '6' : '0' }, { l: 'Errors', v: '0' }]
})

function pickRecipe(i) { sel.value = i; node.value = 0 }
function toggle(i) {
  const was = live.value.includes(i)
  live.value = was ? live.value.filter((x) => x !== i) : live.value.concat(i)
  show(R[i].n + (was ? ' paused' : ' is live'))
}
</script>

<template>
  <div class="wrap" style="max-width: 1400px">
    <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
      <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 420px">
        <span class="eyb"><b>WORKFLOWS</b>·<span>APNE AAP CHALNE WALE KAAM · {{ live.length }} LIVE</span></span>
        <h1 class="h-lg">Messages that send themselves at the right moment</h1>
        <p class="ts muted">Pick a ready recipe — a missed call, a sale, a birthday — and the AI writes every message in your voice. You can change any step.</p>
      </div>
      <button type="button" class="btn bs">Browse 12 recipes</button>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
      <aside class="panel rise d1" style="flex: 1 1 260px; max-width: 300px; min-width: 0; overflow: hidden">
        <div class="ph"><span class="eyb"><b>01</b>·<span>Your workflows</span></span></div>
        <div v-for="(x, i) in R" :key="x.n" :class="'rc' + (sel === i ? ' on' : '')">
          <button type="button" style="border: 0; background: none; padding: 0; text-align: left; cursor: pointer; min-width: 0" @click="pickRecipe(i)">
            <span class="ts" style="font-weight: 600; display: block">{{ x.n }}</span>
            <span class="tx muted">{{ x.stat }}</span>
          </button>
          <button type="button" :class="'sw' + (live.includes(i) ? ' on' : '')" :aria-label="'Turn ' + x.n + ' on or off'" @click="toggle(i)"></button>
        </div>
      </aside>
      <section class="panel dots rise d2" style="flex: 2 1 520px; min-width: 0; background-color: #F8FAFC; overflow: hidden">
        <div class="ph" style="background: #fff"><span class="eyb"><b>02</b>·<span>{{ r.n }}</span></span><span :class="on ? 'st ok live' : 'st'">{{ on ? 'Live' : 'Off' }}</span><span class="sp"></span><button type="button" class="btn bs sm">Test on my number</button></div>
        <div style="padding: 24px 18px 28px; display: flex; flex-direction: column; align-items: center">
          <div v-for="(n, i) in nodes" :key="sel + '-' + i" style="display: flex; flex-direction: column; align-items: center; width: 100%">
            <div v-if="n.link" class="lk"></div>
            <button type="button" :class="'nd' + (ni === i ? ' on' : '')" :style="{ animationDelay: n.dl }" @click="node = i">
              <span class="kind" :style="{ background: n.kb, color: n.kc }">{{ n.kind }}</span>
              <span style="min-width: 0"><span class="ts" style="font-weight: 600; display: block">{{ n.t }}</span><span class="tx muted" :lang="n.lc">{{ n.d }}</span></span>
              <span class="mono muted" style="font-size: 11px; text-align: right">{{ n.stat }}</span>
            </button>
            <span v-if="n.branch" class="tx muted" style="margin-top: 6px">yes ↓ · no → end</span>
          </div>
          <div class="lk"></div><button type="button" class="add" aria-label="Add a step">+</button>
        </div>
      </section>
      <aside class="panel rise d3" style="flex: 1.15 1 300px; min-width: 0">
        <div class="ph"><span class="eyb"><b>03</b>·<span>{{ nd[0] }} step</span></span></div>
        <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
          <p class="h-md">{{ nd[1] }}</p>
          <div v-if="isSend" style="display: flex; flex-direction: column; gap: 12px">
            <div class="seg"><button v-for="(l, i) in LANGS" :key="l" type="button" :class="lang === i ? 'on' : ''" @click="lang = i">{{ l }}</button></div>
            <textarea class="in" style="height: 110px; padding: 10px 12px; resize: none; line-height: 1.5" :lang="LCS[lang]" aria-label="Message" :value="msg"></textarea>
            <div class="chips"><button v-for="w in REWRITES" :key="w[0]" type="button" class="chip" @click="show(w[1])">{{ w[0] }}</button></div>
            <div class="receipt" style="border-top: 1px dashed #CBD5E1; padding-top: 4px">
              <div class="ln"><span class="muted">channel</span><span>WhatsApp · SMS if not on WhatsApp</span></div>
              <div class="ln"><span class="muted">timing</span><span>9 am – 9 pm only</span></div>
              <div class="ln"><span class="muted">cost</span><span>₹0.86 per WhatsApp</span></div>
            </div>
          </div>
          <p v-if="!isSend" class="ts muted">{{ HELP[nd[0]] || '' }}</p>
          <div class="strip" style="grid-template-columns: repeat(3, minmax(0, 1fr))">
            <div v-for="k in nstats" :key="k.l" style="padding: 10px 12px; gap: 4px"><span class="lm">{{ k.l }}</span><span class="num" style="font-size: 20px">{{ k.v }}</span></div>
          </div>
        </div>
      </aside>
    </div>
    <div v-if="toast" class="toast" role="status">{{ toast }}</div>
  </div>
</template>

<style scoped>
.rc{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:center;width:100%;padding:12px 14px;border:0;border-bottom:1px solid #E2E8F0;background:none;text-align:left;cursor:pointer;transition:background .15s}.rc:hover{background:#F8FAFC}.rc.on{background:#F1F5F9}.nd{position:relative;display:grid;grid-template-columns:72px minmax(0,1fr) auto;gap:12px;align-items:center;width:min(560px,100%);padding:12px 14px;border:1px solid #E2E8F0;border-radius:12px;background:#fff;text-align:left;cursor:pointer;transition:border-color .15s,box-shadow .15s,transform .2s var(--ease);animation:rise .5s var(--ease) both}.nd:hover{transform:translateY(-1px);border-color:#94A3B8}.nd.on{border-color:#0F172A;box-shadow:inset 0 0 0 1px #0F172A}.lk{width:1.5px;height:26px;background:#CBD5E1;position:relative}.lk::after{content:"";position:absolute;left:-3px;bottom:-1px;width:7.5px;height:7.5px;border-right:1.5px solid #CBD5E1;border-bottom:1.5px solid #CBD5E1;transform:rotate(45deg)}.kind{font:500 10.5px/1 var(--fm);letter-spacing:.06em;padding:5px 6px;border-radius:5px;text-align:center}.add{width:22px;height:22px;border-radius:50%;border:1px dashed #94A3B8;background:#fff;color:#64748B;display:grid;place-items:center;font-size:13px;line-height:1;cursor:pointer;margin:2px 0}
</style>
