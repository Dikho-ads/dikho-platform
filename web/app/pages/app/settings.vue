<script setup lang="ts">
// Settings: one panel with tabs (?tab=billing|wallet|team|channels|shop|account). Each tab is its
// own component that loads only its data: SettingsBilling, SettingsWallet, SettingsTeam,
// SettingsChannels, SettingsShop. The account tab (sign out) lives here.
definePageMeta({ layout: 'app', title: 'Settings' });

const route = useRoute();
const router = useRouter();
const auth = useAuth();

const TABS = [
    { key: 'billing', n: 'Plan & credits', icon: 'M4 6h16v12H4zM4 10h16' },
    { key: 'wallet', n: 'Wallet', icon: 'M3 6h18v14H3zM3 10h18M16 15h2' },
    { key: 'team', n: 'Team', icon: 'M9 11.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM2.5 20c.8-3.6 3.4-5.5 6.5-5.5s5.7 1.9 6.5 5.5M16 4.6a3.5 3.5 0 0 1 0 6.8' },
    { key: 'channels', n: 'Connections', icon: 'M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1' },
    { key: 'shop', n: 'Shop & brand', icon: 'M4 9.5V20h16V9.5M3 4h18l-1.5 5.5a2.6 2.6 0 0 1-5 0 2.6 2.6 0 0 1-5 0 2.6 2.6 0 0 1-5 0z' },
    { key: 'account', n: 'Account', icon: 'M12 3l7.5 3v5.5c0 4.7-3.2 8.3-7.5 9.5-4.3-1.2-7.5-4.8-7.5-9.5V6z' },
];

const tab = computed(() => {
    const t = route.query.tab;
    return TABS.some((x) => x.key === t) ? (t as string) : 'billing';
});
const current = computed(() => TABS.find((x) => x.key === tab.value)!);
function go(key: string) {
    router.replace({ query: { ...route.query, tab: key } });
}

// Each tab reports a short status for the bar above it.
const status = ref<[string, string]>(['st', '']);
watch(tab, () => (status.value = ['st', '']));
const accountStatus = computed<[string, string]>(() => ['st ok', auth.user?.role_label || 'Signed in']);

const signingOut = ref(false);
async function signOut() {
    signingOut.value = true;
    await auth.logout();
}
</script>

<template>
    <div class="wrap">
        <div class="rise" style="display: flex; flex-direction: column; gap: 8px">
            <span class="eyb"><b>SETTINGS</b>·<span>{{ (auth.shop?.name || '').toUpperCase() }}</span></span>
            <h1 class="h-lg">Settings</h1>
        </div>
        <div class="panel rise d1 sbox">
            <nav class="snav" aria-label="Settings sections">
                <button v-for="t in TABS" :key="t.key" type="button" :class="'stab' + (tab === t.key ? ' on' : '')" :aria-current="tab === t.key ? 'page' : undefined" @click="go(t.key)">
                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path :d="t.icon"></path></svg>{{ t.n }}
                </button>
            </nav>
            <div style="flex: 999 1 520px; min-width: 0; display: flex; flex-direction: column">
                <div style="display: flex; align-items: center; gap: 12px; padding: 14px 22px; border-bottom: 1px solid #e2e8f0; background: #fff; flex-wrap: wrap">
                    <span class="h-sm">{{ current.n }}</span><span class="sp"></span>
                    <span v-if="tab === 'account'" :class="accountStatus[0]">{{ accountStatus[1] }}</span>
                    <ClientOnly v-else><span v-if="status[1]" :class="status[0]">{{ status[1] }}</span></ClientOnly>
                </div>

                <SettingsBilling v-if="tab === 'billing'" @status="status = $event" />
                <SettingsWallet v-else-if="tab === 'wallet'" @status="status = $event" />
                <SettingsTeam v-else-if="tab === 'team'" @status="status = $event" />
                <SettingsChannels v-else-if="tab === 'channels'" @status="status = $event" />
                <SettingsShop v-else-if="tab === 'shop'" @status="status = $event" />
                <div v-else class="fade">
                    <div class="srow">
                        <span style="display: flex; gap: 12px; align-items: center; min-width: 0">
                            <span class="mt lt">{{ auth.user?.initials }}</span>
                            <span style="min-width: 0"><span class="h-sm" style="display: block">{{ auth.user?.name }}</span><span class="mono muted" style="overflow-wrap: anywhere">{{ auth.user?.email }}</span></span>
                        </span>
                        <span class="tag">{{ auth.user?.role_label || 'Member' }}</span>
                    </div>
                    <div class="srow">
                        <span><span class="h-sm" style="display: block">Sign-in</span><span class="tx muted">A 6-digit code sent to your email each time. No password to remember.</span></span>
                        <span></span>
                    </div>
                    <div class="srow">
                        <span><span class="h-sm" style="display: block">Sign out</span><span class="tx muted">Signed in as {{ auth.user?.email }}</span></span>
                        <button type="button" class="btn bs" :disabled="signingOut" @click="signOut">{{ signingOut ? 'Signing out…' : 'Sign out' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.sbox {
    display: flex;
    flex-wrap: wrap;
    overflow: hidden;
    min-height: 640px;
}
.snav {
    flex: 1 1 210px;
    max-width: 250px;
    background: #f8fafc;
    border-right: 1px solid #e2e8f0;
    padding: 14px 10px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.stab {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    height: 36px;
    padding: 0 10px;
    border: 0;
    border-radius: 8px;
    background: none;
    text-align: left;
    cursor: pointer;
    font: 500 13.5px var(--f);
    color: #334155;
    transition: background 0.15s;
    white-space: nowrap;
}
.stab:hover {
    background: #f1f5f9;
}
.stab.on {
    background: #fff;
    color: #0f172a;
    box-shadow: 0 0 0 1px #e2e8f0;
}
/* Wide enough rows for the tabs below; the nav becomes a scrolling strip when the panel wraps. */
@media (max-width: 899px) {
    .sbox {
        min-height: 0;
    }
    .snav {
        flex: 1 1 100%;
        max-width: none;
        flex-direction: row;
        overflow-x: auto;
        border-right: 0;
        border-bottom: 1px solid #e2e8f0;
        padding: 8px;
        scrollbar-width: none;
    }
    .snav::-webkit-scrollbar {
        display: none;
    }
    .stab {
        width: auto;
        flex-shrink: 0;
    }
}
:deep(.srow) {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 20px;
    align-items: center;
    padding: 16px 22px;
    border-bottom: 1px solid #e2e8f0;
}
:deep(.srow:last-child) {
    border-bottom: 0;
}
@media (max-width: 640px) {
    :deep(.srow) {
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
        padding: 14px 16px;
    }
}
</style>
