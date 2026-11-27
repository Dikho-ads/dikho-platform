<script setup lang="ts">
// Workflows: GET workflows. Turn each on or off (PATCH {active}, optimistic with rollback), see
// its steps as a flow, edit step timing, channel and text (PATCH {steps}). Placeholders are shown
// with the shop's real name in previews.
definePageMeta({ layout: 'app', title: 'Workflows' });

const api = useApi();
const auth = useAuth();
const toast = useToast();

type Step = { after: string; action: 'whatsapp' | 'sms'; text: string };
type Workflow = { id: number; name: string; trigger: string; description: string | null; steps: Step[]; active: boolean; sent_count: number; reply_count: number; last_run_at: string | null };

const { data, pending, error, refresh } = await useApiData<{ data: Workflow[] }>('workflows', 'workflows');
const list = computed(() => data.value?.data ?? []);
const live = computed(() => list.value.filter((w) => w.active).length);
const put = (w: Workflow) => {
    if (data.value) data.value = { data: list.value.map((x) => (x.id === w.id ? w : x)) };
};

// Per-message rates the wallet pays (API.md: WhatsApp 86 paise, SMS 25 paise).
const RATES: Record<string, number> = { whatsapp: 86, sms: 25 };
const K: Record<string, [string, string]> = { TRIGGER: ['#0F172A', '#fff'], WAIT: ['#F1F5F9', '#334155'], SEND: ['#FEF3DC', '#8A5300'], END: ['#F1F5F9', '#64748B'] };
const TRIGGERS: Record<string, string> = {
    new_customer: 'A new customer is added',
    birthday: 'It’s a customer’s birthday',
    lapsed: 'A regular stops coming',
    after_purchase: 'Someone buys from you',
    missed_call: 'You miss a call',
};
const HELP: Record<string, string> = {
    TRIGGER: 'What starts this workflow. It watches your customer book and calls — you don’t have to do anything.',
    WAIT: 'A pause, counted from the moment the workflow starts, so the message arrives at a natural time.',
    END: 'Where the workflow stops. People who never said yes to WhatsApp are always skipped.',
};
const UNITS: Record<string, [string, string, number]> = { m: ['minute', 'minutes', 1], h: ['hour', 'hours', 60], d: ['day', 'days', 1440] };

function parseAfter(a: string) {
    const m = /^(\d+)\s*([mhd])$/.exec(a || '');
    return m ? { n: Number(m[1]), u: m[2] } : { n: 0, u: 'd' };
}
function afterLabel(a: string) {
    const { n, u } = parseAfter(a);
    if (!n) return 'right away';
    return `${n} ${n === 1 ? UNITS[u][0] : UNITS[u][1]} later`;
}

// {shop} → the shop's name; {name} and {review_link} stay visible as placeholders.
type Seg = { t: string; tok?: boolean };
function segments(text: string): Seg[] {
    const shopName = auth.shop?.name ?? '';
    return (text || '').split(/(\{\w+\})/).filter(Boolean).map((p) => {
        if (p === '{shop}') return { t: shopName };
        if (p === '{name}') return { t: 'name', tok: true };
        if (p === '{review_link}') return { t: 'review link', tok: true };
        return { t: p };
    });
}

// ---- selection ----
const sel = ref(list.value[0]?.id ?? null);
const wf = computed(() => list.value.find((w) => w.id === sel.value) ?? list.value[0] ?? null);
const draft = ref<Step[]>([]);
const saved = ref('');
function resetDraft() {
    draft.value = (wf.value?.steps ?? []).map((s) => ({ ...s }));
    saved.value = JSON.stringify(draft.value);
}
watch(() => wf.value?.id, resetDraft, { immediate: true });
const dirty = computed(() => JSON.stringify(draft.value) !== saved.value);
const node = ref(0);

function pickWorkflow(id: number) {
    if (dirty.value && !window.confirm('Leave without saving your changes to this workflow?')) return;
    sel.value = id;
    node.value = 0;
}

type Node = { kind: 'TRIGGER' | 'WAIT' | 'SEND' | 'END'; t: string; d: string; segs?: Seg[]; stat: string; step?: number };
const nodes = computed<Node[]>(() => {
    const w = wf.value;
    if (!w) return [];
    const out: Node[] = [{ kind: 'TRIGGER', t: TRIGGERS[w.trigger] || w.trigger.replace(/_/g, ' '), d: w.description || '', stat: '' }];
    draft.value.forEach((s, i) => {
        const { n } = parseAfter(s.after);
        if (n > 0) out.push({ kind: 'WAIT', t: afterLabel(s.after).replace(' later', ''), d: 'counted from the start', stat: '', step: i });
        out.push({ kind: 'SEND', t: s.action === 'sms' ? 'SMS' : 'WhatsApp message', d: '', segs: segments(s.text), stat: `step ${i + 1}`, step: i });
    });
    out.push({ kind: 'END', t: 'Done', d: `${num(w.sent_count)} sent · ${num(w.reply_count)} replied`, stat: w.last_run_at ? 'ran ' + relative(w.last_run_at) : '' });
    return out;
});
const ni = computed(() => Math.min(node.value, Math.max(0, nodes.value.length - 1)));
const nd = computed(() => nodes.value[ni.value]);
const step = computed(() => (nd.value?.kind === 'SEND' && nd.value.step != null ? draft.value[nd.value.step] : null));
const stepAfter = computed({
    get: () => (step.value ? parseAfter(step.value.after) : { n: 0, u: 'd' }),
    set: (v) => step.value && (step.value.after = `${Math.max(0, Math.min(999, Math.round(v.n) || 0))}${v.u}`),
});

// ---- on / off ----
const toggling = ref<number | null>(null);
async function toggle(w: Workflow) {
    const next = !w.active;
    put({ ...w, active: next });
    if (auth.account?.counts) auth.account.counts.workflows_active += next ? 1 : -1;
    toggling.value = w.id;
    try {
        const res = await api<{ data: Workflow }>(`workflows/${w.id}`, { method: 'PATCH', body: { active: next } });
        put({ ...w, ...res.data });
        toast.ok(w.name + (next ? ' is live' : ' paused'));
    } catch (e: any) {
        put({ ...w, active: !next });
        if (auth.account?.counts) auth.account.counts.workflows_active += next ? -1 : 1;
        toast.error(e.message);
    } finally {
        toggling.value = null;
    }
}

// ---- step editing ----
const errs = ref<Record<string, string | undefined>>({});
const busy = ref(false);
function addToken(t: string) {
    if (!step.value) return;
    const el = document.getElementById('wt') as HTMLTextAreaElement | null;
    const a = el?.selectionStart ?? step.value.text.length;
    const b = el?.selectionEnd ?? a;
    step.value.text = step.value.text.slice(0, a) + t + step.value.text.slice(b);
    nextTick(() => {
        el?.focus();
        el?.setSelectionRange(a + t.length, a + t.length);
    });
}
function addStep() {
    if (draft.value.length >= 6) return toast.info('A workflow can have up to 6 messages.');
    const last = draft.value[draft.value.length - 1];
    const prev = last ? parseAfter(last.after) : { n: 0, u: 'd' };
    const minutes = prev.n * UNITS[prev.u][2] + 1440;
    draft.value.push({ after: `${Math.round(minutes / 1440)}d`, action: 'whatsapp', text: '' });
    nextTick(() => {
        node.value = nodes.value.length - 2;
        document.getElementById('wt')?.focus();
    });
}
function removeStep() {
    if (!nd.value || nd.value.step == null) return;
    if (draft.value.length <= 1) return toast.info('Keep at least one message, or turn the workflow off instead.');
    draft.value.splice(nd.value.step, 1);
    node.value = 0;
}
async function saveSteps() {
    const w = wf.value;
    if (!w) return;
    errs.value = {};
    const empty = draft.value.findIndex((s) => !s.text.trim());
    if (empty >= 0) {
        node.value = nodes.value.findIndex((n) => n.kind === 'SEND' && n.step === empty);
        errs.value = { text: 'Write this message or remove the step.' };
        return;
    }
    busy.value = true;
    try {
        const steps = draft.value.map((s) => ({ ...s, text: s.text.trim() }));
        const res = await api<{ data: Workflow }>(`workflows/${w.id}`, { method: 'PATCH', body: { steps } });
        put({ ...w, ...res.data });
        resetDraft();
        toast.ok(`${w.name} saved`);
    } catch (e: any) {
        const k = Object.keys(e.errors || {})[0];
        if (k) errs.value = { text: e.field(k) };
        else toast.error(e.message);
    } finally {
        busy.value = false;
    }
}

const stats = computed(() => {
    const w = wf.value;
    if (!w) return [];
    return [
        { l: 'Sent', v: num(w.sent_count) },
        { l: 'Replied', v: num(w.reply_count) },
        { l: 'Reply rate', v: w.sent_count ? Math.round((w.reply_count / w.sent_count) * 100) + '%' : '—' },
    ];
});
const statLine = (w: Workflow) => (w.sent_count ? `${num(w.sent_count)} sent · ${num(w.reply_count)} replied` : 'Nothing sent yet') + (w.last_run_at ? ' · ' + relative(w.last_run_at) : '');
</script>

<template>
    <div class="wrap" style="max-width: 1400px">
        <div class="rise" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px">
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1 1 420px">
                <span class="eyb"><b>WORKFLOWS</b>·<span>APNE AAP CHALNE WALE KAAM · {{ live }} LIVE</span></span>
                <h1 class="h-lg">Messages that send themselves at the right moment</h1>
                <p class="ts muted">A new customer, a birthday, a missed call — each workflow sends its messages on time. You can change any step.</p>
            </div>
        </div>

        <div v-if="!data" class="panel"><LoadState :pending="pending" :error="error" :rows="6" @retry="refresh" /></div>
        <div v-else-if="!list.length" class="panel"><LoadState empty empty-text="No workflows yet" empty-hint="They are created when the shop setup is finished." /></div>

        <div v-else style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start">
            <aside class="panel rise d1 wlist">
                <div class="ph"><span class="eyb"><b>01</b>·<span>Your workflows</span></span></div>
                <div v-for="x in list" :key="x.id" :class="'rc' + (wf?.id === x.id ? ' on' : '')">
                    <button type="button" style="border: 0; background: none; padding: 0; text-align: left; cursor: pointer; min-width: 0" @click="pickWorkflow(x.id)">
                        <span class="ts" style="font-weight: 600; display: block">{{ x.name }}</span>
                        <span class="tx muted">{{ statLine(x) }}</span>
                    </button>
                    <button
                        type="button"
                        :class="'sw' + (x.active ? ' on' : '')"
                        role="switch"
                        :aria-checked="x.active"
                        :aria-label="'Turn ' + x.name + (x.active ? ' off' : ' on')"
                        :disabled="toggling === x.id"
                        @click="toggle(x)"
                    ></button>
                </div>
            </aside>

            <section v-if="wf" class="panel dots rise d2" style="flex: 2 1 520px; min-width: 0; background-color: #f8fafc; overflow: hidden">
                <div class="ph" style="background: #fff">
                    <span class="eyb"><b>02</b>·<span>{{ wf.name }}</span></span><span :class="wf.active ? 'st ok live' : 'st'">{{ wf.active ? 'Live' : 'Off' }}</span><span class="sp"></span>
                    <template v-if="dirty">
                        <button type="button" class="btn bg sm" :disabled="busy" @click="resetDraft(); errs = {}">Undo</button>
                        <button type="button" class="btn bp sm" :disabled="busy" @click="saveSteps">{{ busy ? 'Saving…' : 'Save changes' }}</button>
                    </template>
                </div>
                <div style="padding: 24px 18px 28px; display: flex; flex-direction: column; align-items: center">
                    <div v-for="(n, i) in nodes" :key="wf.id + '-' + i + n.kind" style="display: flex; flex-direction: column; align-items: center; width: 100%">
                        <div v-if="i > 0" class="lk"></div>
                        <button type="button" :class="'nd' + (ni === i ? ' on' : '')" :style="{ animationDelay: i * 0.06 + 's' }" @click="node = i">
                            <span class="kind" :style="{ background: K[n.kind][0], color: K[n.kind][1] }">{{ n.kind }}</span>
                            <span style="min-width: 0">
                                <span class="ts" style="font-weight: 600; display: block">{{ n.t }}</span>
                                <span v-if="n.segs" class="tx muted clamp"><template v-for="(s, si) in n.segs" :key="si"><span v-if="s.tok" class="tok">{{ s.t }}</span><template v-else>{{ s.t }}</template></template><em v-if="!n.segs.length">Empty — write this message</em></span>
                                <span v-else class="tx muted">{{ n.d }}</span>
                            </span>
                            <span class="mono muted" style="font-size: 11px; text-align: right">{{ n.stat }}</span>
                        </button>
                    </div>
                    <template v-if="draft.length < 6">
                        <div class="lk"></div>
                        <button type="button" class="add" aria-label="Add a message step" title="Add a message step" @click="addStep">+</button>
                    </template>
                </div>
            </section>

            <aside v-if="wf && nd" class="panel rise d3" style="flex: 1.15 1 300px; min-width: 0">
                <div class="ph"><span class="eyb"><b>03</b>·<span>{{ nd.kind }} step</span></span></div>
                <div class="pb" style="display: flex; flex-direction: column; gap: 14px">
                    <p class="h-md">{{ nd.kind === 'SEND' ? 'Message ' + ((nd.step ?? 0) + 1) : nd.t }}</p>
                    <div v-if="step" style="display: flex; flex-direction: column; gap: 12px">
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center">
                            <div class="seg" role="group" aria-label="Channel">
                                <button type="button" :class="step.action === 'whatsapp' ? 'on' : ''" @click="step.action = 'whatsapp'">WhatsApp</button>
                                <button type="button" :class="step.action === 'sms' ? 'on' : ''" @click="step.action = 'sms'">SMS</button>
                            </div>
                            <span class="sp"></span>
                            <label class="tx muted" style="display: inline-flex; gap: 6px; align-items: center">
                                after
                                <input class="in" type="number" min="0" max="999" style="width: 64px; height: 32px" :value="stepAfter.n" aria-label="Wait" @input="stepAfter = { n: Number(($event.target as HTMLInputElement).value), u: stepAfter.u }" />
                                <select class="in" style="width: auto; height: 32px" :value="stepAfter.u" aria-label="Unit" @change="stepAfter = { n: stepAfter.n, u: ($event.target as HTMLSelectElement).value }">
                                    <option v-for="(u, k) in UNITS" :key="k" :value="k">{{ u[1] }}</option>
                                </select>
                            </label>
                        </div>
                        <textarea id="wt" v-model="step.text" class="in" maxlength="500" style="height: 110px; padding: 10px 12px; resize: vertical; line-height: 1.5" aria-label="Message"></textarea>
                        <span v-if="errs.text" class="err">{{ errs.text }}</span>
                        <div class="chips">
                            <span class="tx muted">Insert</span>
                            <button v-for="t in ['{name}', '{shop}']" :key="t" type="button" class="chip" style="height: 26px; font-family: var(--fm); font-size: 11.5px" @click="addToken(t)">{{ t }}</button>
                        </div>
                        <div v-if="step.text.trim()" class="bub">
                            <template v-for="(s, si) in segments(step.text)" :key="si"><span v-if="s.tok" class="tok">{{ s.t }}</span><template v-else>{{ s.t }}</template></template>
                        </div>
                        <div class="receipt" style="border-top: 1px dashed #cbd5e1; padding-top: 4px">
                            <div class="ln"><span class="muted">channel</span><span>{{ step.action === 'sms' ? 'SMS' : 'WhatsApp · only people who said yes' }}</span></div>
                            <div class="ln"><span class="muted">timing</span><span>{{ afterLabel(step.after) }} than the start</span></div>
                            <div class="ln"><span class="muted">cost</span><span>{{ inr(RATES[step.action], { paise: true }) }} per message, from the wallet</span></div>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap">
                            <button type="button" class="btn bp" :disabled="!dirty || busy" @click="saveSteps">{{ busy ? 'Saving…' : dirty ? 'Save changes' : 'Saved' }}</button>
                            <button type="button" class="btn bg" :disabled="busy" @click="removeStep">Remove step</button>
                        </div>
                    </div>
                    <template v-else>
                        <p class="ts muted">{{ nd.kind === 'TRIGGER' && nd.d ? nd.d : HELP[nd.kind] || '' }}</p>
                        <p v-if="nd.kind === 'TRIGGER'" class="tx muted">{{ HELP.TRIGGER }}</p>
                        <p v-if="nd.kind === 'WAIT'" class="tx muted">Change the timing on the message after this pause.</p>
                        <p v-if="nd.kind === 'END' && wf.last_run_at" class="tx muted">Last ran {{ relative(wf.last_run_at) }} · {{ eyebrowDate(wf.last_run_at) }} {{ time(wf.last_run_at) }}</p>
                    </template>
                    <div class="strip" style="grid-template-columns: repeat(3, minmax(0, 1fr))">
                        <div v-for="k in stats" :key="k.l" style="padding: 10px 12px; gap: 4px"><span class="lm">{{ k.l }}</span><span class="num" style="font-size: 20px">{{ k.v }}</span></div>
                    </div>
                    <span class="tx muted">Numbers are for the whole workflow.</span>
                </div>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.wlist {
    flex: 1 1 260px;
    max-width: 300px;
    min-width: 0;
    overflow: hidden;
}
@media (max-width: 899px) {
    .wlist {
        max-width: none;
        flex-basis: 100%;
    }
}
.rc {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 10px;
    align-items: center;
    width: 100%;
    padding: 12px 14px;
    border: 0;
    border-bottom: 1px solid #e2e8f0;
    background: none;
    text-align: left;
    transition: background 0.15s;
}
.rc:last-child {
    border-bottom: 0;
}
.rc:hover {
    background: #f8fafc;
}
.rc.on {
    background: #f1f5f9;
}
.nd {
    position: relative;
    display: grid;
    grid-template-columns: 72px minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
    width: min(560px, 100%);
    padding: 12px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.2s var(--ease);
    animation: rise 0.5s var(--ease) both;
}
.nd:hover {
    transform: translateY(-1px);
    border-color: #94a3b8;
}
.nd.on {
    border-color: #0f172a;
    box-shadow: inset 0 0 0 1px #0f172a;
}
.lk {
    width: 1.5px;
    height: 26px;
    background: #cbd5e1;
    position: relative;
}
.lk::after {
    content: '';
    position: absolute;
    left: -3px;
    bottom: -1px;
    width: 7.5px;
    height: 7.5px;
    border-right: 1.5px solid #cbd5e1;
    border-bottom: 1.5px solid #cbd5e1;
    transform: rotate(45deg);
}
.kind {
    font: 500 10.5px/1 var(--fm);
    letter-spacing: 0.06em;
    padding: 5px 6px;
    border-radius: 5px;
    text-align: center;
}
.add {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 1px dashed #94a3b8;
    background: #fff;
    color: #64748b;
    display: grid;
    place-items: center;
    font-size: 13px;
    line-height: 1;
    cursor: pointer;
    margin: 2px 0;
}
.clamp {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.tok {
    display: inline-block;
    padding: 0 5px;
    border-radius: 4px;
    background: #fef3dc;
    color: #8a5300;
    font: 500 0.92em/1.4 var(--fm);
}
.bub {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px 14px 14px 14px;
    padding: 10px 12px;
    font-size: 13.5px;
    line-height: 20px;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}
@media (max-width: 480px) {
    .nd {
        grid-template-columns: 60px minmax(0, 1fr);
    }
    .nd > .mono {
        display: none;
    }
}
</style>
