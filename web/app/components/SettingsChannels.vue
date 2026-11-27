<script setup lang="ts">
// Settings › Connections: GET connections, link (PATCH connections/{provider} {account}) and
// unlink ({disconnect:true}). Linking only records the account; the provider's own sign-in
// finishes it later, so a fresh link shows as "Waiting for sign-in" (status pending).
const emit = defineEmits<{ status: [[string, string]] }>();
const api = useApi();
const toast = useToast();
const { confirm } = useConfirm();

const { data, pending, error, refresh } = useApiData<{ data: any[] }>('connections', 'connections');
const list = computed(() => data.value?.data ?? []);
watch(
    list,
    (l) => {
        if (l.length) emit('status', ['st ok', `${l.filter((c) => c.status === 'connected').length} of ${l.length} connected`]);
    },
    { immediate: true },
);

const P: Record<string, { m: string; n: string; d: string; ph: string }> = {
    whatsapp: { m: 'WA', n: 'WhatsApp Business', d: 'Status, broadcasts and replies', ph: '+91 98200 00147' },
    instagram: { m: 'IG', n: 'Instagram', d: 'Posts and reels on your business account', ph: '@yourshop' },
    facebook: { m: 'FB', n: 'Facebook Page', d: 'Posts on your shop’s page', ph: 'Your page name' },
    google: { m: 'GB', n: 'Google Business Profile', d: 'Offer posts on your Google listing', ph: 'Shop name, area' },
    meta_ads: { m: 'AD', n: 'Meta ads', d: 'Click-to-WhatsApp ads paid from the wallet', ph: 'Ad account name' },
    upi: { m: 'UPI', n: 'UPI AutoPay', d: 'Pay the monthly plan automatically', ph: 'yourname@upi' },
};
const ST: Record<string, [string, string]> = { connected: ['st ok', 'Connected'], pending: ['st warn', 'Waiting for sign-in'], not_linked: ['st', 'Not linked'] };

// useAsyncData data is a shallow ref in Nuxt 4: replace the row so the list re-renders.
function put(row: any) {
    data.value = { data: list.value.map((x) => (x.provider === row.provider ? row : x)) };
}

const editing = ref<string | null>(null);
const account = ref('');
const busy = ref<string | null>(null);
const fieldErr = ref('');

function startLink(c: any) {
    editing.value = c.provider;
    account.value = c.account ?? '';
    fieldErr.value = '';
}
async function link(c: any) {
    busy.value = c.provider;
    fieldErr.value = '';
    try {
        const res = await api<{ data: any }>(`connections/${c.provider}`, { method: 'PATCH', body: { account: account.value.trim() || null } });
        put(res.data);
        editing.value = null;
        toast.ok(`Saved. ${P[c.provider]?.n ?? c.provider} shows as connected once you finish its own sign-in — that step is not wired up in this build yet.`);
    } catch (e: any) {
        if (e.field('account')) fieldErr.value = e.field('account');
        else toast.error(e.message);
    } finally {
        busy.value = null;
    }
}
async function unlink(c: any) {
    const name = P[c.provider]?.n ?? c.provider;
    const ok = await confirm({ title: `Unlink ${name}?`, body: `Dikho stops using ${name} until you link it again.`, confirm: 'Unlink', danger: true });
    if (!ok) return;
    busy.value = c.provider;
    try {
        const res = await api<{ data: any }>(`connections/${c.provider}`, { method: 'PATCH', body: { disconnect: true } });
        put(res.data);
        toast.ok(`${name} unlinked`);
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        busy.value = null;
    }
}
</script>

<template>
    <div class="fade">
        <LoadState v-if="!data" :pending="pending" :error="error" :rows="5" @retry="refresh" />
        <p v-else-if="!list.length" class="tx muted" style="padding: 18px 22px">No channels to link yet.</p>
        <template v-else>
            <div v-for="c in list" :key="c.provider" class="srow">
                <span style="display: flex; gap: 12px; align-items: center; min-width: 0">
                    <span :class="c.status === 'connected' ? 'mt' : 'mt lt'">{{ P[c.provider]?.m || c.provider.slice(0, 2).toUpperCase() }}</span>
                    <span style="min-width: 0; flex: 1">
                        <span class="h-sm" style="display: block">{{ P[c.provider]?.n || c.provider }}</span>
                        <span class="mono muted" style="overflow-wrap: anywhere">{{ c.account ? c.account : P[c.provider]?.d }}{{ c.status === 'connected' && c.connected_at ? ' · since ' + day(c.connected_at) : '' }}</span>
                        <form v-if="editing === c.provider" class="lk fade" novalidate @submit.prevent="link(c)">
                            <input v-model="account" class="in" :placeholder="P[c.provider]?.ph" maxlength="120" :aria-label="(P[c.provider]?.n || c.provider) + ' account'" autofocus />
                            <button type="submit" class="btn bp sm" :disabled="busy === c.provider">{{ busy === c.provider ? 'Saving…' : 'Save' }}</button>
                            <button type="button" class="btn bg sm" @click="editing = null">Cancel</button>
                            <span v-if="fieldErr" class="err" style="flex-basis: 100%">{{ fieldErr }}</span>
                        </form>
                    </span>
                </span>
                <span style="display: inline-flex; gap: 12px; align-items: center">
                    <span :class="ST[c.status]?.[0] || 'st'">{{ ST[c.status]?.[1] || c.status }}</span>
                    <button v-if="c.status === 'not_linked'" type="button" class="btn bp sm" :disabled="editing === c.provider" @click="startLink(c)">Connect</button>
                    <button v-else type="button" class="btn bs sm" :disabled="busy === c.provider" @click="unlink(c)">Unlink</button>
                </span>
            </div>
            <p class="tx muted" style="padding: 14px 22px 18px">Connecting saves the account here; the final step is signing in on Meta, Google or your UPI app, which this build does not do yet — until then it shows “Waiting for sign-in”.</p>
        </template>
    </div>
</template>

<style scoped>
.lk {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-top: 10px;
}
.lk .in {
    height: 32px;
    flex: 1 1 180px;
    min-width: 0;
}
</style>
