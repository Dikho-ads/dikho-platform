<script setup lang="ts">
// Settings › Plan & credits: GET billing (package, period, credit history), change package
// (POST billing/package), buy a credit pack (POST credits/topup). Credit costs per format come
// from GET studio. No payment gateway is wired yet, so nothing is actually charged.
const emit = defineEmits<{ status: [[string, string]] }>();
const api = useApi();
const auth = useAuth();
const toast = useToast();
const { confirm } = useConfirm();

const { data, pending, error, refresh } = useApiData<any>('billing', 'billing');
const { data: studio, error: studioErr } = useApiData<any>('studio', 'studio', { lazy: true });

const sub = computed(() => data.value?.subscription);
const pkg = computed(() => auth.account?.package);
const credits = computed(() => auth.account?.credits);
const packages = computed<any[]>(() => data.value?.packages ?? []);
const nextPkg = computed(() => packages.value.find((p) => p.id === sub.value?.next_package_id));
const current = computed(() => packages.value.find((p) => p.id === (sub.value?.package_id ?? pkg.value?.id)));
const packs = computed<any[]>(() => data.value?.credit_packs ?? []);
const history = computed<any[]>(() => data.value?.credit_history ?? []);
const formats = computed<any[]>(() => (studio.value?.formats ?? []).slice().sort((a: any, b: any) => a.credits - b.credits));

watch(
    [sub, pkg],
    () => {
        if (pkg.value) emit('status', [sub.value?.status === 'active' || !sub.value ? 'st ok live' : 'st warn', (sub.value?.status === 'active' || !sub.value ? 'Active · ' : (sub.value?.status ?? '') + ' · ') + pkg.value.name]);
    },
    { immediate: true },
);

const ticks = computed(() => {
    const on = Math.round(((credits.value?.percent_left ?? 0) / 100) * 50);
    return Array.from({ length: 50 }, (_, i) => ({ c: i < on ? 'on' : '', d: (0.1 + i * 0.012).toFixed(3) + 's' }));
});

// ---- change package ----
const showPlans = ref(false);
const busy = ref<string | null>(null);
async function pickPlan(p: any) {
    if (!current.value || p.id === current.value.id) return;
    const up = p.price_paise > current.value.price_paise;
    const end = sub.value?.period_end ? day(sub.value.period_end) : 'renewal';
    const ok = await confirm(
        up
            ? {
                  title: `Upgrade to ${p.name} now?`,
                  body: `It starts today and adds ${num(p.credits - current.value.credits)} credits to this month. From ${end} the plan is ${inr(p.price_paise)} a month. No payment gateway is connected in this build, so nothing is charged now.`,
                  cost: `${inr(p.price_paise)} / month · ${num(p.credits)} credits`,
                  confirm: 'Upgrade now',
              }
            : {
                  title: `Move to ${p.name} at renewal?`,
                  body: `You keep ${current.value.name} and your credits until ${end}. After that: ${num(p.credits)} credits a month.`,
                  cost: `${inr(p.price_paise)} / month from ${end}`,
                  confirm: `Switch on ${end}`,
              },
    );
    if (!ok) return;
    busy.value = 'plan';
    try {
        const res = await api<any>('billing/package', { method: 'POST', body: { package_id: p.id } });
        data.value = res;
        auth.set(res.account);
        showPlans.value = false;
        toast.ok(up ? `You are on ${p.name} now · ${num(p.credits - current.value.credits)} credits added` : `${p.name} starts on ${end}`);
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        busy.value = null;
    }
}

// ---- credit packs ----
const packIdx = ref(0);
const pack = computed(() => packs.value[packIdx.value]);
async function buy() {
    const p = pack.value;
    if (!p) return;
    const ok = await confirm({
        title: `Buy ${num(p.credits)} credits?`,
        body: 'Recorded as paid by UPI. No payment gateway is connected in this build, so no money moves.',
        cost: `${inr(p.price_paise)} · ${num(p.credits)} credits · ${credits.value?.left ?? 0} → ${(credits.value?.left ?? 0) + p.credits} left`,
        confirm: `Buy · ${inr(p.price_paise)}`,
    });
    if (!ok) return;
    busy.value = 'pack';
    try {
        const res = await api<any>('credits/topup', { method: 'POST', body: { credits: p.credits } });
        auth.set(res.account);
        toast.ok(`${num(p.credits)} credits added · ${inr(p.price_paise)} paid by UPI`);
        refresh();
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        busy.value = null;
    }
}
</script>

<template>
    <div class="fade">
        <LoadState v-if="!data" :pending="pending" :error="error" :rows="6" @retry="refresh" />
        <template v-else>
            <div class="srow">
                <span style="display: flex; gap: 12px; align-items: center; min-width: 0">
                    <span class="mt">{{ (pkg?.name || '').slice(0, 2).toUpperCase() }}</span>
                    <span style="min-width: 0">
                        <span class="h-sm" style="display: block">{{ pkg?.name }} · {{ pkg?.price_paise ? inr(pkg.price_paise) + ' / month' : 'free' }}</span>
                        <span class="tx muted">
                            {{ num(pkg?.credits) }} credits a month<template v-if="sub"> · this period {{ day(sub.period_start) }} – {{ day(sub.period_end) }} · {{ sub.autopay ? 'renews by UPI AutoPay' : 'renews ' + day(sub.period_end) }}</template>
                        </span>
                        <span v-if="nextPkg" class="tx" style="display: block; color: #8a5300">Moves to {{ nextPkg.name }} on {{ day(sub.period_end) }}</span>
                    </span>
                </span>
                <button type="button" class="btn bs sm" :aria-expanded="showPlans" @click="showPlans = !showPlans">Change plan</button>
            </div>
            <div v-if="showPlans" class="srow fade" style="grid-template-columns: minmax(0, 1fr)">
                <div class="plans">
                    <button
                        v-for="p in packages"
                        :key="p.id"
                        type="button"
                        :class="'plan' + (p.id === current?.id ? ' on' : '')"
                        :disabled="p.id === current?.id || busy === 'plan'"
                        @click="pickPlan(p)"
                    >
                        <span style="display: flex; justify-content: space-between; gap: 8px"><b>{{ p.name }}</b><span class="mono">{{ p.price_paise ? inr(p.price_paise) : 'Free' }}</span></span>
                        <span class="tx muted">{{ p.blurb }} · {{ num(p.credits) }} credits</span>
                        <span class="tx" style="font-weight: 500">{{ p.id === current?.id ? 'Your plan' : p.price_paise > (current?.price_paise ?? 0) ? 'Upgrade now' : 'Switch at renewal' }}</span>
                    </button>
                </div>
                <span class="tx muted">Upgrades start today and add the extra credits now. Moving to a smaller plan starts at renewal{{ sub ? ' on ' + day(sub.period_end) : '' }}.</span>
            </div>
            <div class="srow">
                <span style="display: flex; flex-direction: column; gap: 10px; min-width: 0">
                    <span><span class="h-sm">{{ num(credits?.left) }} credits left</span><span class="tx muted"> of {{ num(credits?.total) }} · resets {{ credits ? day(credits.resets_on) : '' }}</span></span>
                    <span class="ticks" style="max-width: 560px"><i v-for="(k, i) in ticks" :key="i" :class="k.c" :style="{ animationDelay: k.d }"></i></span>
                </span>
                <span class="mono muted">{{ num(credits?.used) }} used</span>
            </div>
            <div class="srow">
                <span>
                    <span class="h-sm" style="display: block">Top up credits</span>
                    <span class="tx muted">Extra credits for this month{{ pack ? ` · ${num(pack.credits)} for ${inr(pack.price_paise)}` : '' }}</span>
                </span>
                <span style="display: inline-flex; flex-wrap: wrap; gap: 8px; align-items: center">
                    <span class="seg" role="group" aria-label="Credit packs">
                        <button v-for="(p, i) in packs" :key="p.credits" type="button" :class="packIdx === i ? 'on' : ''" @click="packIdx = i">{{ p.credits }} cr</button>
                    </span>
                    <button type="button" class="btn bp sm" :disabled="!pack || busy === 'pack'" @click="buy">{{ busy === 'pack' ? 'Buying…' : `Buy · ${pack ? inr(pack.price_paise) : ''}` }}</button>
                </span>
            </div>
            <div class="srow" style="grid-template-columns: minmax(0, 1fr); background: #fffbeb">
                <span class="tx" style="color: #8a5300">No payment gateway is connected in this build yet. Top-ups and plan changes are recorded as UPI payments for testing; no money moves.</span>
            </div>
            <div style="padding: 16px 22px 22px; display: flex; flex-direction: column; gap: 10px">
                <span class="lm">What each thing costs · any mix, same price</span>
                <LoadState v-if="!studio && !studioErr" pending :rows="3" />
                <p v-else-if="studioErr" class="tx muted">Could not load the price list.</p>
                <div v-else style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(260px, 100%), 1fr)); column-gap: 28px" class="receipt">
                    <div v-for="f in formats" :key="f.type" class="ln" style="border-bottom: 1px dashed #e2e8f0"><span>{{ f.label }}</span><span>{{ f.credits ? f.credits + ' cr' : 'free' }}</span></div>
                </div>
                <p class="tx muted">Ad spend and WhatsApp fees never use credits — they come from the wallet at cost.</p>
            </div>
            <div style="border-top: 1px solid #e2e8f0">
                <div style="padding: 14px 22px 6px"><span class="lm">Credit history</span></div>
                <p v-if="!history.length" class="tx muted" style="padding: 0 22px 18px">No credits used or added yet.</p>
                <div v-else class="tscroll">
                    <table class="tbl">
                        <thead>
                            <tr><th>Date</th><th>What</th><th style="text-align: right">Credits</th><th style="text-align: right">Left after</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="h in history" :key="h.id" style="cursor: default">
                                <td class="mono" style="white-space: nowrap">{{ day(h.created_at).toUpperCase() }}</td>
                                <td>{{ h.description }}</td>
                                <td class="mono" style="text-align: right; white-space: nowrap">{{ h.delta > 0 ? '+ ' + h.delta : '− ' + Math.abs(h.delta) }}</td>
                                <td class="mono muted" style="text-align: right">{{ num(h.balance_after) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.plans {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(200px, 100%), 1fr));
    gap: 10px;
}
.plan {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    text-align: left;
    cursor: pointer;
    font: inherit;
    color: inherit;
    transition: border-color 0.15s, transform 0.2s var(--ease);
}
.plan:hover:not(:disabled) {
    border-color: #0f172a;
    transform: translateY(-1px);
}
.plan.on {
    border-color: #0f172a;
    box-shadow: inset 0 0 0 1px #0f172a;
    cursor: default;
}
.plan:disabled:not(.on) {
    opacity: 0.6;
}
</style>
