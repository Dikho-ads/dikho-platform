<script setup lang="ts">
// Messages: GET messages (list, audiences with counts per channel, rates). Compose a WhatsApp or
// SMS message, save it as a draft (POST messages / PATCH messages/{id}), then send now or
// schedule (POST messages/{id}/schedule), which charges the wallet after a confirm.
definePageMeta({ layout: 'app', title: 'Messages' });

const api = useApi();
const auth = useAuth();
const toast = useToast();
const { confirm } = useConfirm();

type Msg = {
    id: number;
    channel: 'whatsapp' | 'sms';
    name: string;
    template: string | null;
    language: string | null;
    body: string;
    buttons: string[] | null;
    audience: { stage?: string; customer_ids?: number[] } | null;
    status: 'draft' | 'scheduled' | 'sending' | 'sent';
    scheduled_at: string | null;
    sent_at: string | null;
    recipients: number;
    delivered: number;
    read: number;
    replies: number;
    cost_paise: number;
};

// Awaited so the first draft is picked on the server too (no hydration flip).
const { data, pending, error, refresh } = await useApiData<{ data: Msg[]; audiences: any[]; rates_paise: Record<string, number> }>('messages', 'messages');
const list = computed(() => data.value?.data ?? []);
const audiences = computed(() => data.value?.audiences ?? []);
const rates = computed(() => data.value?.rates_paise ?? {});

// A few real first names (people who said yes) for the preview; client-only, not needed for SSR.
const { data: people } = useAsyncData('messages:preview-people', () => api<any>('customers', { query: { consent: 1, per_page: 5 } }), { server: false, lazy: true });
const previewNames = computed<string[]>(() => (people.value?.data ?? []).slice(0, 3).map((c: any) => c.name.split(' ')[0]));

const CHANS = [
    ['whatsapp', 'WhatsApp'],
    ['sms', 'SMS'],
] as const;
const TOKENS = ['{name}', '{shop}'];
const BUTTONS = ['Call shop', 'Get directions'];
const ST: Record<string, [string, string]> = { draft: ['st', 'Draft'], scheduled: ['st ok', 'Scheduled'], sending: ['st ok live', 'Sending'], sent: ['st ink', 'Sent'] };

const shop = computed(() => auth.shop);
const langs = computed<string[]>(() => {
    const l = [...(shop.value?.languages ?? ['en'])];
    if (form.language && !l.includes(form.language)) l.push(form.language);
    return l;
});

// ---- selection and the form ----
const sel = ref<number | null>(null);
const selected = computed(() => list.value.find((m) => m.id === sel.value) ?? null);
const readOnly = computed(() => !!selected.value && selected.value.status !== 'draft');
const blank = () => ({ channel: 'whatsapp' as 'whatsapp' | 'sms', name: '', language: shop.value?.languages?.[0] ?? 'en', body: '', stage: 'all' });
const form = reactive(blank());
const errs = ref<Record<string, string | undefined>>({});
const who = ref(0); // index into previewNames
const saved = ref(''); // JSON of the last saved form, to know when a draft has changes
const snapshot = () => JSON.stringify(form);
const dirty = computed(() => snapshot() !== saved.value);

function load(m: Msg | null) {
    sel.value = m?.id ?? null;
    Object.assign(form, m ? { channel: m.channel, name: m.name, language: m.language ?? 'en', body: m.body, stage: m.audience?.stage ?? 'all' } : blank());
    saved.value = m ? snapshot() : '';
    errs.value = {};
    who.value = 0;
}
function newMessage() {
    load(null);
    nextTick(() => document.getElementById('mt')?.focus());
}
// Open the first draft, else a fresh form.
watch(
    list,
    (l) => {
        if (sel.value == null && !form.body) {
            const draft = l.find((m) => m.status === 'draft');
            if (draft) load(draft);
        }
    },
    { immediate: true },
);

const isSms = computed(() => form.channel === 'sms');
const audience = computed(() => audiences.value.find((a) => a.key === form.stage));
const n = computed(() => (readOnly.value ? selected.value!.recipients : audience.value ? audience.value[form.channel] : 0));
const leftOut = computed(() => (audience.value && form.channel === 'whatsapp' ? audience.value.sms - audience.value.whatsapp : 0));
const rate = computed(() => rates.value[form.channel] ?? 0);
const cost = computed(() => (readOnly.value ? selected.value!.cost_paise : n.value * rate.value));
const wallet = computed(() => auth.account?.wallet_paise ?? 0);

// ---- body helpers ----
const dev = computed(() => /[ऀ-ॿ]/.test(form.body));
const per = computed(() => (dev.value ? 70 : 160));
const fill = (t: string) =>
    t
        .replace(/\{name\}/g, previewNames.value[who.value] ?? previewNames.value[0] ?? 'Customer')
        .replace(/\{shop\}/g, shop.value?.name ?? '');
const filled = computed(() => fill(readOnly.value ? selected.value!.body : form.body));
const counter = computed(() => {
    const len = filled.value.length;
    return isSms.value ? `${len} / ${per.value} · ${Math.max(1, Math.ceil(len / per.value))} SMS` : `${len} / 1,024 chars`;
});
function addToken(t: string) {
    const el = document.getElementById('mt') as HTMLTextAreaElement | null;
    if (!el) return (form.body += t);
    const a = el.selectionStart ?? form.body.length;
    const b = el.selectionEnd ?? a;
    form.body = form.body.slice(0, a) + t + form.body.slice(b);
    nextTick(() => {
        el.focus();
        el.setSelectionRange(a + t.length, a + t.length);
    });
}

// ---- when ----
const when = ref<'now' | 'later'>('now');
const at = ref('');
const minAt = computed(() => {
    const d = new Date(Date.now() + 10 * 60000);
    return isoDate(d) + 'T' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
});

// ---- save and send ----
const busy = ref<'save' | 'send' | null>(null);
function payload() {
    const body = form.body.trim();
    return {
        channel: form.channel,
        name: form.name.trim() || body.replace(/\{\w+\}/g, '').trim().split(/\s+/).slice(0, 5).join(' ').slice(0, 60) || 'Message',
        body,
        language: form.language,
        audience: { stage: form.stage },
        buttons: form.channel === 'whatsapp' ? BUTTONS : null,
    };
}
async function save(quiet = false): Promise<Msg | null> {
    errs.value = {};
    if (!form.body.trim()) {
        errs.value = { body: 'Write the message first.' };
        return null;
    }
    try {
        const body = payload();
        const res = sel.value
            ? await api<{ data: Msg }>(`messages/${sel.value}`, { method: 'PATCH', body })
            : await api<{ data: Msg }>('messages', { method: 'POST', body });
        const m = res.data;
        const rest = list.value.filter((x) => x.id !== m.id);
        if (data.value) data.value = { ...data.value, data: [m, ...rest].sort((a, b) => b.id - a.id) };
        sel.value = m.id;
        form.name = m.name;
        saved.value = snapshot();
        if (!quiet) toast.ok(`Draft saved · ${num(m.recipients)} ${m.recipients === 1 ? 'person' : 'people'}`);
        return m;
    } catch (e: any) {
        if (Object.keys(e.errors || {}).length) {
            errs.value = Object.fromEntries(Object.keys(e.errors).map((k) => [k.split('.')[0], e.field(k)]));
            if (!errs.value.body && !errs.value.name) toast.error(e.message);
        } else toast.error(e.message);
        return null;
    }
}
async function onSave() {
    busy.value = 'save';
    await save();
    busy.value = null;
}
async function send() {
    if (when.value === 'later' && !at.value) {
        errs.value = { scheduled_at: 'Pick a date and time.' };
        return;
    }
    busy.value = 'send';
    try {
        const m = dirty.value || !sel.value ? await save(true) : selected.value;
        if (!m) return;
        if (!m.recipients) {
            toast.error(`Nobody in “${audience.value?.label ?? 'this group'}” can get ${m.channel === 'whatsapp' ? 'WhatsApp offers' : 'SMS'} yet.`);
            return;
        }
        const rateTxt = inr(rates.value[m.channel] ?? 0, { paise: true });
        const whenTxt = when.value === 'now' ? 'now' : eyebrowDate(at.value) + ' · ' + time(at.value);
        const ok = await confirm({
            title: when.value === 'now' ? `Send “${m.name}” to ${num(m.recipients)} people now?` : `Schedule “${m.name}” for ${whenTxt}?`,
            body: (when.value === 'now' ? 'Paid from the wallet now.' : 'The wallet is charged now, when it is scheduled.') + (m.channel === 'whatsapp' && leftOut.value ? ` ${num(leftOut.value)} people without WhatsApp consent are left out.` : ''),
            cost: `${num(m.recipients)} × ${rateTxt} = ${inr(m.cost_paise, { paise: true })} from wallet ${inr(wallet.value, { paise: true })}`,
            confirm: when.value === 'now' ? 'Send now' : 'Schedule',
        });
        if (!ok) return;
        const res = await api<{ data: Msg; account: any }>(`messages/${m.id}/schedule`, {
            method: 'POST',
            body: when.value === 'later' ? { scheduled_at: new Date(at.value).toISOString() } : {},
        });
        auth.set(res.account);
        if (data.value) data.value = { ...data.value, data: list.value.map((x) => (x.id === res.data.id ? res.data : x)) };
        toast.ok(`${when.value === 'now' ? 'Sending to' : 'Scheduled for'} ${num(res.data.recipients)} people · ${inr(res.data.cost_paise, { paise: true })} from wallet`);
        refresh();
    } catch (e: any) {
        if (e.code === 'insufficient_wallet') {
            toast.error(`Not enough in the wallet: ${inr(e.data?.needed, { paise: true })} needed, ${inr(e.data?.available, { paise: true })} available.`, {
                label: 'Add money',
                run: () => navigateTo('/app/settings?tab=wallet'),
            });
        } else if (e.field?.('scheduled_at')) errs.value = { scheduled_at: e.field('scheduled_at') };
        else toast.error(e.message);
    } finally {
        busy.value = null;
    }
}

const whenLabel = (m: Msg) =>
    m.status === 'draft' ? 'DRAFT' : m.sent_at ? 'SENT ' + eyebrowDate(m.sent_at) : m.scheduled_at ? eyebrowDate(m.scheduled_at) + ' · ' + time(m.scheduled_at) : m.status.toUpperCase();
const metaLabel = (m: Msg) =>
    m.status === 'sent' ? `${num(m.delivered)} delivered · ${num(m.read)} read · ${num(m.replies)} replies` : `${num(m.recipients)} people · ${inr(m.cost_paise, { paise: true })}`;
const audienceLabel = (m: Msg) => (m.audience?.customer_ids ? 'Picked customers' : (audiences.value.find((a) => a.key === (m.audience?.stage ?? 'all'))?.label ?? 'All customers'));
const pvTime = computed(() => {
    if (readOnly.value) return selected.value!.sent_at ? eyebrowDate(selected.value!.sent_at) + ' · ' + time(selected.value!.sent_at) : selected.value!.scheduled_at ? eyebrowDate(selected.value!.scheduled_at) + ' · ' + time(selected.value!.scheduled_at) : '';
    return when.value === 'now' || !at.value ? 'NOW' : eyebrowDate(at.value) + ' · ' + time(at.value);
});
const pvChannel = computed(() => (readOnly.value ? selected.value!.channel : form.channel));
const pvButtons = computed(() => (readOnly.value ? (selected.value!.buttons ?? []) : pvChannel.value === 'whatsapp' ? BUTTONS : []));
</script>

<template>
    <div class="wrap" style="max-width: 1400px">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 420px">
                <span class="eyb"><b>MESSAGES</b>·<span>WHATSAPP &amp; SMS · सीधा ग्राहक तक</span></span>
                <h1 class="h-lg">Messages that reach the customer’s phone</h1>
                <p class="ts muted">Write it once in your language; WhatsApp goes only to people who said yes.</p>
            </div>
            <button type="button" class="btn bs" @click="newMessage"><svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>New message</button>
        </div>

        <div v-if="!data" class="panel"><LoadState :pending="pending" :error="error" :rows="6" @retry="refresh" /></div>

        <div v-else style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
            <aside class="panel rise d1 mlist">
                <div class="ph"><span class="eyb"><b>01</b>·<span>Your messages</span></span><span class="sp"></span><span class="mono muted">{{ list.length }}</span></div>
                <p v-if="!list.length" class="tx muted" style="padding: 14px">No messages yet. Write your first one on the right.</p>
                <button v-for="m in list" :key="m.id" type="button" :class="'ml' + (sel === m.id ? ' on' : '')" :aria-current="sel === m.id" @click="load(m)">
                    <span style="display: flex; justify-content: space-between; align-items: center; gap: 8px"><span class="ts" style="font-weight: 600; min-width: 0">{{ m.name }}</span><span :class="ST[m.status]?.[0] || 'st'" style="font-size: 12px">{{ ST[m.status]?.[1] || m.status }}</span></span>
                    <span class="mono muted" style="font-size: 11px">{{ m.channel === 'sms' ? 'SMS' : 'WHATSAPP' }} · {{ whenLabel(m) }}</span>
                    <span class="tx muted">{{ metaLabel(m) }}</span>
                </button>
            </aside>

            <section style="flex: 2 1 460px; min-width: 0; display: flex; flex-direction: column; gap: 16px">
                <!-- Sent or scheduled: read-only with numbers -->
                <div v-if="readOnly && selected" class="panel rise d2">
                    <div class="ph"><span class="eyb"><b>02</b>·<span>{{ selected.name }}</span></span><span class="sp"></span><span :class="ST[selected.status]?.[0]">{{ ST[selected.status]?.[1] }}</span></div>
                    <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
                        <p class="bubtxt" :lang="selected.language || 'en'">{{ selected.body }}</p>
                        <div class="strip" style="grid-template-columns: repeat(auto-fit, minmax(min(110px, 100%), 1fr))">
                            <div><span class="lm">Sent to</span><span class="num" style="font-size: 22px">{{ num(selected.recipients) }}</span></div>
                            <div><span class="lm">Delivered</span><span class="num" style="font-size: 22px">{{ selected.status === 'sent' ? num(selected.delivered) : '—' }}</span></div>
                            <div><span class="lm">Read</span><span class="num" style="font-size: 22px">{{ selected.status === 'sent' ? num(selected.read) : '—' }}</span></div>
                            <div><span class="lm">Replies</span><span class="num" style="font-size: 22px">{{ selected.status === 'sent' ? num(selected.replies) : '—' }}</span></div>
                        </div>
                        <div class="receipt" style="border-top: 1px dashed #cbd5e1; padding-top: 6px">
                            <div class="ln"><span class="muted">channel</span><span>{{ selected.channel === 'sms' ? 'SMS' : 'WhatsApp' }} · {{ LANG_LABEL[selected.language || 'en'] || selected.language }}</span></div>
                            <div class="ln"><span class="muted">who</span><span>{{ audienceLabel(selected) }}</span></div>
                            <div class="ln"><span class="muted">{{ selected.sent_at ? 'sent' : 'goes out' }}</span><span>{{ pvTime || '—' }}</span></div>
                            <div class="ln"><span class="muted">paid from wallet</span><span>{{ inr(selected.cost_paise, { paise: true }) }}</span></div>
                        </div>
                        <p class="tx muted">{{ selected.status === 'sent' ? 'Sent messages can’t be changed.' : 'Already paid and scheduled, so it can’t be edited.' }} <button type="button" class="btn bg sm" @click="newMessage">Write a new one</button></p>
                    </div>
                </div>

                <!-- Draft or new: the composer -->
                <div v-else class="panel rise d2">
                    <div class="ph">
                        <span class="eyb"><b>02</b>·<span>{{ sel ? 'Edit draft' : 'Write' }}</span></span><span class="sp"></span>
                        <div class="seg" role="group" aria-label="Channel">
                            <button v-for="c in CHANS" :key="c[0]" type="button" :class="form.channel === c[0] ? 'on' : ''" :aria-pressed="form.channel === c[0]" @click="form.channel = c[0]">{{ c[1] }}</button>
                        </div>
                    </div>
                    <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
                        <div class="fld">
                            <label class="lab" for="mn">Name <span class="muted" style="font-weight: 400">· only you see it</span></label>
                            <input id="mn" v-model="form.name" class="in" maxlength="80" placeholder="Weekend offer" />
                            <span v-if="errs.name" class="err">{{ errs.name }}</span>
                        </div>
                        <div v-if="langs.length > 1" class="seg" style="align-self: flex-start" role="group" aria-label="Language">
                            <button v-for="l in langs" :key="l" type="button" :class="form.language === l ? 'on' : ''" @click="form.language = l">{{ LANG_LABEL[l] || l }}</button>
                        </div>
                        <div class="fld">
                            <label class="lab" for="mt">Message</label>
                            <textarea
                                id="mt"
                                v-model="form.body"
                                class="in"
                                maxlength="1024"
                                style="height: 120px; padding: 10px 12px; resize: vertical; line-height: 1.5"
                                :lang="form.language"
                                placeholder="Namaste {name} ji! Is hafte {shop} mein…"
                            ></textarea>
                            <span v-if="errs.body" class="err">{{ errs.body }}</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center">
                                <span class="tx muted">Insert</span>
                                <button v-for="t in TOKENS" :key="t" type="button" class="chip" style="height: 26px; font-family: var(--fm); font-size: 11.5px" @click="addToken(t)">{{ t }}</button>
                                <span class="sp"></span>
                                <span class="mono muted" style="font-size: 11px">{{ counter }}</span>
                            </div>
                            <span class="tx muted">{name} becomes each customer’s first name; {shop} becomes “{{ shop?.name }}”.</span>
                        </div>
                        <div v-if="!isSms" class="receipt" style="border-top: 1px dashed #cbd5e1; padding-top: 6px">
                            <div class="ln"><span class="muted">buttons</span><span>{{ BUTTONS.join(' · ') }}</span></div>
                            <div class="ln"><span class="muted">goes to</span><span>only people who said yes</span></div>
                        </div>
                        <div v-else class="receipt" style="border-top: 1px dashed #cbd5e1; padding-top: 6px">
                            <div class="ln"><span class="muted">encoding</span><span>{{ dev ? 'Unicode · 70 chars per SMS' : 'Plain text · 160 chars per SMS' }}</span></div>
                            <div class="ln"><span class="muted">goes to</span><span>everyone in the group with a number</span></div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="rise d3" style="flex: 1.2 1 300px; min-width: 0; display: flex; flex-direction: column; gap: 16px">
                <section class="panel pb" style="display: flex; flex-direction: column; gap: 12px">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px">
                        <span class="lm">Preview{{ previewNames.length > 1 ? ' as' : '' }}</span><span class="sp"></span>
                        <div v-if="previewNames.length > 1" class="seg"><button v-for="(p, i) in previewNames" :key="p + i" type="button" :class="who === i ? 'on' : ''" @click="who = i">{{ p }}</button></div>
                    </div>
                    <div class="phone">
                        <div class="scr2">
                            <div style="display: flex; gap: 8px; align-items: center; padding: 9px 12px; background: #fff; border-bottom: 1px solid #e2e8f0">
                                <span class="mt sm">{{ shop?.initials }}</span><span class="ts" style="font-weight: 600; min-width: 0">{{ pvChannel === 'sms' ? (shop?.short_name || shop?.name) + ' · SMS' : shop?.name }}</span>
                            </div>
                            <div style="padding: 12px; display: flex; flex-direction: column; gap: 8px">
                                <span v-if="pvTime" class="mono" style="align-self: center; font-size: 10px; color: #64748b">{{ pvTime }}</span>
                                <p v-if="!filled.trim()" class="tx muted" style="align-self: center; padding: 30px 0">Your message shows here.</p>
                                <div v-else-if="pvChannel !== 'sms'" class="msg">
                                    <p style="padding: 8px 10px; white-space: pre-wrap" :lang="form.language">{{ filled }}</p>
                                    <span v-for="b in pvButtons" :key="b" class="mbtn">{{ b }}</span>
                                </div>
                                <div v-else class="msg" style="background: #e2e8f0; border: 0; padding: 9px 11px; white-space: pre-wrap" :lang="form.language">{{ filled }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-if="!readOnly" class="panel">
                    <div class="ph"><span class="eyb"><b>03</b>·<span>Who gets it</span></span><span class="sp"></span><span class="mono">{{ num(n) }} people</span></div>
                    <div class="pb" style="display: flex; flex-direction: column; gap: 12px">
                        <div class="chips">
                            <button v-for="a in audiences" :key="a.key" type="button" :class="'chip' + (form.stage === a.key ? ' on' : '')" :aria-pressed="form.stage === a.key" @click="form.stage = a.key">
                                {{ a.label }} <span class="n">{{ num(a[form.channel]) }}</span>
                            </button>
                        </div>
                        <span v-if="leftOut" class="tx muted">Left out automatically: {{ num(leftOut) }} who haven’t said yes to WhatsApp offers.</span>
                        <div class="seg full" role="group" aria-label="When">
                            <button type="button" :class="when === 'now' ? 'on' : ''" @click="when = 'now'">Now</button>
                            <button type="button" :class="when === 'later' ? 'on' : ''" @click="when = 'later'">Pick a time</button>
                        </div>
                        <div v-if="when === 'later'" class="fld">
                            <input v-model="at" class="in" type="datetime-local" :min="minAt" aria-label="Send at" />
                            <span v-if="errs.scheduled_at" class="err">{{ errs.scheduled_at }}</span>
                        </div>
                        <div class="receipt" style="border-top: 1px dashed #cbd5e1; padding-top: 4px">
                            <div class="ln"><span class="muted">{{ num(n) }} × {{ inr(rate, { paise: true }) }} {{ isSms ? 'SMS' : 'WhatsApp' }}</span><span>{{ inr(cost, { paise: true }) }}</span></div>
                            <div class="ln"><span class="muted">wallet after</span><span :style="wallet < cost ? 'color: #B91C1C' : ''">{{ inr(wallet - cost, { paise: true }) }}</span></div>
                        </div>
                        <div style="display: flex; gap: 8px">
                            <button type="button" class="btn bs" :disabled="busy !== null || (!!sel && !dirty)" @click="onSave">{{ busy === 'save' ? 'Saving…' : sel && !dirty ? 'Saved' : 'Save draft' }}</button>
                            <button type="button" class="btn bp lg" style="flex: 1" :disabled="busy !== null || !n || !form.body.trim()" @click="send">
                                {{ busy === 'send' ? 'Working…' : (when === 'now' ? 'Send now to ' : 'Schedule for ') + num(n) + (n === 1 ? ' person' : ' people') }}
                            </button>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.mlist {
    flex: 1 1 250px;
    max-width: 290px;
    min-width: 0;
    overflow: hidden;
}
@media (max-width: 899px) {
    .mlist {
        max-width: none;
        flex-basis: 100%;
    }
}
.ml {
    display: flex;
    flex-direction: column;
    gap: 4px;
    width: 100%;
    padding: 12px 14px;
    border: 0;
    border-bottom: 1px solid #e2e8f0;
    background: none;
    text-align: left;
    cursor: pointer;
    transition: background 0.15s;
}
.ml:last-child {
    border-bottom: 0;
}
.ml:hover {
    background: #f8fafc;
}
.ml.on {
    background: #f1f5f9;
}
.bubtxt {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px 14px 14px 14px;
    padding: 10px 12px;
    font-size: 13.5px;
    line-height: 20px;
    white-space: pre-wrap;
}
.phone {
    width: 100%;
    max-width: 300px;
    border: 1px solid #cbd5e1;
    border-radius: 26px;
    background: #fff;
    padding: 9px;
    align-self: center;
}
.scr2 {
    border-radius: 19px;
    background: #f1f5f9;
    overflow: hidden;
    min-height: 300px;
    display: flex;
    flex-direction: column;
}
.msg {
    align-self: flex-start;
    max-width: 90%;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 4px 14px 14px 14px;
    overflow: hidden;
    font-size: 13px;
    line-height: 19px;
    animation: fade 0.3s ease both;
    overflow-wrap: anywhere;
}
.mbtn {
    display: block;
    text-align: center;
    padding: 7px;
    border-top: 1px solid #e2e8f0;
    font-weight: 500;
    font-size: 12.5px;
    color: #1d4ed8;
}
</style>
