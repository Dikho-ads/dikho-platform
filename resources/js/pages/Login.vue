<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useApp } from '@/stores/app';
import Logo from '@/components/ui/Logo.vue';

const app = useApp();
const router = useRouter();
const route = useRoute();

const LANGS = [
    ['EN', 'en', {
        inTitle: 'Welcome back', inSub: "Sign in to manage your shop's marketing.",
        upTitle: 'Create your Dikho account', upSub: 'Free to start. Set up your shop in about 3 minutes.',
        google: 'Continue with Google', or: 'or',
        name: 'Your name', namePh: 'e.g. Ramesh', email: 'Email address', emailPh: 'you@example.com',
        phone: 'WhatsApp number', phoneHint: 'For post reminders and customer chats. Never shared.',
        agreeA: 'I agree to the', terms: 'Terms of Service', and: 'and', privacy: 'Privacy Policy',
        cont: 'Continue', sending: 'Sending code…', codeTitle: 'Check your email', codeSub: 'We sent a 6-digit code to',
        codeLabel: 'Verification code', verify: 'Verify and continue', verifying: 'Verifying…',
        resendIn: 'Resend code in', resend: 'Resend code', otherEmail: 'Use a different email', back: 'Back', home: 'Home',
        noAcc: "Don't have an account?", signUp: 'Sign up', haveAcc: 'Already have an account?', signIn: 'Sign in',
        help: 'Need help getting started?', assisted: 'Assisted setup',
        errName: 'Enter your name.', errEmail: 'Enter a valid email address.', errPhone: 'Enter a 10-digit mobile number.',
        errAgree: 'Please accept the terms to continue.', errCode: 'Enter all 6 digits of the code.',
    }],
    ['हिन्दी', 'hi', {
        inTitle: 'फिर से स्वागत है', inSub: 'अपनी दुकान की मार्केटिंग संभालने के लिए साइन इन करें।',
        upTitle: 'अपना Dikho अकाउंट बनाएं', upSub: 'शुरुआत मुफ़्त। करीब 3 मिनट में दुकान सेट करें।',
        google: 'Google से आगे बढ़ें', or: 'या',
        name: 'आपका नाम', namePh: 'जैसे रमेश', email: 'ईमेल पता', emailPh: 'you@example.com',
        phone: 'WhatsApp नंबर', phoneHint: 'पोस्ट रिमाइंडर और ग्राहक चैट के लिए। किसी से शेयर नहीं होता।',
        agreeA: 'मैं सहमत हूं', terms: 'सेवा की शर्तों', and: 'और', privacy: 'प्राइवेसी पॉलिसी',
        cont: 'आगे बढ़ें', sending: 'कोड भेज रहे हैं…', codeTitle: 'अपना ईमेल देखें', codeSub: '6 अंकों का कोड भेजा गया',
        codeLabel: 'वेरिफ़िकेशन कोड', verify: 'वेरिफ़ाई करें', verifying: 'वेरिफ़ाई हो रहा है…',
        resendIn: 'कोड दोबारा भेजें', resend: 'कोड दोबारा भेजें', otherEmail: 'दूसरा ईमेल इस्तेमाल करें', back: 'वापस', home: 'होम',
        noAcc: 'अकाउंट नहीं है?', signUp: 'साइन अप करें', haveAcc: 'पहले से अकाउंट है?', signIn: 'साइन इन करें',
        help: 'शुरू करने में मदद चाहिए?', assisted: 'सहायता से सेटअप',
        errName: 'अपना नाम लिखें।', errEmail: 'सही ईमेल पता लिखें।', errPhone: '10 अंकों का मोबाइल नंबर लिखें।',
        errAgree: 'आगे बढ़ने के लिए शर्तें स्वीकार करें।', errCode: 'कोड के सभी 6 अंक लिखें।',
    }],
    ['मराठी', 'mr', {
        inTitle: 'पुन्हा स्वागत आहे', inSub: 'तुमच्या दुकानाचे मार्केटिंग सांभाळण्यासाठी साइन इन करा.',
        upTitle: 'तुमचे Dikho खाते तयार करा', upSub: 'सुरुवात मोफत. सुमारे 3 मिनिटांत दुकान सेट करा.',
        google: 'Google ने पुढे जा', or: 'किंवा',
        name: 'तुमचे नाव', namePh: 'उदा. रमेश', email: 'ईमेल पत्ता', emailPh: 'you@example.com',
        phone: 'WhatsApp नंबर', phoneHint: 'पोस्ट रिमाइंडर आणि ग्राहकांच्या चॅटसाठी. कोणाशीही शेअर होत नाही.',
        agreeA: 'मी सहमत आहे', terms: 'सेवा अटी', and: 'आणि', privacy: 'गोपनीयता धोरण',
        cont: 'पुढे जा', sending: 'कोड पाठवत आहोत…', codeTitle: 'तुमचा ईमेल तपासा', codeSub: '6 अंकी कोड पाठवला आहे',
        codeLabel: 'पडताळणी कोड', verify: 'पडताळणी करा', verifying: 'पडताळणी सुरू आहे…',
        resendIn: 'कोड पुन्हा पाठवा', resend: 'कोड पुन्हा पाठवा', otherEmail: 'दुसरा ईमेल वापरा', back: 'मागे', home: 'होम',
        noAcc: 'खाते नाही?', signUp: 'साइन अप करा', haveAcc: 'आधीच खाते आहे?', signIn: 'साइन इन करा',
        help: 'सुरुवात करण्यासाठी मदत हवी?', assisted: 'मदतीने सेटअप',
        errName: 'तुमचे नाव लिहा.', errEmail: 'योग्य ईमेल पत्ता लिहा.', errPhone: '10 अंकी मोबाइल नंबर लिहा.',
        errAgree: 'पुढे जाण्यासाठी अटी स्वीकारा.', errCode: 'कोडचे सर्व 6 अंक लिहा.',
    }],
];
const SHOPS = [
    ['Sai Mobile Point', 'Electronics'],
    ['Joshi Sweets', 'Sweets & snacks'],
    ['Pragati Classes', 'Coaching'],
];
const slats = Array.from({ length: 12 }, (_, i) => ({ x: i * 50 + 'px', o: -i * 50 + 'px', dl: (i * 0.06).toFixed(2) + 's' }));

const lang = ref(0);
// Sign in and sign up share this screen; the code step lives in the URL (?step=code) so the
// browser back button returns to the form.
const isSignup = computed(() => route.name === 'signup');
const name = ref('');
const email = ref('');
const phone = ref('');
const agree = ref(false);
const code = ref('');
const secs = ref(30);
const tried = ref(false);
const codeTried = ref(false);
const busy = ref(false);
const k = ref(0);
const n = ref(0);

const t = computed(() => LANGS[lang.value][2]);
const lc = computed(() => LANGS[lang.value][1]);
const emailOk = computed(() => /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(email.value.trim()));
const nameOk = computed(() => name.value.trim().length >= 2);
const phoneOk = computed(() => /^[6-9]\d{9}$/.test(phone.value));
const step = computed(() => (route.query.step === 'code' && emailOk.value ? 'code' : 'form'));
const errs = computed(() => {
    if (!tried.value) return {};
    const e = {};
    if (isSignup.value && !nameOk.value) e.name = t.value.errName;
    if (!emailOk.value) e.email = t.value.errEmail;
    if (isSignup.value && !phoneOk.value) e.phone = t.value.errPhone;
    if (isSignup.value && !agree.value) e.agree = t.value.errAgree;
    return e;
});
const cells = computed(() => [0, 1, 2, 3, 4, 5].map((i) => ({ d: code.value[i] || '', cls: 'c' + (i === code.value.length ? ' on' : '') })));
const dots = computed(() => [0, 1, 2].map((j) => ({ w: j === k.value ? '28px' : '8px', on: j === k.value })));
const curMeta = computed(() => ({ shop: SHOPS[k.value][0], cat: SHOPS[k.value][1] }));

let t0 = 0;
let iv = null;
let bv = null;
onMounted(() => {
    t0 = performance.now();
    iv = setInterval(() => {
        const e = (performance.now() - t0) / 1000;
        const nk = e < 4.4 ? 0 : (Math.floor((e - 4.4) / 4.6) + 1) % 3;
        const nn = Math.floor(e / 1.8);
        if (nk !== k.value) k.value = nk;
        if (nn !== n.value) n.value = nn;
    }, 250);
    bv = setInterval(() => {
        if (step.value === 'code' && secs.value > 0) secs.value -= 1;
    }, 1000);
});
onBeforeUnmount(() => {
    clearInterval(iv);
    clearInterval(bv);
    window.removeEventListener('resize', onResize);
});

// The billboard is a fixed 720 x 900 scene: scale it to fit the panel width and the window height.
const vw = ref(window.innerWidth);
const vh = ref(window.innerHeight);
function onResize() {
    vw.value = window.innerWidth;
    vh.value = window.innerHeight;
}
window.addEventListener('resize', onResize);
// Panel width per breakpoint; the billboard scales to fit that width and the window height,
// and the street scene widens behind it so the panel is always filled edge to edge.
const panelW = computed(() => (vw.value < 900 ? vw.value : vw.value >= 1280 ? 720 : vw.value >= 1100 ? 576 : 475));
const artScale = computed(() =>
    vw.value < 900 ? Math.min(vw.value / 720, (vh.value * 0.5) / 900) : Math.min(panelW.value / 720, vh.value / 900),
);
const stageW = computed(() => Math.max(720, Math.ceil(panelW.value / artScale.value)));
const sceneBox = computed(() => `${-(stageW.value - 720) / 2} 0 ${stageW.value} 900`);
const artStyle = computed(() => (vw.value < 900 ? {} : { flex: '0 0 ' + panelW.value + 'px' }));

function onCode(e) {
    code.value = e.target.value.replace(/\D/g, '').slice(0, 6);
    e.target.value = code.value;
    if (code.value.length === 6) verify();
}
function onPhone(e) {
    phone.value = e.target.value.replace(/\D/g, '').slice(0, 10);
    e.target.value = phone.value;
}

const wait = (ms) => new Promise((r) => setTimeout(r, ms));

// POST /api/auth/otp (demo: short delay, no network)
async function submitForm() {
    tried.value = true;
    if (Object.keys(errs.value).length || busy.value) return;
    busy.value = true;
    await wait(600);
    busy.value = false;
    code.value = '';
    codeTried.value = false;
    secs.value = 30;
    await router.push({ query: { ...route.query, step: 'code' } });
    await nextTick();
    document.getElementById('cd')?.focus();
}

function goBack() {
    if (window.history.state?.back) router.back();
    else router.replace({ query: { ...route.query, step: undefined } });
}

function resend() {
    secs.value = 30;
    code.value = '';
    nextTick(() => document.getElementById('cd')?.focus());
}

// POST /api/auth/verify (demo: any 6 digits work)
async function verify() {
    codeTried.value = true;
    if (code.value.length !== 6 || busy.value) return;
    busy.value = true;
    await wait(500);
    busy.value = false;
    finish();
}

function google() {
    if (isSignup.value) app.signUp({ name: 'Ramesh', email: 'ramesh.saimobile@gmail.com' });
    else app.login('ramesh.saimobile@gmail.com');
    goNext();
}

function finish() {
    if (isSignup.value) app.signUp({ name: name.value, email: email.value.trim(), whatsapp: phone.value });
    else app.login(email.value.trim());
    goNext();
}

function goNext() {
    const next = route.query.next;
    if (typeof next === 'string' && next.startsWith('/')) router.push(next);
    else if (!app.setupDone) router.push({ name: 'setup' });
    else router.push({ name: 'home' });
}
</script>

<template>
<div class="app">
<div class="dark login-art" :style="artStyle" style="min-width: 0; position: relative; overflow: hidden; min-height: 560px; display: flex; align-items: center; justify-content: center; background: #0F172A">
<div class="login-stage" :style="{ zoom: artScale, width: stageW + 'px' }" style="position: relative; height: 900px; flex: none; overflow: hidden; background: #0F172A" role="img" aria-label="A street billboard turning between three local shop ads">
<svg aria-hidden="true" :width="stageW" height="900" :viewBox="sceneBox" style="position: absolute; left: 0; top: 0" fill="none">
<g id="dk-skyline">
<g fill="#334155"><circle cx="70" cy="34" r="1.2"></circle><circle cx="210" cy="22" r="1"></circle><circle cx="470" cy="30" r="1.2"></circle><circle cx="640" cy="44" r="1"></circle><circle cx="690" cy="150" r="1"></circle><circle cx="26" cy="170" r="1"></circle></g>
<path d="M0 770V620H34V596H62V612H96V580H128V604H160V630H196V610H232V640H488V616H520V588H556V606H590V572H626V598H664V584H700V610H720V770Z" fill="#131D30"></path>
<g fill="#F2A007" opacity=".35"><rect x="42" y="606" width="6" height="8" rx="1"></rect><rect x="104" y="592" width="6" height="8" rx="1"></rect><rect x="116" y="616" width="6" height="8" rx="1"></rect><rect x="566" y="616" width="6" height="8" rx="1"></rect><rect x="600" y="584" width="6" height="8" rx="1"></rect><rect x="636" y="610" width="6" height="8" rx="1"></rect><rect x="676" y="596" width="6" height="8" rx="1"></rect></g>
<rect x="0" y="652" width="150" height="118" fill="#0B1322"></rect>
<rect x="150" y="690" width="82" height="80" fill="#0D1628"></rect>
<rect x="488" y="694" width="64" height="76" fill="#0D1628"></rect>
<rect x="552" y="660" width="168" height="110" fill="#0B1322"></rect>
<g fill="#1E293B"><rect x="0" y="690" width="150" height="9"></rect><rect x="552" y="696" width="168" height="9"></rect></g>
<g fill="#F2A007"><rect x="14" y="708" width="56" height="40" rx="2" opacity=".18"></rect><rect x="84" y="708" width="40" height="62" rx="2" opacity=".1"></rect><rect x="572" y="714" width="64" height="40" rx="2" opacity=".18"></rect><rect x="650" y="714" width="48" height="56" rx="2" opacity=".1"></rect><rect x="166" y="712" width="48" height="30" rx="2" opacity=".12"></rect><rect x="500" y="714" width="40" height="30" rx="2" opacity=".12"></rect></g>
</g>
<use href="#dk-skyline" transform="scale(-1 1)"></use>
<use href="#dk-skyline" transform="translate(1440 0) scale(-1 1)"></use>
<rect x="-1440" y="770" width="3600" height="130" fill="#0A101C"></rect>
<path d="M-1440 770H2160" stroke="#1E293B"></path>
<path d="M-1440 792H2160" stroke="#172133" stroke-width="3" stroke-dasharray="26 22"></path>
<path d="M360 640L120 580M360 640L600 580" stroke="#1E293B" stroke-width="5"></path>
<rect x="344" y="566" width="32" height="204" fill="#1E293B"></rect>
<rect x="326" y="762" width="68" height="8" rx="2" fill="#273449"></rect>
<rect x="36" y="566" width="648" height="7" rx="2" fill="#273449"></rect>
<g stroke="#273449" stroke-width="2"><path d="M44 573V588M124 573V588M204 573V588M284 573V588M436 573V588M516 573V588M596 573V588M676 573V588"></path><path d="M44 588H676"></path></g>
<g stroke="#334155" stroke-width="3" stroke-linecap="round"><path d="M150 196V178"></path><path d="M360 196V178"></path><path d="M570 196V178"></path></g>
<g fill="#334155"><rect x="132" y="168" width="36" height="11" rx="4"></rect><rect x="342" y="168" width="36" height="11" rx="4"></rect><rect x="552" y="168" width="36" height="11" rx="4"></rect></g>
<path d="M137 179H163M347 179H373M557 179H583" stroke="#FDE68A" stroke-width="2" stroke-linecap="round" opacity=".9"></path>
</svg>
<div class="bb-center">
<div style="position: absolute; left: 46px; top: 196px; width: 628px; height: 368px; border-radius: 5px; background: #1E293B; box-shadow: inset 0 0 0 2px #273449"></div>
<div style="position: absolute; left: 60px; top: 210px; width: 600px; height: 340px; background: #0B1220; perspective: 1800px">
<div v-for="s in slats" :key="s.x" class="pr" :style="{ left: s.x, animationDelay: s.dl }">
<div class="fc f0" style="transform: rotateY(0deg) translateZ(14.43px)" :style="{ '--d': s.dl }"><div class="ad" style="background: #F2A007; color: #0F172A" :style="{ left: s.o }">
<svg aria-hidden="true" width="600" height="340" viewBox="0 0 600 340" style="position: absolute; inset: 0" fill="none">
<circle cx="500" cy="150" r="150" fill="#F6B532"></circle>
<g transform="rotate(-14 446 160)"><rect x="394" y="62" width="104" height="196" rx="18" fill="#FBE2A4" stroke="#0F172A" stroke-width="3"></rect><rect x="405" y="76" width="82" height="164" rx="9" fill="#F4CF7A"></rect><path d="M420 98L438 128L428 146L452 178" stroke="#0F172A" stroke-width="1.6" stroke-linecap="round"></path></g>
<rect x="446" y="40" width="118" height="226" rx="22" fill="#0F172A"></rect>
<rect x="454" y="50" width="102" height="206" rx="15" fill="#1E293B"></rect>
<rect x="491" y="57" width="28" height="7" rx="3.5" fill="#0F172A"></rect>
<circle cx="505" cy="130" r="34" fill="#F2A007"></circle>
<path d="M491 130L501 140L520 120" stroke="#0F172A" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>
<rect x="468" y="186" width="74" height="9" rx="4.5" fill="#F8FAFC"></rect>
<rect x="478" y="202" width="54" height="7" rx="3.5" fill="#64748B"></rect>
<rect x="474" y="224" width="62" height="18" rx="9" fill="#F2A007"></rect>
</svg>
<div style="position: absolute; left: 30px; top: 24px; display: flex; align-items: center; gap: 9px"><span style="width: 26px; height: 26px; border-radius: 7px; background: #0F172A; color: #F2A007; display: grid; place-items: center; font: 700 14px/1 var(--f)">S</span><span style="font: 700 12.5px/1 var(--f); letter-spacing: .1em">SAI MOBILE POINT</span></div>
<span style="position: absolute; left: 30px; top: 64px; background: #0F172A; color: #F8FAFC; font: 600 10.5px/1 var(--fm); letter-spacing: .06em; padding: 6px 10px; border-radius: 99px">DIWALI EXCHANGE FEST · TILL SUNDAY</span>
<div class="hd" style="position: absolute; left: 30px; top: 96px; font-size: 54px; line-height: .94">Old phone in.<br>New phone out.</div>
<div style="position: absolute; left: 30px; top: 210px; font: 600 16px/1.2 var(--f)">Up to ₹2,000 extra on your exchange</div>
<div style="position: absolute; left: 30px; top: 242px; display: flex; gap: 14px; font: 500 12.5px/1 var(--f)"><span class="chk-row"><svg aria-hidden="true" width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6.2" fill="#0F172A"></circle><path d="M4.2 7.2L6.1 9L9.8 5.2" stroke="#F2A007" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>No-cost EMI</span><span class="chk-row"><svg aria-hidden="true" width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6.2" fill="#0F172A"></circle><path d="M4.2 7.2L6.1 9L9.8 5.2" stroke="#F2A007" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>Instant valuation</span><span class="chk-row"><svg aria-hidden="true" width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6.2" fill="#0F172A"></circle><path d="M4.2 7.2L6.1 9L9.8 5.2" stroke="#F2A007" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>All brands</span></div>
<div style="position: absolute; left: 380px; top: 172px; width: 100px; height: 100px; border-radius: 50%; background: #F8FAFC; border: 3px solid #0F172A; box-sizing: border-box; transform: rotate(-10deg); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px"><span style="font: 600 9.5px/1 var(--fm); letter-spacing: .08em">UP TO</span><span class="hd" style="font-size: 27px; line-height: 1">₹2,000</span><span style="font: 600 9.5px/1 var(--fm); letter-spacing: .08em">EXTRA</span></div>
<div style="position: absolute; left: 0; right: 0; bottom: 0; height: 50px; background: #0F172A; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-sizing: border-box"><span style="font: 500 12px/1 var(--f); color: #CBD5E1">College Road, Nashik · Open 10 AM – 9 PM</span><span style="background: #F2A007; color: #0F172A; font: 600 12px/1 var(--f); padding: 8px 12px; border-radius: 99px">Message us on WhatsApp</span></div>
</div></div>
<div class="fc f1" style="transform: rotateY(120deg) translateZ(14.43px)" :style="{ '--d': s.dl }"><div class="ad" style="background: #FBF3E4; color: #3A1118" :style="{ left: s.o }">
<div style="position: absolute; inset: 10px; border: 1.5px solid #D8B48A; border-radius: 4px"></div>
<svg aria-hidden="true" width="600" height="340" viewBox="0 0 600 340" style="position: absolute; inset: 0" fill="none">
<ellipse cx="470" cy="284" rx="104" ry="13" fill="#EBD8BA"></ellipse>
<rect x="394" y="116" width="168" height="30" rx="4" fill="#7A1626"></rect>
<rect x="404" y="146" width="148" height="114" fill="#9E2236"></rect>
<path d="M404 206L420 196L436 206L452 196L468 206L484 196L500 206L516 196L532 206L552 196" stroke="#D4A017" stroke-width="2" opacity=".7"></path>
<rect x="470" y="116" width="16" height="144" fill="#D4A017"></rect>
<ellipse cx="462" cy="104" rx="17" ry="10" transform="rotate(-24 462 104)" stroke="#D4A017" stroke-width="6"></ellipse>
<ellipse cx="494" cy="104" rx="17" ry="10" transform="rotate(24 494 104)" stroke="#D4A017" stroke-width="6"></ellipse>
<circle cx="478" cy="112" r="7" fill="#D4A017"></circle>
<g fill="#E8A83A"><circle cx="404" cy="266" r="20"></circle><circle cx="442" cy="270" r="20"></circle><circle cx="423" cy="240" r="19"></circle></g>
<g fill="#C98722"><circle cx="398" cy="260" r="1.6"></circle><circle cx="410" cy="272" r="1.6"></circle><circle cx="436" cy="264" r="1.6"></circle><circle cx="448" cy="276" r="1.6"></circle><circle cx="418" cy="234" r="1.6"></circle><circle cx="429" cy="246" r="1.6"></circle></g>
<path d="M534 272Q560 298 586 272Z" fill="#B4532A"></path>
<path d="M560 268C551 258 556 246 560 238C564 246 569 258 560 268Z" fill="#F2A007"></path>
</svg>
<span style="position: absolute; left: 34px; top: 32px; font: 600 11px/1 var(--fm); letter-spacing: .12em; color: #8A1C2B">JOSHI SWEETS · SADASHIV PETH</span>
<div class="hd" style="position: absolute; left: 34px; top: 56px; font-size: 56px; line-height: .94">Diwali Faral,<br>made at home.</div>
<div style="position: absolute; left: 34px; top: 176px; font: 500 15px/1.3 var(--f); color: #5E3A2C">Chakli · Besan Ladoo · Karanji · Shankarpali</div>
<span style="position: absolute; left: 34px; top: 212px; background: #8A1C2B; color: #FBF3E4; font: 600 15px/1 var(--f); padding: 10px 14px; border-radius: 99px">Gift boxes from ₹499</span>
<span style="position: absolute; left: 34px; bottom: 30px; font: 500 11.5px/1 var(--fm); color: #5E3A2C">Free home delivery in Pune · Order on WhatsApp</span>
</div></div>
<div class="fc f2" style="transform: rotateY(240deg) translateZ(14.43px)" :style="{ '--d': s.dl }"><div class="ad" style="background: #1E3A8A; color: #F8FAFC" :style="{ left: s.o }">
<span class="hd" aria-hidden="true" style="position: absolute; right: -6px; top: -36px; font-size: 250px; line-height: 1; color: #24439A">A+</span>
<div style="position: absolute; left: 30px; top: 24px; display: flex; align-items: center; gap: 9px"><span style="width: 26px; height: 26px; border-radius: 7px; background: #FACC15; color: #0F172A; display: grid; place-items: center; font: 700 14px/1 var(--f)">P</span><span style="font: 700 12.5px/1 var(--f); letter-spacing: .1em">PRAGATI CLASSES</span></div>
<span style="position: absolute; right: 30px; top: 28px; background: #FACC15; color: #0F172A; font: 600 10.5px/1 var(--fm); letter-spacing: .06em; padding: 6px 10px; border-radius: 99px">ADMISSIONS OPEN · STD 8–12</span>
<div class="hd" style="position: absolute; left: 30px; top: 70px; font-size: 56px; line-height: .94">Ace your<br><span style="color: #FACC15">Board exams.</span></div>
<div style="position: absolute; left: 30px; top: 190px; display: flex; flex-direction: column; gap: 9px; font: 500 14px/1 var(--f); color: #DBE4FF"><span class="chk-row"><svg aria-hidden="true" width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6.2" fill="#FACC15"></circle><path d="M4.2 7.2L6.1 9L9.8 5.2" stroke="#0F172A" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>Small batches, personal attention</span><span class="chk-row"><svg aria-hidden="true" width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6.2" fill="#FACC15"></circle><path d="M4.2 7.2L6.1 9L9.8 5.2" stroke="#0F172A" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>Weekly tests &amp; progress reports</span><span class="chk-row"><svg aria-hidden="true" width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6.2" fill="#FACC15"></circle><path d="M4.2 7.2L6.1 9L9.8 5.2" stroke="#0F172A" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>Daily doubt-solving sessions</span></div>
<div style="position: absolute; left: 394px; top: 80px; width: 176px; height: 172px; background: #F8FAFC; color: #0F172A; border-radius: 12px; transform: rotate(3deg); box-sizing: border-box; padding: 16px 16px 0">
<span style="font: 600 10.5px/1 var(--fm); letter-spacing: .12em; color: #1E3A8A">FREE</span>
<div class="hd" style="font-size: 32px; line-height: .95; margin-top: 6px">Demo<br>class</div>
<div style="font: 600 13.5px/1 var(--f); margin-top: 10px">Sunday · 10 AM</div>
<div style="position: absolute; left: 12px; right: 12px; top: 128px; border-top: 2px dashed #CBD5E1"></div>
<span style="position: absolute; left: -9px; top: 120px; width: 18px; height: 18px; border-radius: 50%; background: #1E3A8A"></span>
<span style="position: absolute; right: -9px; top: 120px; width: 18px; height: 18px; border-radius: 50%; background: #1E3A8A"></span>
<div style="position: absolute; left: 16px; right: 16px; top: 144px; font: 500 11.5px/1.2 var(--f); color: #334155">Book a seat on WhatsApp</div>
</div>
<div style="position: absolute; left: 0; right: 0; bottom: 0; height: 44px; background: #FACC15; color: #0F172A; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-sizing: border-box"><span style="font: 600 12.5px/1 var(--f)">Station Road, Nashik</span><span style="font: 500 11.5px/1 var(--fm)">Limited seats in every batch</span></div>
</div></div>
</div>
</div>
<div class="cone" style="left: 40px"></div><div class="cone" style="left: 250px"></div><div class="cone" style="left: 460px"></div>
</div>
<div class="lane" aria-hidden="true" style="top: 741px"><svg class="vh vh-r1" width="66" height="30" viewBox="0 0 66 30" fill="none"><path d="M6 22V12C6 5.5 11 2 20 2H44C51 2 55 5 57.5 11L62 18V22Z" fill="#273449"></path><path d="M40 6H49C52 6 54 8.5 55.5 13H40Z" fill="#3B4A63"></path><path d="M12 7H34V13H12Z" fill="#1E293B"></path><rect x="61.5" y="15" width="3" height="4" rx="1" fill="#F2A007"></rect><circle cx="16" cy="24" r="5" fill="#0B1220" stroke="#334155" stroke-width="2"></circle><circle cx="51" cy="24" r="5" fill="#0B1220" stroke="#334155" stroke-width="2"></circle></svg><svg class="vh vh-r2" width="78" height="30" viewBox="0 0 78 30" fill="none"><rect x="2" y="4" width="50" height="18" rx="3" fill="#1E293B"></rect><path d="M52 8H64C68 8 71 12 72 16V22H52Z" fill="#273449"></path><path d="M56 11H63C65 11 67 13 68 16H56Z" fill="#3B4A63"></path><rect x="8" y="9" width="30" height="4" rx="2" fill="#F2A007" opacity=".6"></rect><rect x="72" y="16" width="3" height="4" rx="1" fill="#F2A007"></rect><circle cx="16" cy="24" r="5" fill="#0B1220" stroke="#334155" stroke-width="2"></circle><circle cx="60" cy="24" r="5" fill="#0B1220" stroke="#334155" stroke-width="2"></circle></svg></div>
<div class="lane" aria-hidden="true" style="top: 754px"><svg class="vh vh-l1" width="48" height="30" viewBox="0 0 48 30" fill="none"><g transform="translate(48 0) scale(-1 1)"><path d="M9 24L14 17H30L34 22H39" stroke="#273449" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M36 9L39 22" stroke="#334155" stroke-width="2.5" stroke-linecap="round"></path><path d="M22 9L19 17H28L30 12" stroke="#334155" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="24" cy="4.5" r="3.6" fill="#334155"></circle><rect x="39" y="9" width="3" height="3" rx="1" fill="#F2A007"></rect><circle cx="9" cy="24" r="5" fill="#0B1220" stroke="#334155" stroke-width="2"></circle><circle cx="39" cy="24" r="5" fill="#0B1220" stroke="#334155" stroke-width="2"></circle></g></svg><svg class="vh vh-l2" width="48" height="30" viewBox="0 0 48 30" fill="none"><g transform="translate(48 0) scale(-1 1)"><path d="M9 24L14 17H30L34 22H39" stroke="#1E293B" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M36 9L39 22" stroke="#334155" stroke-width="2.5" stroke-linecap="round"></path><path d="M22 9L19 17H28L30 12" stroke="#3B4A63" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="24" cy="4.5" r="3.6" fill="#3B4A63"></circle><rect x="39" y="9" width="3" height="3" rx="1" fill="#F2A007"></rect><circle cx="9" cy="24" r="5" fill="#0B1220" stroke="#334155" stroke-width="2"></circle><circle cx="39" cy="24" r="5" fill="#0B1220" stroke="#334155" stroke-width="2"></circle></g></svg></div>
<div style="position: absolute; left: calc(50% - 44px); top: 606px; width: 88px; padding: 5px 0; text-align: center; background: #273449; border-radius: 3px; font: 500 9.5px/1 var(--fm); letter-spacing: .08em; color: #94A3B8">SITE 06</div>
<div style="position: absolute; left: 46px; top: 58px; right: 46px; display: flex; flex-direction: column; gap: 16px">
<div style="height: 16px; display: flex; justify-content: space-between; align-items: center"><span style="display: inline-flex; align-items: center; gap: 8px; font: 500 11px/1 var(--fm); color: #64748B; letter-spacing: .08em"><span style="width: 6px; height: 6px; border-radius: 50%; background: #F2A007"></span>NOW SHOWING · COLLEGE ROAD</span><div style="display: flex; gap: 6px; align-items: center"><span v-for="(d, j) in dots" :key="j" :style="{ position: 'relative', width: d.w, height: '4px', borderRadius: '2px', background: '#334155', overflow: 'hidden', transition: 'width .5s cubic-bezier(.2,.7,.2,1)' }"><span v-if="d.on" class="pg"></span></span></div></div>
<div :key="k" class="meta-in" style="height: 30px; display: flex; justify-content: space-between; align-items: center"><div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; min-width: 0"><span style="font: 600 22px/30px var(--f); color: #F8FAFC; letter-spacing: -0.02em">{{ curMeta.shop }}</span><span style="height: 24px; display: inline-flex; align-items: center; box-sizing: border-box; padding: 0 10px; border: 1px solid #334155; border-radius: 99px; font: 500 12px/1 var(--f); color: #94A3B8">{{ curMeta.cat }}</span></div></div>
</div>
<div style="position: absolute; left: 46px; right: 46px; bottom: 32px; display: flex; flex-direction: column; gap: 8px"><span style="font: 600 22px/1.25 var(--f); color: #F8FAFC; letter-spacing: -0.02em">The World Should See You</span><span style="font: 500 12px/1.5 var(--fm); color: #94A3B8">Dikho makes your posts, ads and WhatsApp offers, sends them to people nearby, and shows what each one brought in - in Hindi, Marathi or English.</span></div>
</div>
</div>
<div class="login-form" style="flex: 1 1 520px; min-width: 0; display: flex; flex-direction: column; padding: 32px 48px">
<div style="display: flex; align-items: center; gap: 12px">
<RouterLink class="brand" style="padding: 0" :to="{ name: 'landing' }" aria-label="Dikho home"><Logo /></RouterLink>
<span class="sp"></span>
<div class="seg" role="group" aria-label="Language"><button v-for="(l, i) in LANGS" :key="l[1]" type="button" :class="lang === i ? 'on' : ''" @click="lang = i">{{ l[0] }}</button></div>
</div>
<div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 32px 0">
<div class="auth" :lang="lc">

<!-- step 1: form -->
<form v-if="step === 'form'" :key="'f' + isSignup" class="rise auth__body" novalidate @submit.prevent="submitForm">
<RouterLink class="auth__back" :to="{ name: 'landing' }"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"></path></svg>{{ t.home }}</RouterLink>
<div class="auth__head">
<h1 class="h-lg">{{ isSignup ? t.upTitle : t.inTitle }}</h1>
<p class="ts muted">{{ isSignup ? t.upSub : t.inSub }}</p>
</div>
<button type="button" class="btn bs lg full" @click="google">
<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="#4285F4" d="M17.6 9.2c0-.6-.1-1.2-.2-1.8H9v3.4h4.8a4.1 4.1 0 0 1-1.8 2.7v2.2h2.9c1.7-1.6 2.7-3.9 2.7-6.5z"></path><path fill="#34A853" d="M9 18c2.4 0 4.5-.8 6-2.2l-2.9-2.2c-.8.5-1.8.9-3.1.9-2.4 0-4.4-1.6-5.1-3.7H.9v2.3A9 9 0 0 0 9 18z"></path><path fill="#FBBC05" d="M3.9 10.7a5.4 5.4 0 0 1 0-3.4V5H.9a9 9 0 0 0 0 8z"></path><path fill="#EA4335" d="M9 3.6c1.3 0 2.5.5 3.4 1.4l2.6-2.6A9 9 0 0 0 .9 5l3 2.3C4.6 5.2 6.6 3.6 9 3.6z"></path></svg>
{{ t.google }}
</button>
<div class="or">{{ t.or }}</div>
<div v-if="isSignup" class="fld">
<label class="lab" for="nm">{{ t.name }}</label>
<input id="nm" v-model="name" class="in lg" :class="{ bad: errs.name }" type="text" autocomplete="name" :placeholder="t.namePh" :aria-invalid="!!errs.name">
<span v-if="errs.name" class="err">{{ errs.name }}</span>
</div>
<div class="fld">
<label class="lab" for="em">{{ t.email }}</label>
<input id="em" v-model="email" class="in lg" :class="{ bad: errs.email }" type="email" autocomplete="email" inputmode="email" :placeholder="t.emailPh" :aria-invalid="!!errs.email">
<span v-if="errs.email" class="err">{{ errs.email }}</span>
</div>
<div v-if="isSignup" class="fld">
<label class="lab" for="ph">{{ t.phone }}</label>
<div class="iw"><span class="pre">+91</span><input id="ph" class="in lg" :class="{ bad: errs.phone }" type="tel" inputmode="numeric" autocomplete="tel-national" placeholder="98765 43210" :value="phone" :aria-invalid="!!errs.phone" @input="onPhone"></div>
<span v-if="errs.phone" class="err">{{ errs.phone }}</span>
<span v-else class="hint">{{ t.phoneHint }}</span>
</div>
<div v-if="isSignup" class="fld">
<label class="agree"><input v-model="agree" type="checkbox"><span>{{ t.agreeA }} <a href="#" class="lnk" @click.prevent>{{ t.terms }}</a> {{ t.and }} <a href="#" class="lnk" @click.prevent>{{ t.privacy }}</a></span></label>
<span v-if="errs.agree" class="err">{{ errs.agree }}</span>
</div>
<button type="submit" class="btn bp lg full" :disabled="busy" :aria-busy="busy">
<span v-if="busy" class="spinner" aria-hidden="true"></span>{{ busy ? t.sending : t.cont }}
</button>
<p v-if="isSignup" class="ts muted auth__switch">{{ t.haveAcc }} <RouterLink class="lnk" :to="{ name: 'login', query: route.query.next ? { next: route.query.next } : {} }">{{ t.signIn }}</RouterLink></p>
<p v-else class="ts muted auth__switch">{{ t.noAcc }} <RouterLink class="lnk" :to="{ name: 'signup', query: route.query.next ? { next: route.query.next } : {} }">{{ t.signUp }}</RouterLink></p>
</form>

<!-- step 2: email code -->
<form v-else class="rise auth__body" novalidate @submit.prevent="verify">
<button type="button" class="auth__back" @click="goBack"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"></path></svg>{{ t.back }}</button>
<div class="auth__head">
<h1 class="h-lg">{{ t.codeTitle }}</h1>
<p class="ts muted">{{ t.codeSub }} <b style="color: #0F172A">{{ email.trim() }}</b></p>
</div>
<div class="fld">
<label class="lab" for="cd">{{ t.codeLabel }}</label>
<div class="otp" :class="{ bad: codeTried && code.length !== 6 }"><span v-for="(c, i) in cells" :key="i" :class="c.cls">{{ c.d }}</span><input id="cd" inputmode="numeric" autocomplete="one-time-code" maxlength="6" :value="code" :aria-invalid="codeTried && code.length !== 6" @input="onCode"></div>
<span v-if="codeTried && code.length !== 6" class="err">{{ t.errCode }}</span>
</div>
<button type="submit" class="btn bp lg full" :disabled="busy" :aria-busy="busy">
<span v-if="busy" class="spinner" aria-hidden="true"></span>{{ busy ? t.verifying : t.verify }}
</button>
<div class="auth__row">
<span v-if="secs > 0" class="ts muted">{{ t.resendIn }} 0:{{ String(secs).padStart(2, '0') }}</span>
<button v-else type="button" class="lnk ts" @click="resend">{{ t.resend }}</button>
<button type="button" class="lnk ts" @click="goBack">{{ t.otherEmail }}</button>
</div>
</form>

</div>
</div>
<p v-if="step === 'form' && !isSignup" class="tx muted">{{ t.help }} <a href="#" class="lnk" @click.prevent>{{ t.assisted }}</a></p>
</div>
</div>
</template>

<style scoped>
.or{display:flex;align-items:center;gap:12px;color:#94A3B8;font:500 11px var(--fm);letter-spacing:.08em}.or::before,.or::after{content:"";flex:1;height:1px;background:#E2E8F0}.pr{position:absolute;top:0;width:50px;height:340px;transform-style:preserve-3d;transform:translateZ(-14.43px);animation:tri 13.8s infinite both}@keyframes tri{0%,25.36%{transform:translateZ(-14.43px) rotateY(0deg);animation-timing-function:cubic-bezier(.7,0,.2,1)}33.33%,58.7%{transform:translateZ(-14.43px) rotateY(-120deg);animation-timing-function:cubic-bezier(.7,0,.2,1)}66.67%,92.03%{transform:translateZ(-14.43px) rotateY(-240deg);animation-timing-function:cubic-bezier(.7,0,.2,1)}100%{transform:translateZ(-14.43px) rotateY(-360deg)}}.fc:before{content:'';position:absolute;inset:0;z-index:2;background:#0B1220;opacity:0;pointer-events:none;animation:13.8s infinite both;animation-delay:var(--d)}.f0:before{animation-name:sh0}.f1:before{animation-name:sh1}.f2:before{animation-name:sh2}@keyframes sh0{0%,25.36%{opacity:0}33.33%,92.03%{opacity:.65}100%{opacity:0}}@keyframes sh1{0%,25.36%{opacity:.65}33.33%,58.7%{opacity:0}66.67%,100%{opacity:.65}}@keyframes sh2{0%,58.7%{opacity:.65}66.67%,92.03%{opacity:0}100%{opacity:.65}}.pg{position:absolute;inset:0;background:#F2A007;border-radius:2px;transform-origin:left;animation:pg 4.6s linear both}@keyframes pg{from{transform:scaleX(0)}}.lane{position:absolute;left:0;width:100%;height:30px;overflow:hidden;pointer-events:none}.vh{position:absolute;left:0;bottom:0}.vh-r1{animation:drive 12s linear -3s infinite}.vh-r2{animation:drive 12s linear -9s infinite}.vh-l1{animation:driveL 9s linear -6s infinite}.vh-l2{animation:driveL 9s linear -1.5s infinite}.bb-center{position:absolute;top:0;left:50%;width:720px;height:900px;margin-left:-360px}@keyframes drive{from{left:-90px}to{left:100%}}@keyframes driveL{from{left:100%}to{left:-90px}}.rx{position:absolute;top:664px;display:inline-flex;gap:7px;align-items:center;white-space:nowrap;background:#0F172A;border:1px solid #334155;border-radius:99px;padding:6px 10px;font:500 10.5px/1 var(--fm);color:#CBD5E1;animation:rx 1.8s cubic-bezier(.2,.7,.2,1) both}.rx i{width:6px;height:6px;border-radius:50%;background:#F2A007}@keyframes rx{0%{opacity:0;transform:translateY(14px)}18%{opacity:1;transform:translateY(0)}70%{opacity:1;transform:translateY(-10px)}100%{opacity:0;transform:translateY(-22px)}}.fc{position:absolute;inset:0;overflow:hidden;backface-visibility:hidden;-webkit-backface-visibility:hidden}.fc:after{content:'';position:absolute;right:0;top:0;bottom:0;width:1px;background:rgba(15,23,42,.22)}.ad{position:absolute;top:0;width:600px;height:340px;box-sizing:border-box;overflow:hidden}.meta-in{animation:mt .6s cubic-bezier(.2,.7,.2,1) .5s both}@keyframes mt{from{opacity:0;transform:translateY(6px)}}.hd{font-family:'Anek Latin',var(--fp);font-weight:700;font-stretch:84%;letter-spacing:-0.01em}.chk-row{display:inline-flex;gap:6px;align-items:center}.cone{position:absolute;top:178px;width:220px;height:330px;clip-path:polygon(45% 0,55% 0,100% 100%,0 100%);background:linear-gradient(180deg,rgba(255,236,190,.2),rgba(255,236,190,0) 80%);pointer-events:none}
</style>

<style scoped>
@media (max-width: 1279px) {
    .login-form {
        padding: 28px 32px !important;
    }
}
@media (max-width: 1099px) {
    .login-new {
        display: none;
    }
}
@media (max-width: 899px) {
    .login-art {
        flex: 1 1 100% !important;
        min-height: 0 !important;
    }
    .login-form {
        padding: 20px 16px 28px !important;
    }
}
.auth {
    width: 100%;
    max-width: 380px;
}
.auth__body {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.auth__head {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.auth__back {
    align-self: flex-start;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 32px;
    padding: 0 10px 0 6px;
    margin-left: -6px;
    border: 0;
    border-radius: 8px;
    background: none;
    color: var(--ink-2);
    font: 500 13.5px/1 var(--f);
    text-decoration: none;
    cursor: pointer;
}
.auth__back:hover {
    background: var(--sunk);
    color: var(--ink);
}
.auth__switch {
    text-align: center;
}
.auth__row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.in.bad {
    border-color: #f87171;
}
.agree {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    font: 400 13px/1.45 var(--f);
    color: var(--ink-2);
    cursor: pointer;
}
.agree input {
    width: 16px;
    height: 16px;
    margin: 2px 0 0;
    accent-color: var(--ink);
    flex-shrink: 0;
}
.spinner {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    animation: spin 0.8s linear infinite;
}
.btn:disabled {
    opacity: 0.75;
    cursor: progress;
}
/* Short laptop screens (e.g. 1366 x 657): keep the sign-up form close to one screen. */
@media (min-width: 900px) and (max-height: 760px) {
    .login-form {
        padding-top: 20px !important;
        padding-bottom: 16px !important;
    }
    .auth__body {
        gap: 13px;
    }
    .auth__head h1 {
        font-size: 24px;
    }
}
</style>
