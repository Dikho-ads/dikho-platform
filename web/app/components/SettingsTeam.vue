<script setup lang="ts">
// Settings › Team: GET team, invite by name + phone + role with POST team (owner only).
const emit = defineEmits<{ status: [[string, string]] }>();
const api = useApi();
const auth = useAuth();
const toast = useToast();

const { data, pending, error, refresh } = useApiData<{ data: any[] }>('team', 'team');
const members = computed(() => data.value?.data ?? []);
watch(members, (m) => emit('status', ['st', m.length + (m.length === 1 ? ' person' : ' people')]), { immediate: true });

const ROLES: Record<string, [string, string]> = {
    owner: ['Owner', 'Everything, including plan, wallet and team'],
    manager: ['Manager', 'Make, approve and post, and see customers — can’t pay or change the plan'],
    staff: ['Staff', 'Make and post — can’t approve, pay or change the plan'],
};
const PERM: Record<string, string> = { approve: 'approve posts', post: 'make and post', customers: 'customers' };
const isOwner = computed(() => auth.user?.role === 'owner');

const phone = (p?: string | null) => (p && p.length === 10 ? p.slice(0, 5) + ' ' + p.slice(5) : p || '—');
const initials = (n: string) =>
    n
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
const rows = computed(() =>
    members.value.map((m) => {
        const you = m.role === 'owner' ? isOwner.value : m.phone && m.phone === auth.user?.phone;
        const can = m.role === 'owner' ? ROLES.owner[1] : (m.permissions || []).map((p: string) => PERM[p] || p).join(', ') || ROLES[m.role]?.[1] || '—';
        return {
            id: m.id,
            n: m.name + (you ? ' (you)' : ''),
            ini: initials(m.name),
            ph: phone(m.phone),
            r: (ROLES[m.role]?.[0] || m.role) + (m.joined_at ? '' : ' · invited'),
            tc: m.role === 'owner' ? 'tag dk' : m.joined_at ? 'tag' : 'tag acc',
            can: can.charAt(0).toUpperCase() + can.slice(1),
            when: m.joined_at ? 'joined ' + day(m.joined_at) : m.invited_at ? 'invited ' + day(m.invited_at) : '',
        };
    }),
);

const form = reactive({ name: '', phone: '', role: 'staff' });
const errs = ref<Record<string, string | undefined>>({});
const busy = ref(false);
async function invite() {
    errs.value = {};
    busy.value = true;
    try {
        const res = await api<{ data: any }>('team', { method: 'POST', body: { name: form.name.trim(), phone: form.phone.replace(/\D/g, '').slice(-10), role: form.role } });
        toast.ok(`${res.data.name} added as ${ROLES[res.data.role]?.[0] ?? res.data.role}`);
        form.name = '';
        form.phone = '';
        refresh();
    } catch (e: any) {
        if (Object.keys(e.errors || {}).length) errs.value = { name: e.field('name'), phone: e.field('phone'), role: e.field('role') };
        else toast.error(e.message);
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="fade">
        <LoadState v-if="!data" :pending="pending" :error="error" :rows="3" @retry="refresh" />
        <div v-else class="tscroll">
            <table class="tbl">
                <thead>
                    <tr><th>Person</th><th>Phone</th><th>Role</th><th>Can</th></tr>
                </thead>
                <tbody>
                    <tr v-for="m in rows" :key="m.id" style="cursor: default">
                        <td>
                            <span style="display: inline-flex; gap: 10px; align-items: center">
                                <span class="av">{{ m.ini }}</span>
                                <span style="display: flex; flex-direction: column; gap: 2px"><b style="font-weight: 500; white-space: nowrap">{{ m.n }}</b><span class="mono muted" style="font-size: 11px">{{ m.when }}</span></span>
                            </span>
                        </td>
                        <td class="mono" style="white-space: nowrap">{{ m.ph }}</td>
                        <td><span :class="m.tc">{{ m.r }}</span></td>
                        <td class="muted" style="min-width: 200px">{{ m.can }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <form v-if="isOwner" class="invite" novalidate @submit.prevent="invite">
            <div class="fld" style="flex: 1 1 200px">
                <label class="lab" for="tn">Name</label><input id="tn" v-model="form.name" class="in" maxlength="80" autocomplete="off" />
                <span v-if="errs.name" class="err">{{ errs.name }}</span>
            </div>
            <div class="fld" style="flex: 1 1 180px">
                <label class="lab" for="tp">Mobile number</label><input id="tp" v-model="form.phone" class="in" inputmode="numeric" maxlength="14" placeholder="98200 00147" />
                <span v-if="errs.phone" class="err">{{ errs.phone }}</span>
            </div>
            <div class="fld">
                <span class="lab">Role</span>
                <span class="seg"><button v-for="r in ['manager', 'staff']" :key="r" type="button" :class="form.role === r ? 'on' : ''" @click="form.role = r">{{ ROLES[r][0] }}</button></span>
                <span v-if="errs.role" class="err">{{ errs.role }}</span>
            </div>
            <button type="submit" class="btn bp" :disabled="busy">{{ busy ? 'Adding…' : 'Add to team' }}</button>
        </form>
        <p class="tx muted" style="padding: 0 22px 18px">
            <template v-if="isOwner">{{ ROLES[form.role][0] }}: {{ ROLES[form.role][1] }}. Invite messages are not sent yet in this build, so tell them yourself.</template>
            <template v-else>Only the shop owner can add people.</template>
        </p>
    </div>
</template>

<style scoped>
.invite {
    padding: 18px 22px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}
@media (max-width: 640px) {
    .invite {
        padding: 14px 16px;
    }
}
</style>
