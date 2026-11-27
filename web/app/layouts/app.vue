<script setup lang="ts">
// The signed-in shell. Three layouts switched in CSS: >= 1280 full sidebar, 900-1279 icon rail,
// < 900 phone shell (top header + bottom tab bar + menu sheet), as in the Design canvas.
const auth = useAuth();
const route = useRoute();
const router = useRouter();
const api = useApi();

const ICON: Record<string, string> = {
    home: 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z',
    studio: 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
    auto: 'M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7',
    plan: 'M6 3h9l4 4v14H6zM14 3v5h5M9 12h7M9 16h5',
    editor: 'M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4',
    messages: 'M4 20l1.3-3.9A8 8 0 1 1 8 19z',
    workflows: 'M6 3v6M6 15v6M6 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6zM18 6a3 3 0 1 0 0 .01M9 12h3a3 3 0 0 0 3-3V7.5',
    campaigns: 'M3 10v4a1 1 0 0 0 1 1h3l6 4V5L7 9H4a1 1 0 0 0-1 1zM17 8.5a5 5 0 0 1 0 7',
    calendar: 'M4 5h16v16H4zM4 10h16M8 3v4M16 3v4',
    occasions: 'M5 21V4M5 4h11l-2 4 2 4H5',
    customers:
        'M9 11.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM2.5 20c.8-3.6 3.4-5.5 6.5-5.5s5.7 1.9 6.5 5.5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c1.9.6 3.1 2.4 3.5 5.2',
    results: 'M5 20v-7M11 20V5M17 20v-10M3 20h18',
    settings: 'M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M16 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM10 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4z',
    plus: 'M12 5v14M5 12h14',
    mic: 'M12 3a3 3 0 0 1 3 3v5a3 3 0 0 1-6 0V6a3 3 0 0 1 3-3zM5 11a7 7 0 0 0 14 0M12 18v3',
    bell: 'M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15zM10 20.5a2 2 0 0 0 4 0',
    close: 'M6 6l12 12M18 6L6 18',
    logout: 'M14 4h5a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-5M10 16l-4-4 4-4M6 12h10',
};

type NavItem = { to: string; label: string; icon: string; exact?: boolean; count?: number | null; also?: string[]; raised?: boolean };

const counts = computed(() => auth.account?.counts);
const groups = computed<{ head?: string; items: NavItem[] }[]>(() => [
    { items: [{ to: '/app', label: 'Home', icon: 'home', exact: true }] },
    {
        head: 'Content Studio · कंटेंट',
        items: [
            { to: '/app/studio', label: 'Overview', icon: 'studio', exact: true },
            { to: '/app/studio/auto', label: 'Auto mode', icon: 'auto', count: counts.value?.auto_pending || null },
            { to: '/app/studio/plan', label: 'Plan mode', icon: 'plan' },
            { to: '/app/studio/editor', label: 'Editor', icon: 'editor' },
            { to: '/app/studio/messages', label: 'Messages', icon: 'messages' },
            { to: '/app/studio/workflows', label: 'Workflows', icon: 'workflows', count: counts.value?.workflows_active || null },
            { to: '/app/studio/campaigns', label: 'Campaigns', icon: 'campaigns', count: counts.value?.campaigns_live || null },
        ],
    },
    {
        head: 'Plan & grow',
        items: [
            { to: '/app/calendar', label: 'Calendar', icon: 'calendar' },
            { to: '/app/occasions', label: 'Occasions', icon: 'occasions' },
            { to: '/app/customers', label: 'Customers', icon: 'customers' },
            { to: '/app/results', label: 'Results', icon: 'results' },
        ],
    },
]);

// Bottom tab bar on phones: Home, Plan, raised Studio, Customers, Results.
const tabs: NavItem[] = [
    { to: '/app', label: 'Home', icon: 'home', exact: true },
    { to: '/app/calendar', label: 'Plan', icon: 'calendar' },
    { to: '/app/studio', label: 'Studio', icon: 'plus', raised: true, also: ['/app/create'] },
    { to: '/app/customers', label: 'Customers', icon: 'customers' },
    { to: '/app/results', label: 'Results', icon: 'results' },
];

const under = (to: string) => route.path === to || route.path.startsWith(to + '/');
const isOn = (item: NavItem) => (item.exact ? route.path === item.to : under(item.to) || (item.also || []).some(under));
const title = computed(() => (route.meta.title as string) || '');
const shop = computed(() => auth.shop);
const credits = computed(() => auth.account?.credits);
const creditsWidth = computed(() => (credits.value?.percent_left ?? 0) + '%');
const resetsOn = computed(() => (credits.value ? day(credits.value.resets_on) : ''));
const wallet = computed(() => inr(auth.account?.wallet_paise ?? 0));
const packageName = computed(() => auth.account?.package?.name ?? '');

useHead(() => ({ title: title.value ? `${title.value} · ${shop.value?.short_name ?? ''}` : shop.value?.name }));

const menuOpen = ref(false);
watch(
    () => route.fullPath,
    () => {
        menuOpen.value = false;
        bellOpen.value = false;
    },
);

// Header search: customers by name or number.
const q = ref('');
function search() {
    const term = q.value.trim();
    if (term) router.push({ path: '/app/customers', query: { q: term } });
}

// Notifications.
const bellOpen = ref(false);
const feed = ref<any[] | null>(null);
async function toggleBell() {
    bellOpen.value = !bellOpen.value;
    if (!bellOpen.value) return;
    try {
        const res = await api<{ data: any[] }>('notifications');
        feed.value = res.data;
        if (counts.value?.notifications_unread) {
            await api('notifications/read', { method: 'POST' });
            if (auth.account?.counts) auth.account.counts.notifications_unread = 0;
        }
    } catch (e: any) {
        useToast().error(e.message);
        bellOpen.value = false;
    }
}

function signOut() {
    auth.logout();
}
</script>

<template>
    <div class="app">
        <aside class="side">
            <NuxtLink class="brand" to="/app" aria-label="Dikho home"><Logo /></NuxtLink>
            <NuxtLink class="shop" to="/app/settings" aria-label="Shop settings" :title="shop?.name">
                <span class="mt sm">{{ shop?.initials }}</span>
                <span class="grow lbl"><b>{{ shop?.name }}</b><span>{{ shop?.city || shop?.category_label }} · {{ packageName }}</span></span>
                <svg class="i s lbl" viewBox="0 0 24 24" aria-hidden="true" style="color: #94a3b8"><path d="M8 9l4-4 4 4M8 15l4 4 4-4"></path></svg>
            </NuxtLink>
            <nav class="nav" aria-label="Main">
                <template v-for="(g, gi) in groups" :key="gi">
                    <span v-if="g.head" class="navh" style="margin-top: 12px">{{ g.head }}</span>
                    <NuxtLink v-for="item in g.items" :key="item.to" :to="item.to" :class="{ on: isOn(item) }" :title="item.label">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON[item.icon]"></path></svg>
                        <span class="lbl">{{ item.label }}</span>
                        <span v-if="item.count" class="ct">{{ item.count }}</span>
                    </NuxtLink>
                </template>
            </nav>
            <span class="sp"></span>
            <NuxtLink to="/app/settings?tab=billing" class="panel credits" :title="`Credits ${credits?.left ?? 0} / ${credits?.total ?? 0}`">
                <span class="credits__top">
                    <span class="lm lbl">Credits</span>
                    <span class="mono"><b style="font-weight: 600">{{ credits?.left ?? 0 }}</b><span class="muted lbl"> / {{ credits?.total ?? 0 }}</span></span>
                </span>
                <span class="bar-t"><i :style="{ width: creditsWidth, background: '#F2A007' }"></i></span>
                <span class="tx muted lbl">Resets {{ resetsOn }} · wallet {{ wallet }}</span>
            </NuxtLink>
            <div class="me">
                <span class="av">{{ auth.user?.initials }}</span>
                <span class="grow lbl">
                    <span class="ts" style="display: block; font-weight: 500; line-height: 1.2">{{ auth.user?.first_name }}</span>
                    <span class="tx muted">{{ auth.user?.role_label }}</span>
                </span>
                <button class="btn bg ib sm lbl" type="button" aria-label="Sign out" title="Sign out" @click="signOut">
                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON.logout"></path></svg>
                </button>
            </div>
        </aside>

        <div class="main">
            <header class="bar">
                <!-- phone header -->
                <NuxtLink class="m-only m-logo" to="/app" aria-label="Dikho home"><Logo :name="false" :size="28" /></NuxtLink>
                <span class="m-only m-title">{{ title }}</span>
                <!-- laptop header -->
                <span class="crumb d-only">{{ shop?.name }} <b>/ {{ title }}</b></span>
                <span class="sp"></span>
                <form class="search d-only" role="search" @submit.prevent="search">
                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4"></path></svg>
                    <input v-model="q" placeholder="Search customers by name or number" aria-label="Search customers" />
                </form>
                <NuxtLink class="btn ba voice" to="/app/create" aria-label="Bol ke batayein">
                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON.mic"></path></svg>
                    <span class="voice__txt">Bol ke batayein</span>
                </NuxtLink>
                <div class="bell">
                    <button class="btn bg ib" type="button" aria-label="Notifications" :aria-expanded="bellOpen" @click="toggleBell">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON.bell"></path></svg>
                        <span v-if="counts?.notifications_unread" class="bell__dot" aria-hidden="true"></span>
                    </button>
                    <div v-if="bellOpen" class="bell__pop panel" role="dialog" aria-label="Notifications">
                        <div class="ph"><span class="eyb"><span>Notifications</span></span></div>
                        <LoadState :pending="feed === null" :empty="feed?.length === 0" empty-text="Nothing new.">
                            <NuxtLink v-for="n in feed" :key="n.id" class="row" :to="n.link || '/app'">
                                <span class="grow"><span class="h-sm" style="display: block">{{ n.title }}</span><span class="tx muted">{{ n.body }}</span></span>
                                <span class="mono muted">{{ relative(n.created_at) }}</span>
                            </NuxtLink>
                        </LoadState>
                    </div>
                </div>
                <button class="m-only av m-me" type="button" aria-label="Open menu" @click="menuOpen = true">{{ auth.user?.initials }}</button>
            </header>
            <slot />
        </div>

        <!-- phone: bottom tab bar -->
        <nav class="tabbar m-only" aria-label="Sections">
            <NuxtLink v-for="t in tabs" :key="t.to" :to="t.to" :class="{ on: isOn(t), raised: t.raised }">
                <span v-if="t.raised" class="tabbar__plus"><svg class="i" viewBox="0 0 24 24" aria-hidden="true" style="stroke-width: 2.2"><path :d="ICON.plus"></path></svg></span>
                <svg v-else class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON[t.icon]"></path></svg>
                {{ t.label }}
            </NuxtLink>
        </nav>

        <!-- phone: menu sheet with every section -->
        <template v-if="menuOpen">
            <div class="scrim" @click="menuOpen = false"></div>
            <div class="sheet" role="dialog" aria-modal="true" aria-label="Menu">
                <div class="sheet__head">
                    <span class="mt sm">{{ shop?.initials }}</span>
                    <span class="grow"><b class="h-sm" style="display: block">{{ shop?.name }}</b><span class="tx muted">{{ auth.user?.first_name }} · {{ auth.user?.role_label }} · {{ packageName }}</span></span>
                    <button class="btn bg ib" type="button" aria-label="Close menu" @click="menuOpen = false">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON.close"></path></svg>
                    </button>
                </div>
                <NuxtLink to="/app/settings?tab=billing" class="panel credits credits--sheet">
                    <span class="credits__top">
                        <span class="lm">Credits</span>
                        <span class="mono"><b style="font-weight: 600">{{ credits?.left ?? 0 }}</b><span class="muted"> / {{ credits?.total ?? 0 }}</span></span>
                    </span>
                    <span class="bar-t"><i :style="{ width: creditsWidth, background: '#F2A007' }"></i></span>
                    <span class="tx muted">Resets {{ resetsOn }} · wallet {{ wallet }}</span>
                </NuxtLink>
                <nav class="nav sheet__nav" aria-label="All sections">
                    <template v-for="(g, gi) in groups" :key="gi">
                        <span v-if="g.head" class="navh" style="margin-top: 10px">{{ g.head }}</span>
                        <NuxtLink v-for="item in g.items" :key="item.to" :to="item.to" :class="{ on: isOn(item) }">
                            <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON[item.icon]"></path></svg>
                            {{ item.label }}
                            <span v-if="item.count" class="ct">{{ item.count }}</span>
                        </NuxtLink>
                    </template>
                    <span class="navh" style="margin-top: 10px">Account</span>
                    <NuxtLink to="/app/settings" :class="{ on: isOn({ to: '/app/settings', label: '', icon: '' }) }">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON.settings"></path></svg>
                        Settings
                    </NuxtLink>
                    <a href="#" @click.prevent="signOut">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON.logout"></path></svg>
                        Sign out
                    </a>
                </nav>
            </div>
        </template>
    </div>
</template>

<style scoped>
.bell {
    position: relative;
}
.bell__dot {
    position: absolute;
    top: 7px;
    right: 8px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #f2a007;
    border: 2px solid #fff;
}
.bell__pop {
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    width: min(360px, calc(100vw - 24px));
    max-height: 70vh;
    overflow-y: auto;
    z-index: 25;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
}
@media (max-width: 899px) {
    .bell__pop {
        position: fixed;
        right: 12px;
        top: 60px;
    }
}
</style>

<style scoped>
.credits {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    text-decoration: none;
    color: var(--ink);
}
.credits__top {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
}
.me {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 4px 6px;
}
.m-only {
    display: none;
}

/* ---------- 900-1279: icon rail ---------- */
@media (min-width: 900px) and (max-width: 1279px) {
    .side {
        flex: 0 0 68px;
        width: 68px;
        padding: 16px 10px;
        gap: 14px;
        align-items: center;
    }
    .side .lbl,
    .side .navh,
    .side .ct {
        display: none;
    }
    .side .brand {
        padding: 0;
    }
    .side .brand :deep(.dk-logo__name) {
        display: none;
    }
    .side .shop {
        width: 44px;
        justify-content: center;
        padding: 7px;
    }
    .side .nav {
        width: 100%;
        align-items: center;
    }
    .side .nav a {
        width: 40px;
        padding: 0;
        justify-content: center;
    }
    .side .credits {
        width: 48px;
        padding: 8px 6px;
        align-items: center;
    }
    .side .credits__top {
        justify-content: center;
    }
    .side .credits .bar-t {
        width: 100%;
    }
    .side .me {
        padding: 0;
        justify-content: center;
    }
    .voice__txt {
        display: none;
    }
    .voice {
        width: 36px;
        padding: 0;
    }
    .search {
        width: 240px;
    }
}

/* ---------- < 900: phone shell (Mobile v2) ---------- */
@media (max-width: 899px) {
    .side,
    .d-only {
        display: none;
    }
    .m-only {
        display: revert;
    }
    .main {
        padding-bottom: calc(68px + env(safe-area-inset-bottom));
    }
    .bar {
        position: sticky;
        top: 0;
        z-index: 15;
        background: rgba(255, 255, 255, 0.94);
        backdrop-filter: blur(8px);
        min-height: 56px;
        padding: 8px 12px 8px 16px;
        gap: 8px;
        flex-wrap: nowrap;
    }
    .m-logo {
        display: inline-flex;
    }
    .m-title {
        display: block;
        font: 600 17px/1.2 var(--f);
        letter-spacing: -0.02em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
    }
    .voice {
        width: 36px;
        padding: 0;
    }
    .voice__txt {
        display: none;
    }
    .m-me {
        display: grid;
        width: 34px;
        height: 34px;
        border: 0;
        cursor: pointer;
    }
    .tabbar {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 18;
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        align-items: end;
        padding: 6px 6px calc(6px + env(safe-area-inset-bottom));
        background: rgba(255, 255, 255, 0.97);
        backdrop-filter: blur(8px);
        border-top: 1px solid var(--line);
    }
    .tabbar a {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        min-height: 50px;
        justify-content: flex-end;
        padding: 4px 0;
        font: 500 11px/1 var(--f);
        color: var(--ink-3);
        text-decoration: none;
    }
    .tabbar a.on {
        color: var(--ink);
    }
    .tabbar a.on .i {
        stroke-width: 2.1;
    }
    .tabbar__plus {
        display: grid;
        place-items: center;
        width: 46px;
        height: 46px;
        margin-top: -22px;
        border-radius: 14px;
        background: var(--ink);
        color: #fff;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.25);
    }
    .tabbar a.raised.on .tabbar__plus {
        background: var(--acc);
        color: var(--ink);
    }
    .sheet {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 21;
        max-height: 88vh;
        overflow-y: auto;
        background: var(--card);
        border-radius: 18px 18px 0 0;
        padding: 14px 14px calc(18px + env(safe-area-inset-bottom));
        display: flex;
        flex-direction: column;
        gap: 12px;
        box-shadow: 0 -24px 48px rgba(15, 23, 42, 0.18);
        animation: sheet-up 0.3s var(--ease) both;
    }
    .sheet__head {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .sheet__nav a {
        height: 44px;
    }
}
@keyframes sheet-up {
    from {
        transform: translateY(40px);
        opacity: 0;
    }
}
</style>
