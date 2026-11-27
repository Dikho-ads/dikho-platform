<script setup lang="ts">
// Sign in and sign up share this screen: email (plus name and WhatsApp for sign-up) → 6-digit
// code by email → verify. The code step lives in the URL (?step=code) so Back returns to the form.
// In local mode the API returns debug_code, shown as "Demo code" with a one-tap fill.
const props = defineProps<{ mode: 'login' | 'signup' }>();

const auth = useAuth();
const toast = useToast();
const route = useRoute();
const router = useRouter();

type Copy = Record<string, string>;
const LANGS: [string, string, Copy][] = [
    ['EN', 'en', {
        inTitle: 'Welcome back', inSub: "Sign in to manage your shop's marketing.",
        upTitle: 'Create your Dikho account', upSub: 'Free to start. Set up your shop in about 3 minutes.',
        name: 'Your name', namePh: 'Your full name', email: 'Email address', emailPh: 'you@example.com',
        phone: 'WhatsApp number', phoneHint: 'For post reminders and customer chats. Never shared.',
        agreeA: 'I agree to the', terms: 'Terms of Service', and: 'and', privacy: 'Privacy Policy',
        cont: 'Continue', sending: 'Sending code…', codeTitle: 'Check your email', codeSub: 'We sent a 6-digit code to',
        codeLabel: 'Verification code', verify: 'Verify and continue', verifying: 'Verifying…',
        resendIn: 'Resend code in', resend: 'Resend code', resending: 'Sending…', otherEmail: 'Use a different email', back: 'Back', home: 'Home',
        noAcc: "Don't have an account?", signUp: 'Sign up', haveAcc: 'Already have an account?', signIn: 'Sign in',
        demoCode: 'Demo code', fill: 'Tap to fill', demo: 'Open the demo shop', demoHint: 'Signs in to the sample account with a demo code. Local build only.',
        errName: 'Enter your name.', errEmail: 'Enter a valid email address.', errPhone: 'Enter a 10-digit mobile number.',
        errAgree: 'Please accept the terms to continue.', errCode: 'Enter all 6 digits of the code.', errMany: 'Too many tries. Wait a minute and try again.',
        sent: 'Code sent to', resent: 'New code sent',
    }],
    ['हिन्दी', 'hi', {
        inTitle: 'फिर से स्वागत है', inSub: 'अपनी दुकान की मार्केटिंग संभालने के लिए साइन इन करें।',
        upTitle: 'अपना Dikho अकाउंट बनाएं', upSub: 'शुरुआत मुफ़्त। करीब 3 मिनट में दुकान सेट करें।',
        name: 'आपका नाम', namePh: 'आपका पूरा नाम', email: 'ईमेल पता', emailPh: 'you@example.com',
        phone: 'WhatsApp नंबर', phoneHint: 'पोस्ट रिमाइंडर और ग्राहक चैट के लिए। किसी से शेयर नहीं होता।',
        agreeA: 'मैं सहमत हूं', terms: 'सेवा की शर्तों', and: 'और', privacy: 'प्राइवेसी पॉलिसी',
        cont: 'आगे बढ़ें', sending: 'कोड भेज रहे हैं…', codeTitle: 'अपना ईमेल देखें', codeSub: '6 अंकों का कोड भेजा गया',
        codeLabel: 'वेरिफ़िकेशन कोड', verify: 'वेरिफ़ाई करें', verifying: 'वेरिफ़ाई हो रहा है…',
        resendIn: 'कोड दोबारा भेजें', resend: 'कोड दोबारा भेजें', resending: 'भेज रहे हैं…', otherEmail: 'दूसरा ईमेल इस्तेमाल करें', back: 'वापस', home: 'होम',
        noAcc: 'अकाउंट नहीं है?', signUp: 'साइन अप करें', haveAcc: 'पहले से अकाउंट है?', signIn: 'साइन इन करें',
        demoCode: 'डेमो कोड', fill: 'भरें', demo: 'डेमो दुकान खोलें', demoHint: 'डेमो कोड से सैंपल अकाउंट में साइन इन। सिर्फ़ लोकल बिल्ड में।',
        errName: 'अपना नाम लिखें।', errEmail: 'सही ईमेल पता लिखें।', errPhone: '10 अंकों का मोबाइल नंबर लिखें।',
        errAgree: 'आगे बढ़ने के लिए शर्तें स्वीकार करें।', errCode: 'कोड के सभी 6 अंक लिखें।', errMany: 'बहुत बार कोशिश हुई। एक मिनट रुककर फिर करें।',
        sent: 'कोड भेजा गया', resent: 'नया कोड भेजा गया',
    }],
    ['मराठी', 'mr', {
        inTitle: 'पुन्हा स्वागत आहे', inSub: 'तुमच्या दुकानाचे मार्केटिंग सांभाळण्यासाठी साइन इन करा.',
        upTitle: 'तुमचे Dikho खाते तयार करा', upSub: 'सुरुवात मोफत. सुमारे 3 मिनिटांत दुकान सेट करा.',
        name: 'तुमचे नाव', namePh: 'तुमचे पूर्ण नाव', email: 'ईमेल पत्ता', emailPh: 'you@example.com',
        phone: 'WhatsApp नंबर', phoneHint: 'पोस्ट रिमाइंडर आणि ग्राहकांच्या चॅटसाठी. कोणाशीही शेअर होत नाही.',
        agreeA: 'मी सहमत आहे', terms: 'सेवा अटी', and: 'आणि', privacy: 'गोपनीयता धोरण',
        cont: 'पुढे जा', sending: 'कोड पाठवत आहोत…', codeTitle: 'तुमचा ईमेल तपासा', codeSub: '6 अंकी कोड पाठवला आहे',
        codeLabel: 'पडताळणी कोड', verify: 'पडताळणी करा', verifying: 'पडताळणी सुरू आहे…',
        resendIn: 'कोड पुन्हा पाठवा', resend: 'कोड पुन्हा पाठवा', resending: 'पाठवत आहोत…', otherEmail: 'दुसरा ईमेल वापरा', back: 'मागे', home: 'होम',
        noAcc: 'खाते नाही?', signUp: 'साइन अप करा', haveAcc: 'आधीच खाते आहे?', signIn: 'साइन इन करा',
        demoCode: 'डेमो कोड', fill: 'भरा', demo: 'डेमो दुकान उघडा', demoHint: 'डेमो कोडने सॅम्पल खात्यात साइन इन. फक्त लोकल बिल्डमध्ये.',
        errName: 'तुमचे नाव लिहा.', errEmail: 'योग्य ईमेल पत्ता लिहा.', errPhone: '10 अंकी मोबाइल नंबर लिहा.',
        errAgree: 'पुढे जाण्यासाठी अटी स्वीकारा.', errCode: 'कोडचे सर्व 6 अंक लिहा.', errMany: 'खूप वेळा प्रयत्न झाला. एक मिनिट थांबून पुन्हा करा.',
        sent: 'कोड पाठवला', resent: 'नवीन कोड पाठवला',
    }],
];

// The sample account the demo shortcut signs in to (seeded locally; see API.md "Demo accounts").
const DEMO_EMAIL = 'ramesh@saimobile.test';
// Dev builds talk to a local API that returns debug codes; production never shows the shortcut.
const showDemo = import.meta.dev;

const lang = useState('auth-lang', () => 0);
const isSignup = computed(() => props.mode === 'signup');
// Form values survive the ?step=code navigation (same component instance on the same route).
const name = useState('auth-name', () => '');
const email = useState('auth-email', () => '');
const phone = useState('auth-phone', () => '');
const agree = ref(false);
const code = ref('');
const secs = ref(30);
const tried = ref(false);
const codeTried = ref(false);
const busy = ref(false);
const resending = ref(false);
const demoBusy = ref(false);
const debugCode = ref<string | null>(null);
// Purpose used for the last code request (the demo shortcut always signs in).
const purpose = ref<'login' | 'signup'>(props.mode);
const serverErr = ref<Record<string, string>>({});
const codeErr = ref('');

const t = computed(() => LANGS[lang.value]![2]);
const lc = computed(() => LANGS[lang.value]![1]);
const emailOk = computed(() => /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(email.value.trim()));
const nameOk = computed(() => name.value.trim().length >= 2);
const phoneOk = computed(() => /^[6-9]\d{9}$/.test(phone.value));
const step = computed(() => (route.query.step === 'code' && emailOk.value ? 'code' : 'form'));
const errs = computed(() => {
    const e: Record<string, string> = {};
    if (tried.value) {
        if (isSignup.value && !nameOk.value) e.name = t.value.errName!;
        if (!emailOk.value) e.email = t.value.errEmail!;
        if (isSignup.value && !phoneOk.value) e.phone = t.value.errPhone!;
        if (isSignup.value && !agree.value) e.agree = t.value.errAgree!;
    }
    for (const [k, v] of Object.entries(serverErr.value)) if (!e[k]) e[k] = v;
    return e;
});
const cells = computed(() => [0, 1, 2, 3, 4, 5].map((i) => ({ d: code.value[i] || '', cls: 'c' + (i === code.value.length ? ' on' : '') })));
const codeBad = computed(() => (codeTried.value && code.value.length !== 6) || !!codeErr.value);
const switchQuery = computed(() => (typeof route.query.next === 'string' ? { next: route.query.next } : {}));

watch([name, email, phone], () => (serverErr.value = {}));

let bv: ReturnType<typeof setInterval> | null = null;
onMounted(() => {
    bv = setInterval(() => {
        if (step.value === 'code' && secs.value > 0) secs.value -= 1;
    }, 1000);
});
onBeforeUnmount(() => {
    if (bv) clearInterval(bv);
});

function message(e: ApiError) {
    return e.status === 429 ? t.value.errMany! : e.message;
}

function onCode(e: Event) {
    const el = e.target as HTMLInputElement;
    code.value = el.value.replace(/\D/g, '').slice(0, 6);
    el.value = code.value;
    codeErr.value = '';
    if (code.value.length === 6) verify();
}
function onPhone(e: Event) {
    const el = e.target as HTMLInputElement;
    phone.value = el.value.replace(/\D/g, '').slice(0, 10);
    el.value = phone.value;
}

function focusCode() {
    nextTick(() => document.getElementById('cd')?.focus());
}

async function sendCode(p: 'login' | 'signup') {
    const payload: any = { email: email.value.trim(), purpose: p };
    if (p === 'signup') {
        payload.name = name.value.trim();
        payload.phone = phone.value;
    }
    const res = await auth.requestCode(payload);
    purpose.value = p;
    debugCode.value = res.debug_code ?? null;
    secs.value = 30;
    code.value = '';
    codeTried.value = false;
    codeErr.value = '';
    return res;
}

async function submitForm() {
    tried.value = true;
    serverErr.value = {};
    if (Object.keys(errs.value).length || busy.value) return;
    busy.value = true;
    try {
        await sendCode(props.mode);
        toast.ok(`${t.value.sent} ${email.value.trim()}`);
        await router.push({ query: { ...route.query, step: 'code' } });
        focusCode();
    } catch (err) {
        const e = toApiError(err);
        const fe: Record<string, string> = {};
        for (const k of ['name', 'email', 'phone']) if (e.field(k)) fe[k] = e.field(k)!;
        if (Object.keys(fe).length) serverErr.value = fe;
        else toast.error(message(e));
    } finally {
        busy.value = false;
    }
}

function goBack() {
    if (import.meta.client && window.history.state?.back) router.back();
    else router.replace({ query: { ...route.query, step: undefined } });
}

async function resend() {
    if (resending.value) return;
    resending.value = true;
    try {
        await sendCode(purpose.value);
        toast.ok(t.value.resent!);
        focusCode();
    } catch (err) {
        toast.error(message(toApiError(err)));
    } finally {
        resending.value = false;
    }
}

function fillDemoCode() {
    if (!debugCode.value) return;
    code.value = debugCode.value;
    codeErr.value = '';
    verify();
}

async function verify() {
    codeTried.value = true;
    if (code.value.length !== 6 || busy.value) return;
    busy.value = true;
    codeErr.value = '';
    try {
        const payload: any = { email: email.value.trim(), code: code.value, purpose: purpose.value };
        if (purpose.value === 'signup') {
            payload.name = name.value.trim();
            payload.phone = phone.value;
        }
        const account = await auth.verify(payload);
        name.value = '';
        phone.value = '';
        await goNext(account);
    } catch (err) {
        const e = toApiError(err);
        codeErr.value = e.field('code') || message(e);
        code.value = '';
        focusCode();
    } finally {
        busy.value = false;
    }
}

async function demo() {
    if (demoBusy.value) return;
    demoBusy.value = true;
    try {
        email.value = DEMO_EMAIL;
        serverErr.value = {};
        const res = await sendCode('login');
        await router.push({ query: { ...route.query, step: 'code' } });
        if (res.debug_code) {
            code.value = res.debug_code;
            await verify();
        } else {
            focusCode();
        }
    } catch (err) {
        toast.error(message(toApiError(err)));
    } finally {
        demoBusy.value = false;
    }
}

async function goNext(account: any) {
    const setupDone = !!account?.shop?.setup_complete;
    if (purpose.value === 'signup' || !setupDone) return navigateTo('/setup', { replace: true });
    const next = route.query.next;
    return navigateTo(typeof next === 'string' && next.startsWith('/app') ? next : '/app', { replace: true });
}
</script>

<template>
    <div class="app">
        <div class="dark login-art" style="min-width: 0; position: relative; overflow: hidden; display: flex; background: #0f172a">
            <div class="login-photo" aria-hidden="true"><Photo name="login-billboard" :rounded="false" /></div>
            <div class="login-shade" aria-hidden="true"></div>
            <div class="login-top" style="display: flex; align-items: center; gap: 8px; font: 500 11px/1 var(--fm); color: #cbd5e1; letter-spacing: 0.08em">
                <span style="width: 6px; height: 6px; border-radius: 50%; background: #f2a007"></span>FOR LOCAL SHOPS · हिन्दी · मराठी · ENGLISH
            </div>
            <div class="login-copy">
                <span style="font: 600 22px/1.25 var(--f); color: #f8fafc; letter-spacing: -0.02em">The World Should See You</span>
                <span style="font: 500 12px/1.5 var(--fm); color: #cbd5e1">Dikho makes your posts, ads and WhatsApp offers, sends them to people nearby, and shows what each one brought in - in Hindi, Marathi or English.</span>
            </div>
        </div>
        <div class="login-form" style="flex: 1 1 520px; min-width: 0; display: flex; flex-direction: column; padding: 32px 48px">
            <div style="display: flex; align-items: center; gap: 12px">
                <NuxtLink class="brand" style="padding: 0" to="/" aria-label="Dikho home"><Logo /></NuxtLink>
                <span class="sp"></span>
                <div class="seg" role="group" aria-label="Language">
                    <button v-for="(l, i) in LANGS" :key="l[1]" type="button" :class="lang === i ? 'on' : ''" @click="lang = i">{{ l[0] }}</button>
                </div>
            </div>
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 32px 0">
                <div class="auth" :lang="lc">
                    <!-- step 1: form -->
                    <form v-if="step === 'form'" :key="'f' + isSignup" class="rise auth__body" novalidate @submit.prevent="submitForm">
                        <NuxtLink class="auth__back" to="/"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"></path></svg>{{ t.home }}</NuxtLink>
                        <div class="auth__head">
                            <h1 class="h-lg">{{ isSignup ? t.upTitle : t.inTitle }}</h1>
                            <p class="ts muted">{{ isSignup ? t.upSub : t.inSub }}</p>
                        </div>
                        <div v-if="isSignup" class="fld">
                            <label class="lab" for="nm">{{ t.name }}</label>
                            <input id="nm" v-model="name" class="in lg" :class="{ bad: errs.name }" type="text" autocomplete="name" :placeholder="t.namePh" :aria-invalid="!!errs.name" />
                            <span v-if="errs.name" class="err">{{ errs.name }}</span>
                        </div>
                        <div class="fld">
                            <label class="lab" for="em">{{ t.email }}</label>
                            <input id="em" v-model="email" class="in lg" :class="{ bad: errs.email }" type="email" autocomplete="email" inputmode="email" :placeholder="t.emailPh" :aria-invalid="!!errs.email" />
                            <span v-if="errs.email" class="err">
                                {{ errs.email }}
                                <NuxtLink v-if="serverErr.email && !isSignup && /create/i.test(serverErr.email)" class="lnk" :to="{ path: '/signup', query: switchQuery }">{{ t.signUp }}</NuxtLink>
                                <NuxtLink v-else-if="serverErr.email && isSignup && /sign in/i.test(serverErr.email)" class="lnk" :to="{ path: '/login', query: switchQuery }">{{ t.signIn }}</NuxtLink>
                            </span>
                        </div>
                        <div v-if="isSignup" class="fld">
                            <label class="lab" for="ph">{{ t.phone }}</label>
                            <div class="iw"><span class="pre">+91</span><input id="ph" class="in lg" :class="{ bad: errs.phone }" type="tel" inputmode="numeric" autocomplete="tel-national" placeholder="98765 43210" :value="phone" :aria-invalid="!!errs.phone" @input="onPhone" /></div>
                            <span v-if="errs.phone" class="err">{{ errs.phone }}</span>
                            <span v-else class="hint">{{ t.phoneHint }}</span>
                        </div>
                        <div v-if="isSignup" class="fld">
                            <label class="agree"><input v-model="agree" type="checkbox" /><span>{{ t.agreeA }} <b>{{ t.terms }}</b> {{ t.and }} <b>{{ t.privacy }}</b></span></label>
                            <span v-if="errs.agree" class="err">{{ errs.agree }}</span>
                        </div>
                        <button type="submit" class="btn bp lg full" :disabled="busy || demoBusy" :aria-busy="busy">
                            <span v-if="busy" class="spinner" aria-hidden="true"></span>{{ busy ? t.sending : t.cont }}
                        </button>
                        <p v-if="isSignup" class="ts muted auth__switch">{{ t.haveAcc }} <NuxtLink class="lnk" :to="{ path: '/login', query: switchQuery }">{{ t.signIn }}</NuxtLink></p>
                        <p v-else class="ts muted auth__switch">{{ t.noAcc }} <NuxtLink class="lnk" :to="{ path: '/signup', query: switchQuery }">{{ t.signUp }}</NuxtLink></p>
                        <div v-if="showDemo" class="demo">
                            <button type="button" class="btn bs full" :disabled="busy || demoBusy" @click="demo">
                                <span v-if="demoBusy" class="spinner dark-spin" aria-hidden="true"></span>{{ demoBusy ? t.sending : t.demo }}
                            </button>
                            <span class="hint">{{ t.demoHint }}</span>
                        </div>
                    </form>

                    <!-- step 2: email code -->
                    <form v-else class="rise auth__body" novalidate @submit.prevent="verify">
                        <button type="button" class="auth__back" @click="goBack"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"></path></svg>{{ t.back }}</button>
                        <div class="auth__head">
                            <h1 class="h-lg">{{ t.codeTitle }}</h1>
                            <p class="ts muted">{{ t.codeSub }} <b style="color: #0f172a; overflow-wrap: anywhere">{{ email.trim() }}</b></p>
                        </div>
                        <button v-if="debugCode" type="button" class="democode" :disabled="busy" @click="fillDemoCode">
                            <span class="lm" style="font-family: var(--f)">{{ t.demoCode }}:</span>
                            <b class="mono" style="font-size: 15px; letter-spacing: 0.12em">{{ debugCode }}</b>
                            <span class="sp"></span>
                            <span class="tx" style="font-weight: 500">{{ t.fill }}</span>
                        </button>
                        <div class="fld">
                            <label class="lab" for="cd">{{ t.codeLabel }}</label>
                            <div class="otp" :class="{ bad: codeBad }">
                                <span v-for="(c, i) in cells" :key="i" :class="c.cls">{{ c.d }}</span>
                                <input id="cd" inputmode="numeric" autocomplete="one-time-code" maxlength="6" :value="code" :aria-invalid="codeBad" @input="onCode" />
                            </div>
                            <span v-if="codeErr" class="err">{{ codeErr }}</span>
                            <span v-else-if="codeTried && code.length !== 6" class="err">{{ t.errCode }}</span>
                        </div>
                        <button type="submit" class="btn bp lg full" :disabled="busy" :aria-busy="busy">
                            <span v-if="busy" class="spinner" aria-hidden="true"></span>{{ busy ? t.verifying : t.verify }}
                        </button>
                        <div class="auth__row">
                            <span v-if="secs > 0" class="ts muted">{{ t.resendIn }} 0:{{ String(secs).padStart(2, '0') }}</span>
                            <button v-else type="button" class="lnk ts" :disabled="resending" @click="resend">{{ resending ? t.resending : t.resend }}</button>
                            <button type="button" class="lnk ts" @click="goBack">{{ t.otherEmail }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-art {
    flex: 0 0 720px;
    min-height: 560px;
}
.login-photo {
    position: absolute;
    inset: 0;
}
.login-photo :deep(.photo__ph) {
    background: repeating-linear-gradient(135deg, #1e293b 0 10px, #172133 10px 20px);
    color: #94a3b8;
}
.login-photo :deep(.photo__ph b) {
    color: #cbd5e1;
}
.login-shade {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.55) 0%, rgba(15, 23, 42, 0) 22%, rgba(15, 23, 42, 0) 55%, rgba(15, 23, 42, 0.9) 100%);
}
.login-top {
    position: absolute;
    left: 40px;
    right: 40px;
    top: 36px;
}
.login-copy {
    position: absolute;
    left: 40px;
    right: 40px;
    bottom: 32px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
@media (max-width: 1279px) {
    .login-art {
        flex-basis: 576px;
    }
    .login-form {
        padding: 28px 32px !important;
    }
}
@media (max-width: 1099px) {
    .login-art {
        flex-basis: 475px;
    }
}
@media (max-width: 899px) {
    .login-art {
        flex: 1 1 100%;
        height: 46vh;
        max-height: 420px;
        min-height: 260px;
    }
    .login-top {
        left: 16px;
        right: 16px;
        top: 18px;
    }
    .login-copy {
        left: 16px;
        right: 16px;
        bottom: 18px;
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
.demo {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding-top: 14px;
    border-top: 1px dashed var(--line-2);
}
.democode {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 10px 14px;
    border: 1px dashed #f2a007;
    border-radius: 10px;
    background: #fff8e7;
    color: var(--ink);
    cursor: pointer;
    text-align: left;
}
.democode:hover {
    background: #fdefc8;
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
.agree b {
    font-weight: 500;
    color: var(--ink);
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
.spinner.dark-spin {
    border-color: rgba(15, 23, 42, 0.2);
    border-top-color: #0f172a;
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
