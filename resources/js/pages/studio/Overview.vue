<script setup>
import { ref, computed } from 'vue'
import { useApp } from '../../stores/app'

const app = useApp()

const auto = ref(true)

const slug = (s) => String(s || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
const shopUrl = computed(() => `dikho.in/${slug(app.shop.city)}/${slug(app.shop.name)}`)

const ic = {
  post: 'M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zM4 16l5-5 4 4 2-2 5 5',
  status: 'M8 3h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zM10 17h4',
  car: 'M7 5h10v14H7zM3 7v10M21 7v10', reel: 'M6 5h12a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3zM10 9.5v5l4.5-2.5z',
  voice: 'M12 3a3 3 0 0 1 3 3v5a3 3 0 0 1-6 0V6a3 3 0 0 1 3-3zM5 11a7 7 0 0 0 14 0M12 18v3', music: 'M9 18V5l11-2v13M6.5 20.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zM17.5 18.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
  wa: 'M4 20l1.3-3.9A8 8 0 1 1 8 19z', sms: 'M4 5h16v11H8l-4 4zM8 9h8M8 12h5', gb: 'M4 9.5V20h16V9.5M3 4h18l-1.5 5.5a2.6 2.6 0 0 1-5 0 2.6 2.6 0 0 1-5 0 2.6 2.6 0 0 1-5 0z',
  ad: 'M3 10v4a1 1 0 0 0 1 1h3l6 4V5L7 9H4a1 1 0 0 0-1 1zM17 8.5a5 5 0 0 1 0 7', print: 'M7 9V3h10v6M7 17H4a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-3M7 14h10v7H7z',
  page: 'M4 4h16v16H4zM4 9h16M8 13h8M8 16h5', star: 'M12 3.5l2.6 5.4 5.9.8-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8L3.5 9.7l5.9-.8z', scene: 'M3 4h18v16H3zM3 17l5-5 4 4 3-3 6 6'
}
const F = [
  ['Post', 'Feed, 4:5', '2 cr', ic.post, 'studio-editor'], ['Status', 'WhatsApp, 9:16', '2 cr', ic.status, 'studio-editor'], ['AI scene', 'Real product, new scene', '6 cr', ic.scene, 'studio-editor'],
  ['Carousel', '5 cards', '5 cr', ic.car, 'studio-editor'], ['Reel 15 s', 'Hook to CTA', '15 cr', ic.reel, 'studio-editor'], ['Reel 30 s', 'With voiceover', '25 cr', ic.voice, 'studio-editor'],
  ['Jingle', 'Yours forever', '40 cr', ic.music, 'studio-editor'], ['WhatsApp message', 'Template + buttons', '3 cr', ic.wa, 'studio-messages'], ['SMS', 'DLT-ready, 160 chars', 'fee/SMS', ic.sms, 'studio-messages'],
  ['Google post', 'Offer on your listing', '2 cr', ic.gb, 'studio-editor'], ['Meta ad', 'Copy + creative', '20 cr', ic.ad, 'studio-editor'], ['Print', 'Flex, pamphlet', '8 cr', ic.print, 'studio-editor'],
  ['Shop page update', 'dikho.in, SEO', 'free', ic.page, 'studio-campaign'], ['Review reply', 'Polite, in Hindi', 'free', ic.star, 'studio-messages']
]
const B = [
  ['SP', 'Shop profile', 'Mobile & electronics · College Road · since 2014', 'setup', 'Complete', 'st ok'],
  ['PR', 'Products & prices', '4 items · 1 live offer: ₹2,000 on exchange', 'setup', 'Complete', 'st ok'],
  ['BK', 'Brand kit', 'Signboard colours · tagline · sonic logo', 'settings', 'Locked', 'st ok'],
  ['CU', 'Customers', '212 people · 148 said yes to offers', 'customers', 'Synced', 'st ok'],
  ['LC', 'Local calendar', 'Nashik · 6 occasions in the next 60 days', 'occasions', 'Updated', 'st ok'],
  ['RS', 'What worked', '6 weeks · offer hooks beat greetings 3 : 1', 'results', 'Learning', 'st ok live'],
  ['MO', 'Moments', 'Salary week (1–7), match days, first rain', 'occasions', 'On', 'st ok'],
  ['PH', 'Photos', '3 of 5 — add 2 product photos for reels', 'setup', 'Needs you', 'st warn']
]

const planLines = [76, 58, 88, 64, 48].map((w, i) => ({ w: w + '%', d: (0.4 + i * 0.12) + 's' }))
const ticks = Array.from({ length: 50 }, (_, i) => ({ c: i < 41 ? 'on' : '', d: (0.2 + i * 0.01).toFixed(2) + 's' }))
const brain = B.map(([m, n, d, to, s, sc]) => ({ m, n, d, to, s, sc, mt: sc === 'st warn' ? 'mt sm' : 'mt sm lt' }))
const funnel = [['Pehchaan', 'Awareness', 30, '#0F172A'], ['Bharosa', 'Trust', 25, '#475569'], ['Kharidari', 'Buying', 30, '#F2A007'], ['Wapsi', 'Return', 15, '#94A3B8']].map(([n, en, v, c], i) => ({ n, en, v: v + '%', w: v + '%', c, d: (0.3 + i * 0.1) + 's' }))
const pillars = [['Bachat', 'offers', 30], ['Tyohaar', 'festivals', 20], ['Bharosa', 'proof', 20], ['Jaankari', 'tips', 15], ['Mohalla', 'local', 10], ['Dukaan ki kahani', 'behind the counter', 5]].map(([n, en, v], i) => ({ n, en, v: v + '%', w: (v / 30 * 100) + '%', d: (0.3 + i * 0.07) + 's' }))
const formats = F.map(([n, sub, cr, d, to]) => ({ n, s: sub, cr, d, to, tc: cr === 'free' ? 'tag dk' : (cr.endsWith('cr') ? 'tag cr' : 'tag') }))
const running = [
  { k: 'Workflows', st: '5 live', t: 'Messages that send themselves', a: 'sent this month', av: '312', b: 'replies', bv: '41', to: 'studio-workflows' },
  { k: 'Campaigns', st: '2 live', t: 'Shaadi Exchange Mela', a: 'enquiries so far', av: '41', b: 'store visits (QR)', bv: '23', to: 'studio-campaign' },
  { k: 'Messages', st: '3 scheduled', t: 'WhatsApp & SMS this week', a: 'to customers who said yes', av: '148', b: 'wallet', bv: '≈ ₹164', to: 'studio-messages' }
]
const flow = [
  { k: '01 · KNOWS', t: 'Business brain', d: 'shop, products, customers', bc: '#E2E8F0' },
  { k: '02 · MAKES', t: 'Auto or Plan', d: '14 formats, 4 languages', bc: '#E2E8F0' },
  { k: '03 · RUNS', t: 'Campaigns & workflows', d: 'scheduled, triggered', bc: '#E2E8F0' },
  { k: '04 · SENDS', t: 'Every channel', d: 'Status, Insta, FB, Google, SMS, print', bc: '#E2E8F0' },
  { k: '05 · ROUTES', t: 'Dikho shop page', d: 'every link and QR', bc: '#F2A007' },
  { k: '06 · COUNTS', t: 'Results', d: 'clicks, enquiries, visits', bc: '#0F172A' }
]
</script>

<template>
  <div class="wrap">
    <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px">
      <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 420px"><span class="eyb"><b>CONTENT STUDIO</b>·<span>कंटेंट स्टूडियो</span></span><h1 class="h-lg">Your shop’s content, made every day</h1><p class="ts muted">AI writes and designs posts, reels, messages and ads from what it knows about {{ app.shop.name }} — in Hindi, Marathi or Hinglish. You decide how much it does on its own.</p></div>
      <RouterLink class="btn bs" :to="{ name: 'create' }">Quick create</RouterLink><RouterLink class="btn bp" :to="{ name: 'studio-auto' }">Open today’s content<svg class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></RouterLink>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(460px, 100%), 1fr)); gap: 20px">
      <div class="mode rise d1">
        <div style="display: flex; align-items: center; gap: 10px"><span class="tag dk">MODE A</span><span class="h-md">Auto mode · अपने आप</span><span class="sp"></span><button type="button" :class="'sw' + (auto ? ' on' : '')" aria-label="Auto mode on or off" @click="auto = !auto"></button></div>
        <div style="display: grid; grid-template-columns: minmax(0, 1fr) 150px; gap: 20px; align-items: center">
          <div style="display: flex; flex-direction: column; gap: 12px"><p class="ts dim">Content is made automatically from your shop information, local calendar and what worked last month. You approve, edit or swap anything.</p>
            <div class="receipt"><div class="ln" style="border-bottom: 1px dashed #E2E8F0"><span class="muted">next 14 days</span><span>38 pieces</span></div><div class="ln" style="border-bottom: 1px dashed #E2E8F0"><span class="muted">ready to approve</span><span>32</span></div><div class="ln"><span class="muted">need you</span><span>6</span></div></div>
            <RouterLink class="btn bp" :to="{ name: 'studio-auto' }" style="align-self: flex-start">Open auto board<svg class="i s arr" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></RouterLink></div>
          <div style="position: relative; height: 190px" aria-hidden="true">
            <div class="pst r45 y feed" style="position: absolute; width: 112px; left: 0; top: 0; font-size: 5.4px"><div class="pad" style="flex: 1; justify-content: flex-end"><span class="hk">Purana phone laao, ₹2,000 extra pao</span></div><div class="ft"><b>{{ app.shop.short }}</b></div></div>
            <div class="panel feed" style="position: absolute; width: 132px; right: 0; top: 40px; padding: 8px 10px; animation-delay: 1.5s"><span class="mono" style="font-size: 9.5px; color: #64748B">WHATSAPP · 10 AM</span><p style="font-size: 11px; line-height: 15px; margin-top: 4px">Namaste Priya ji, shaadi season offer…</p></div>
            <div class="pst r45 feed" style="position: absolute; width: 96px; left: 30px; top: 92px; font-size: 4.6px; animation-delay: 3s"><div class="pi">reel</div><div class="pad"><span class="hk">30 minute repair</span></div><div class="ft"><b>{{ app.shop.short }}</b></div></div>
          </div>
        </div>
      </div>
      <div class="mode rise d2">
        <div style="display: flex; align-items: center; gap: 10px"><span class="tag">MODE B</span><span class="h-md">Plan mode · अपना प्लान</span></div>
        <div style="display: grid; grid-template-columns: minmax(0, 1fr) 150px; gap: 20px; align-items: center">
          <div style="display: flex; flex-direction: column; gap: 12px"><p class="ts dim">Tell us how you market today and what you want next — goal, budget, audience, tone. AI writes the strategy, calendar and every piece; you change anything.</p>
            <div class="receipt"><div class="ln" style="border-bottom: 1px dashed #E2E8F0"><span class="muted">last plan</span><span>Shaadi season push</span></div><div class="ln" style="border-bottom: 1px dashed #E2E8F0"><span class="muted">goal</span><span>60 enquiries in Dec</span></div><div class="ln"><span class="muted">status</span><span>v2 · approved 20 Nov</span></div></div>
            <div style="display: flex; gap: 8px"><RouterLink class="btn bp" :to="{ name: 'studio-plan' }">Start a plan</RouterLink><RouterLink class="btn bs" :to="{ name: 'studio-plan-result' }">Open last plan</RouterLink></div></div>
          <div class="panel" style="padding: 12px; display: flex; flex-direction: column; gap: 7px" aria-hidden="true">
            <span class="mono" style="font-size: 9.5px; color: #64748B">PLAN · DEC</span>
            <span v-for="(l, i) in planLines" :key="i" style="display: flex; gap: 6px; align-items: center"><span class="ck on" style="width: 12px; height: 12px; border-radius: 3px"><svg class="i" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg></span><span class="gx" :style="`height: 6px; border-radius: 3px; background: #CBD5E1; width: ${l.w}; animation-delay: ${l.d}`"></span></span>
          </div>
        </div>
      </div>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
      <section class="panel rise d3" style="flex: 1.25 1 480px; min-width: 0">
        <div class="ph"><span class="eyb"><b>01</b>·<span>Business brain — what the AI knows</span></span><span class="sp"></span><span class="mono">82%</span></div>
        <div style="padding: 14px 18px 4px"><div class="ticks sm"><i v-for="(k, i) in ticks" :key="i" :class="k.c" :style="`animation-delay: ${k.d}`"></i></div></div>
        <RouterLink v-for="b in brain" :key="b.m" class="row" :to="{ name: b.to }" style="padding: 10px 18px"><span :class="b.mt">{{ b.m }}</span><span class="grow"><span class="ts" style="font-weight: 500; display: block">{{ b.n }}</span><span class="tx muted">{{ b.d }}</span></span><span :class="b.sc">{{ b.s }}</span></RouterLink>
      </section>
      <section class="panel rise d4" style="flex: 1 1 380px; min-width: 0">
        <div class="ph"><span class="eyb"><b>02</b>·<span>This month’s mix</span></span><span class="sp"></span><span class="tag">60 value · 40 offer</span></div>
        <div class="pb" style="display: flex; flex-direction: column; gap: 18px">
          <div style="display: flex; flex-direction: column; gap: 8px"><span class="lm">Funnel · grahak ka safar</span>
            <div style="display: flex; height: 14px; gap: 2px; border-radius: 4px; overflow: hidden"><span v-for="f in funnel" :key="f.n" class="gx" :style="`width: ${f.w}; background: ${f.c}; animation-delay: ${f.d}`"></span></div>
            <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px"><span v-for="f in funnel" :key="f.n" style="display: flex; flex-direction: column"><span class="ts" style="font-weight: 600">{{ f.n }}</span><span class="tx muted">{{ f.en }} · {{ f.v }}</span></span></div></div>
          <div style="display: flex; flex-direction: column; gap: 9px"><span class="lm">Content pillars</span>
            <div v-for="p in pillars" :key="p.n" style="display: grid; grid-template-columns: 150px minmax(0, 1fr) 34px; gap: 10px; align-items: center"><span class="ts"><b style="font-weight: 600">{{ p.n }}</b> <span class="muted">{{ p.en }}</span></span><span class="bar-t"><i :style="`width: ${p.w}; animation-delay: ${p.d}`"></i></span><span class="mono" style="text-align: right">{{ p.v }}</span></div></div>
        </div>
      </section>
    </div>

    <section class="rise d5" style="display: flex; flex-direction: column; gap: 14px">
      <div style="display: flex; align-items: center; gap: 10px"><span class="eyb"><b>03</b>·<span>Make something now · 14 formats</span></span><span class="sp"></span><span class="tx muted">Every format comes in your language, with your colours, link and QR.</span></div>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(150px, 100%), 1fr)); gap: 10px">
        <RouterLink v-for="f in formats" :key="f.n" class="fmt" :to="{ name: f.to }"><span style="display: flex; justify-content: space-between; align-items: flex-start"><span class="mt sm lt"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path :d="f.d"></path></svg></span><span :class="f.tc">{{ f.cr }}</span></span><span style="display: flex; flex-direction: column"><span class="ts" style="font-weight: 600">{{ f.n }}</span><span class="tx muted">{{ f.s }}</span></span></RouterLink>
      </div>
    </section>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr)); gap: 20px">
      <RouterLink v-for="r in running" :key="r.k" class="panel hov rise d6" :to="{ name: r.to }" style="padding: 18px; display: flex; flex-direction: column; gap: 10px; text-decoration: none; color: #0F172A"><span style="display: flex; align-items: center; gap: 8px"><span class="lm">{{ r.k }}</span><span class="sp"></span><span class="st ok live">{{ r.st }}</span></span><span class="h-md">{{ r.t }}</span><span class="receipt"><span class="ln" style="display: flex"><span class="muted">{{ r.a }}</span><span>{{ r.av }}</span></span><span class="ln" style="display: flex"><span class="muted">{{ r.b }}</span><span>{{ r.bv }}</span></span></span></RouterLink>
    </div>

    <section class="panel rise d7">
      <div class="ph"><span class="eyb"><b>04</b>·<span>How every piece travels — and why every result is counted</span></span></div>
      <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
        <div style="position: relative; display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 14px; align-items: stretch">
          <div style="position: absolute; left: 4%; right: 4%; top: 50%; height: 1.5px; background: #CBD5E1"><span class="runner"></span><span class="runner" style="animation-delay: 3s"></span></div>
          <div v-for="n in flow" :key="n.k" class="node" :style="`border-color: ${n.bc}`"><span class="mono" style="font-size: 10.5px; color: #64748B">{{ n.k }}</span><span class="ts" style="font-weight: 600">{{ n.t }}</span><span class="tx muted">{{ n.d }}</span></div>
        </div>
        <div class="note"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"></path></svg><span>Every link and QR in your content opens <span class="mono" style="color: #0F172A">{{ shopUrl }}</span> — your free, Google-ready shop page. That is how a scan in Panchavati or a click from Instagram becomes a number you can trust.</span></div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.mode{display:flex;flex-direction:column;gap:16px;padding:22px;border:1px solid #E2E8F0;border-radius:14px;background:#fff;min-width:0;transition:border-color .2s,transform .25s var(--ease)}.mode:hover{border-color:#CBD5E1;transform:translateY(-2px)}.fmt{display:flex;flex-direction:column;gap:10px;padding:14px;border:1px solid #E2E8F0;border-radius:12px;background:#fff;text-decoration:none;color:#0F172A;min-height:112px;transition:border-color .2s,transform .25s var(--ease)}.fmt:hover{border-color:#0F172A;transform:translateY(-2px)}.node{position:relative;z-index:1;display:flex;flex-direction:column;gap:4px;padding:10px 12px;border:1px solid #E2E8F0;border-radius:10px;background:#fff;min-width:0}@keyframes travel{0%{left:0;opacity:0}8%{opacity:1}92%{opacity:1}100%{left:100%;opacity:0}}.runner{position:absolute;top:-4px;width:9px;height:9px;border-radius:2px;background:#F2A007;transform:rotate(45deg);animation:travel 6s linear infinite}@keyframes slideup{0%{transform:translateY(16px);opacity:0}15%,70%{transform:none;opacity:1}85%,100%{transform:translateY(-10px);opacity:0}}.feed{animation:slideup 4.5s var(--ease) infinite}
</style>
