<script setup lang="ts">
// Settings › Wallet: balance from the account, history from GET billing, add money with
// POST wallet/topup {amount_paise}. Recorded as a UPI payment; no gateway is wired yet.
const emit = defineEmits<{ status: [[string, string]] }>();
const api = useApi();
const auth = useAuth();
const toast = useToast();
const { confirm } = useConfirm();

const { data, pending, error, refresh } = useApiData<any>('billing', 'billing');
const wallet = computed(() => auth.account?.wallet_paise ?? 0);
const history = computed<any[]>(() => data.value?.wallet_history ?? []);
watch(wallet, (w) => emit('status', ['st ok', 'Balance ' + inr(w, { paise: w % 100 !== 0 })]), { immediate: true });

const REASON: Record<string, string> = { topup: 'Added by UPI', meta_ad: 'Meta ads', whatsapp: 'WhatsApp messages', sms: 'SMS', refund: 'Refund' };
const AMTS = [500, 1000, 2000];
const amount = ref<number | ''>(1000);
const fieldErr = ref('');
const busy = ref(false);

async function addMoney() {
    fieldErr.value = '';
    const rupees = Number(amount.value);
    if (!rupees || rupees < 100) return (fieldErr.value = 'The smallest top-up is ₹100.');
    if (rupees > 50000) return (fieldErr.value = 'The largest top-up is ₹50,000 at a time.');
    const paise = Math.round(rupees * 100);
    const ok = await confirm({
        title: `Add ${inr(paise)} to the wallet?`,
        body: 'Recorded as paid by UPI. No payment gateway is connected in this build, so no money moves.',
        cost: `${inr(wallet.value, { paise: true })} → ${inr(wallet.value + paise, { paise: true })}`,
        confirm: `Add ${inr(paise)}`,
    });
    if (!ok) return;
    busy.value = true;
    try {
        const res = await api<any>('wallet/topup', { method: 'POST', body: { amount_paise: paise } });
        auth.set(res.account);
        toast.ok(`${inr(paise)} added to the wallet · paid by UPI`);
        refresh();
    } catch (e: any) {
        if (e.field('amount_paise')) fieldErr.value = e.field('amount_paise');
        else toast.error(e.message);
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="fade">
        <div class="srow">
            <span style="min-width: 0">
                <span class="lm">Wallet balance</span>
                <span style="display: block; font: 600 40px/1.1 var(--f); letter-spacing: -0.04em; margin-top: 6px">{{ inr(wallet, { paise: wallet % 100 !== 0 }) }}</span>
                <span class="tx muted">Only for Meta ad spend and WhatsApp and SMS fees, passed through at cost.</span>
            </span>
            <form style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end" novalidate @submit.prevent="addMoney">
                <span style="display: inline-flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: flex-end">
                    <span class="seg" role="group" aria-label="Amount">
                        <button v-for="a in AMTS" :key="a" type="button" :class="amount === a ? 'on' : ''" @click="amount = a">{{ rupees(a) }}</button>
                    </span>
                    <label class="iw amt">
                        <span class="rs" aria-hidden="true">₹</span>
                        <input v-model.number="amount" class="in" type="number" min="100" max="50000" step="1" inputmode="numeric" aria-label="Amount in rupees" />
                    </label>
                    <button type="submit" class="btn bp" :disabled="busy">{{ busy ? 'Adding…' : 'Add by UPI' }}</button>
                </span>
                <span v-if="fieldErr" class="err">{{ fieldErr }}</span>
            </form>
        </div>
        <div class="srow" style="grid-template-columns: minmax(0, 1fr); background: #fffbeb">
            <span class="tx" style="color: #8a5300">No payment gateway is connected in this build yet. Top-ups are recorded as UPI payments for testing; no money moves.</span>
        </div>
        <LoadState v-if="!data" :pending="pending" :error="error" :rows="4" @retry="refresh" />
        <p v-else-if="!history.length" class="tx muted" style="padding: 18px 22px">No wallet activity yet. Add money to run Meta ads and send WhatsApp offers.</p>
        <div v-else class="tscroll">
            <table class="tbl">
                <thead>
                    <tr><th>Date</th><th>What</th><th>Detail</th><th style="text-align: right">Amount</th><th style="text-align: right">Balance</th></tr>
                </thead>
                <tbody>
                    <tr v-for="t in history" :key="t.id" style="cursor: default">
                        <td class="mono" style="white-space: nowrap">{{ day(t.created_at).toUpperCase() }}</td>
                        <td>{{ REASON[t.reason] || t.reason }}</td>
                        <td class="muted">{{ t.description }}{{ t.reference ? ' · ' + t.reference : '' }}</td>
                        <td class="mono" style="text-align: right; white-space: nowrap">{{ (t.amount_paise > 0 ? '+ ' : '− ') + inr(Math.abs(t.amount_paise), { paise: t.amount_paise % 100 !== 0 }) }}</td>
                        <td class="mono muted" style="text-align: right; white-space: nowrap">{{ inr(t.balance_after_paise, { paise: t.balance_after_paise % 100 !== 0 }) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style scoped>
.amt {
    position: relative;
    display: inline-flex;
    align-items: center;
    width: 110px;
}
.amt .in {
    height: 36px;
    padding-left: 24px;
}
.rs {
    position: absolute;
    left: 10px;
    color: #64748b;
    font-size: 13.5px;
    pointer-events: none;
}
@media (max-width: 640px) {
    form {
        align-items: flex-start !important;
    }
    form > span {
        justify-content: flex-start !important;
    }
}
</style>
