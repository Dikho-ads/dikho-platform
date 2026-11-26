<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import { useApp } from '../../stores/app'

const app = useApp()

const LIST = [
  { n: 'Shaadi offer', ch: 'WHATSAPP', when: 'SUN 29 · 10 AM', meta: '148 people · ₹127.74', s: 'Scheduled', sc: 'st ok' },
  { n: 'Exchange Mela', ch: 'SMS', when: 'WED 2 · 11 AM', meta: '148 people · DLT approved', s: 'Scheduled', sc: 'st ok' },
  { n: 'Birthday wish', ch: 'WHATSAPP', when: 'EVERY DAY · 9 AM', meta: 'Workflow · 86 birthdays on file', s: 'Live', sc: 'st ok live' },
  { n: 'Reply to Amol’s review', ch: 'GOOGLE', when: 'DRAFT', meta: '★★★★★ “1 ghante mein screen badal di”', s: 'Draft', sc: 'st' },
  { n: 'Diwali thank-you', ch: 'WHATSAPP', when: 'SENT 14 NOV', meta: '141 delivered · 22 replies', s: 'Sent', sc: 'st ink' },
]
const CHANS = ['WhatsApp', 'SMS', 'Both']
const LANGS = ['Hinglish', 'हिन्दी', 'मराठी', 'English']
const LC = ['en', 'hi', 'mr', 'en']
const TOKENS = ['{naam}', '{offer}', '{link}', '{date}']
const PEOPLE = ['Priya', 'Amol', 'Meena']
const SEG = [['All who said yes', 148], ['Regulars', 104], ['At risk', 27], ['Lapsed', 14]]
const WHENS = ['Now', 'Sun 10 am · best', 'Pick']

const V = computed(() => {
  const nm = app.shop.name
  return [
    [['Apnapan', 'Namaste {naam} ji! Shaadi season mein ' + app.shop.short + ' ki taraf se: purana phone laaiye, ₹2,000 extra paaiye. Offer Sunday 29 Nov tak.'], ['Seedhi baat', '{naam} ji, purane phone pe ₹2,000 extra. Sirf Sunday 29 Nov tak. ' + nm + ', College Road.'], ['Mazedaar', '{naam} ji, drawer mein pada purana phone ab ₹2,000 zyada ka hai! Sunday tak hi chalega.']],
    [['अपनापन', 'नमस्ते {naam} जी! शादी के सीज़न में पुराना फ़ोन लाइए, ₹2,000 एक्स्ट्रा पाइए। ऑफ़र रविवार 29 नवं तक।'], ['सीधी बात', '{naam} जी, पुराने फ़ोन पर ₹2,000 एक्स्ट्रा। सिर्फ़ रविवार तक। साई मोबाइल, कॉलेज रोड।'], ['मज़ेदार', '{naam} जी, दराज़ में पड़ा पुराना फ़ोन अब ₹2,000 ज़्यादा का! रविवार तक ही।']],
    [['आपुलकी', 'नमस्कार {naam} जी! लग्नसराईत जुना फोन आणा, ₹2,000 जास्त मिळवा. ऑफर रविवार 29 नोव्हें पर्यंत.'], ['थेट', '{naam} जी, जुन्या फोनवर ₹2,000 जास्त. फक्त रविवारपर्यंत. साई मोबाईल, कॉलेज रोड.'], ['मजेशीर', '{naam} जी, ड्रॉवरमधला जुना फोन आता ₹2,000 जास्त किमतीचा! फक्त रविवारपर्यंत.']],
    [['Warm', 'Hello {naam}! This wedding season, bring your old phone and get ₹2,000 extra. Offer till Sunday, 29 Nov.'], ['Direct', '{naam}, ₹2,000 extra on your old phone. Till Sunday only. ' + nm + ', College Road.'], ['Playful', '{naam}, that old phone in your drawer is now worth ₹2,000 more! Till Sunday.']],
  ]
})

const sel = ref(0)
const ch = ref(0)
const lang = ref(0)
const v = ref(0)
const customText = ref(null)
const who = ref(0)
const seg = ref(0)
const when = ref(1)
const toast = ref('')
const gen = ref(0)
let tt = null

function show(m) {
  toast.value = m
  clearTimeout(tt)
  tt = setTimeout(() => { toast.value = '' }, 3000)
}
onBeforeUnmount(() => clearTimeout(tt))

const vs = computed(() => V.value[lang.value])
const text = computed(() => customText.value !== null ? customText.value : vs.value[v.value][1])
const isSms = computed(() => ch.value === 1)
const isWa = computed(() => ch.value !== 1)
const showSmsInfo = computed(() => ch.value !== 0)
const dev = computed(() => /[ऀ-ॿ]/.test(text.value))
const per = computed(() => dev.value ? 70 : 160)
const lc = computed(() => LC[lang.value])
const fill = (t) => t.replace(/\{naam\}/g, PEOPLE[who.value]).replace(/\{offer\}/g, '₹2,000 extra').replace(/\{link\}/g, 'dkho.in/sai/2k').replace(/\{date\}/g, '29 Nov')
const filled = computed(() => fill(text.value))
const filledSms = computed(() => 'SAIMOB: ' + filled.value.slice(0, 120) + ' dkho.in/sai/2k — reply STOP to stop')
const counter = computed(() => isSms.value
  ? filled.value.length + ' / ' + per.value + ' · ' + Math.ceil(filled.value.length / per.value) + ' SMS'
  : filled.value.length + ' chars · fits one message')
const n = computed(() => SEG[seg.value][1])
const capped = computed(() => seg.value === 0 ? 6 : 0)
const sendN = computed(() => n.value - capped.value)
const waCost = computed(() => Math.round(sendN.value * 0.8631 * 100) / 100)
const costLabel = computed(() => isSms.value ? sendN.value + ' SMS · DLT plan rate' : sendN.value + ' × ₹0.86 WhatsApp')
const cost = computed(() => isSms.value ? 'per your SMS plan' : '₹' + waCost.value.toFixed(2))
const after = computed(() => isSms.value ? '₹' + app.wallet.toLocaleString('en-IN') : '₹' + (app.wallet - waCost.value).toLocaleString('en-IN', { maximumFractionDigits: 2 }))
const sendTxt = computed(() => (when.value === 0 ? 'Send now to ' : 'Schedule for ') + sendN.value + ' people')
const pvTime = computed(() => when.value === 0 ? 'NOW' : 'SUN 29 NOV · 10:00')

function pickMsg(i) {
  sel.value = i
  ch.value = LIST[i].ch === 'SMS' ? 1 : 0
}
function write() {
  gen.value += 1
  customText.value = null
  v.value = (v.value + 1) % 3
}
function setLang(i) { lang.value = i; customText.value = null }
function setVariant(i) { v.value = i; customText.value = null }
function addToken(t) { customText.value = text.value + ' ' + t }
function send() {
  show((when.value === 0 ? 'Sending to ' : 'Scheduled for ') + sendN.value + ' people · STOP replies handled automatically')
}
</script>

<template>
  <div class="wrap" style="max-width: 1400px">
    <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
      <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 420px">
        <span class="eyb"><b>MESSAGES</b>·<span>WHATSAPP &amp; SMS · सीधा ग्राहक तक</span></span>
        <h1 class="h-lg">Messages that reach the customer’s phone</h1>
        <p class="ts muted">AI writes them in your voice; they go only to people who said yes, with STOP in every one.</p>
      </div>
      <button type="button" class="btn bs"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>New message</button>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
      <aside class="panel rise d1" style="flex: 1 1 250px; max-width: 290px; min-width: 0; overflow: hidden">
        <div class="ph"><span class="eyb"><b>01</b>·<span>This week</span></span></div>
        <button v-for="(m, i) in LIST" :key="m.n" type="button" :class="'ml' + (sel === i ? ' on' : '')" @click="pickMsg(i)">
          <span style="display: flex; justify-content: space-between; align-items: center; gap: 8px"><span class="ts" style="font-weight: 600">{{ m.n }}</span><span :class="m.sc" style="font-size: 12px">{{ m.s }}</span></span>
          <span class="mono muted" style="font-size: 11px">{{ m.ch }} · {{ m.when }}</span>
          <span class="tx muted">{{ m.meta }}</span>
        </button>
      </aside>
      <section style="flex: 2 1 460px; min-width: 0; display: flex; flex-direction: column; gap: 16px">
        <div class="panel rise d2">
          <div class="ph"><span class="eyb"><b>02</b>·<span>Write</span></span><span class="sp"></span>
            <div class="seg"><button v-for="(c, i) in CHANS" :key="c" type="button" :class="ch === i ? 'on' : ''" @click="ch = i">{{ c }}</button></div>
          </div>
          <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
            <div style="display: flex; gap: 8px">
              <div class="iw" style="flex: 1"><input class="in" value="Shaadi offer batao — purane phone pe ₹2,000 extra, Sunday tak" aria-label="What should the message say"><button type="button" class="btn ba ib sm" aria-label="Speak" style="right: 5px"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a3 3 0 0 1 3 3v5a3 3 0 0 1-6 0V6a3 3 0 0 1 3-3zM5 11a7 7 0 0 0 14 0M12 18v3"></path></svg></button></div>
              <button type="button" class="btn bp" @click="write">{{ gen ? 'Write again' : 'Write 3 versions' }}</button>
            </div>
            <div class="seg" style="align-self: flex-start"><button v-for="(l, i) in LANGS" :key="l" type="button" :class="lang === i ? 'on' : ''" @click="setLang(i)">{{ l }}</button></div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(190px, 100%), 1fr)); gap: 10px">
              <button v-for="(x, i) in vs" :key="lang + '-' + i" type="button" :class="'vr' + (v === i && customText === null ? ' on' : '')" :style="{ animationDelay: (i * 0.06) + 's' }" @click="setVariant(i)">
                <span style="display: flex; justify-content: space-between"><span class="mono" style="font-size: 11px">{{ x[0].toUpperCase() }}</span><span class="mono muted" style="font-size: 11px">{{ x[1].length }} chars</span></span>
                <span class="ts" :lang="lc">{{ x[1] }}</span>
              </button>
            </div>
            <div class="fld">
              <label class="lab" for="mt">Message</label>
              <textarea id="mt" class="in" style="height: 96px; padding: 10px 12px; resize: none; line-height: 1.5" :lang="lc" :value="text" @input="customText = $event.target.value"></textarea>
              <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center">
                <span class="tx muted">Insert</span>
                <button v-for="t in TOKENS" :key="t" type="button" class="chip" style="height: 26px; font-family: var(--fm); font-size: 11.5px" @click="addToken(t)">{{ t }}</button>
                <span class="sp"></span>
                <span class="mono muted" style="font-size: 11px">{{ counter }}</span>
              </div>
            </div>
            <div v-if="isWa" class="receipt" style="border-top: 1px dashed #CBD5E1; padding-top: 6px">
              <div class="ln"><span class="muted">template</span><span>shaadi_exchange_v1 · Marketing</span></div>
              <div class="ln"><span class="muted">status</span><span :class="customText !== null ? 'st warn' : 'st ok'">{{ customText !== null ? 'Edited — Meta re-approval needed (usually minutes)' : 'Approved by Meta' }}</span></div>
              <div class="ln"><span class="muted">buttons</span><span>Offer dekho · Call karo · Stop offers</span></div>
              <div class="ln"><span class="muted">image</span><span>Shaadi offer poster · 4:5</span></div>
            </div>
            <div v-if="showSmsInfo" class="receipt" style="border-top: 1px dashed #CBD5E1; padding-top: 6px">
              <div class="ln"><span class="muted">header</span><span>SAIMOB · DLT registered</span></div>
              <div class="ln"><span class="muted">template</span><span class="st ok">Matches approved DLT template</span></div>
              <div class="ln"><span class="muted">encoding</span><span>{{ dev ? 'Unicode · 70 chars per SMS' : 'Plain text · 160 chars per SMS' }}</span></div>
            </div>
          </div>
        </div>
      </section>
      <aside class="rise d3" style="flex: 1.2 1 300px; min-width: 0; display: flex; flex-direction: column; gap: 16px">
        <section class="panel pb" style="display: flex; flex-direction: column; gap: 12px">
          <div style="display: flex; align-items: center; gap: 8px"><span class="lm">Preview as</span><span class="sp"></span>
            <div class="seg"><button v-for="(p, i) in PEOPLE" :key="p" type="button" :class="who === i ? 'on' : ''" @click="who = i">{{ p }}</button></div>
          </div>
          <div class="phone">
            <div class="scr2">
              <div style="display: flex; gap: 8px; align-items: center; padding: 9px 12px; background: #fff; border-bottom: 1px solid #E2E8F0"><span class="mt sm">{{ isSms ? 'VM' : app.shop.initials }}</span><span class="ts" style="font-weight: 600">{{ isSms ? 'VM-SAIMOB' : app.shop.name }}</span></div>
              <div style="padding: 12px; display: flex; flex-direction: column; gap: 8px">
                <span class="mono" style="align-self: center; font-size: 10px; color: #64748B">{{ pvTime }}</span>
                <div v-if="!isSms" class="msg">
                  <div class="pst r45 y" style="width: 100%; font-size: 5px; border-radius: 0"><div class="pad" style="flex: 1; justify-content: flex-end"><span class="hk">Purana phone laao, ₹2,000 extra pao</span></div><div class="ft"><b>{{ app.shop.short }}</b></div></div>
                  <p style="padding: 8px 10px" :lang="lc">{{ filled }}</p>
                  <span class="mbtn">Offer dekho</span><span class="mbtn">Call karo</span><span class="mbtn" style="color: #64748B">Stop offers</span>
                </div>
                <div v-if="isSms" class="msg" style="background: #E2E8F0; border: 0; padding: 9px 11px" :lang="lc">{{ filledSms }}</div>
              </div>
            </div>
          </div>
        </section>
        <section class="panel">
          <div class="ph"><span class="eyb"><b>03</b>·<span>Who gets it</span></span><span class="sp"></span><span class="mono">{{ n }} people</span></div>
          <div class="pb" style="display: flex; flex-direction: column; gap: 12px">
            <div class="chips"><button v-for="(g, i) in SEG" :key="g[0]" type="button" :class="'chip' + (seg === i ? ' on' : '')" @click="seg = i">{{ g[0] }} <span class="n">{{ g[1] }}</span></button></div>
            <span class="tx muted">Left out automatically: 64 never asked · 9 said STOP · {{ capped }} already got 2 offers this week.</span>
            <div class="seg full"><button v-for="(w, i) in WHENS" :key="w" type="button" :class="when === i ? 'on' : ''" @click="when = i">{{ w }}</button></div>
            <div class="receipt" style="border-top: 1px dashed #CBD5E1; padding-top: 4px">
              <div class="ln"><span class="muted">{{ costLabel }}</span><span>{{ cost }}</span></div>
              <div class="ln"><span class="muted">wallet after</span><span>{{ after }}</span></div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 6px">
              <span class="st ok" style="font-size: 12.5px">Only people who said yes</span>
              <span class="st ok" style="font-size: 12.5px">STOP in every message</span>
              <span class="st ok" style="font-size: 12.5px">Nothing between 9 pm and 9 am</span>
              <span class="st ok" style="font-size: 12.5px">Max 2 offers a week per person</span>
            </div>
            <button type="button" class="btn bp lg full" @click="send">{{ sendTxt }}</button>
          </div>
        </section>
      </aside>
    </div>
    <div v-if="toast" class="toast" role="status">{{ toast }}</div>
  </div>
</template>

<style scoped>
.ml{display:flex;flex-direction:column;gap:4px;width:100%;padding:12px 14px;border:0;border-bottom:1px solid #E2E8F0;background:none;text-align:left;cursor:pointer;transition:background .15s}.ml:hover{background:#F8FAFC}.ml.on{background:#F1F5F9}.vr{display:flex;flex-direction:column;gap:8px;padding:12px;border:1px solid #E2E8F0;border-radius:12px;background:#fff;text-align:left;cursor:pointer;transition:border-color .15s,box-shadow .15s,transform .2s var(--ease);animation:rise .5s var(--ease) both}.vr:hover{transform:translateY(-2px)}.vr.on{border-color:#0F172A;box-shadow:inset 0 0 0 1px #0F172A}.phone{width:100%;max-width:300px;border:1px solid #CBD5E1;border-radius:26px;background:#fff;padding:9px;align-self:center}.scr2{border-radius:19px;background:#F1F5F9;overflow:hidden;min-height:300px;display:flex;flex-direction:column}.msg{align-self:flex-start;max-width:90%;background:#fff;border:1px solid #E2E8F0;border-radius:4px 14px 14px 14px;overflow:hidden;font-size:13px;line-height:19px;animation:fade .3s ease both}.mbtn{display:block;text-align:center;padding:7px;border-top:1px solid #E2E8F0;font-weight:500;font-size:12.5px;color:#1D4ED8}
</style>
