<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useApp } from '@/stores/app';
import Logo from '@/components/ui/Logo.vue';

const app = useApp();
const route = useRoute();
const router = useRouter();

// Three layouts, switched in CSS: >= 1280 full sidebar (the design), 900-1279 icon rail,
// < 900 phone shell from the Mobile v2 design (top header + bottom tab bar + menu sheet).
const ICON = {
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

const groups = [
    { items: [{ to: '/app', name: 'home', label: 'Home', icon: 'home', exact: true }] },
    {
        head: 'Content Studio · कंटेंट',
        items: [
            { to: '/app/studio', name: 'studio', label: 'Overview', icon: 'studio', exact: true },
            { to: '/app/studio/auto', name: 'studio-auto', label: 'Auto mode', icon: 'auto', count: '38' },
            { to: '/app/studio/plan', name: 'studio-plan', label: 'Plan mode', icon: 'plan' },
            { to: '/app/studio/editor', name: 'studio-editor', label: 'Editor', icon: 'editor' },
            { to: '/app/studio/messages', name: 'studio-messages', label: 'Messages', icon: 'messages' },
            { to: '/app/studio/workflows', name: 'studio-workflows', label: 'Workflows', icon: 'workflows', count: '5' },
            { to: '/app/studio/campaigns', name: 'studio-campaign', label: 'Campaigns', icon: 'campaigns', count: '2' },
        ],
    },
    {
        head: 'Plan & grow',
        items: [
            { to: '/app/calendar', name: 'calendar', label: 'Calendar', icon: 'calendar' },
            { to: '/app/occasions', name: 'occasions', label: 'Occasions', icon: 'occasions' },
            { to: '/app/customers', name: 'customers', label: 'Customers', icon: 'customers' },
            { to: '/app/results', name: 'results', label: 'Results', icon: 'results' },
        ],
    },
];

// Bottom tab bar on phones, as in Mobile v2: Home, Plan, raised Studio, Customers, Results.
const tabs = [
    { to: '/app', label: 'Home', icon: 'home', exact: true },
    { to: '/app/calendar', label: 'Plan', icon: 'calendar' },
    { to: '/app/studio', label: 'Studio', icon: 'plus', raised: true, also: ['/app/create'] },
    { to: '/app/customers', label: 'Customers', icon: 'customers' },
    { to: '/app/results', label: 'Results', icon: 'results' },
];

const under = (to) => route.path === to || route.path.startsWith(to + '/');
const isOn = (item) => (item.exact ? route.path === item.to : under(item.to) || (item.also || []).some(under));
const title = computed(() => route.meta.title || '');
const creditsWidth = computed(() => app.creditsPct + '%');

const menuOpen = ref(false);
watch(
    () => route.fullPath,
    () => (menuOpen.value = false),
);

function signOut() {
    app.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="app">
        <aside class="side">
            <RouterLink class="brand" :to="{ name: 'home' }" aria-label="Dikho home"><Logo /></RouterLink>
            <RouterLink class="shop" :to="{ name: 'settings' }" aria-label="Shop settings" :title="app.shop.name">
                <span class="mt sm">{{ app.shop.initials }}</span>
                <span class="grow lbl"><b>{{ app.shop.name }}</b><span>{{ app.shop.city }} · {{ app.pkg.name }}</span></span>
                <svg class="i s lbl" viewBox="0 0 24 24" aria-hidden="true" style="color: #94a3b8"><path d="M8 9l4-4 4 4M8 15l4 4 4-4"></path></svg>
            </RouterLink>
            <nav class="nav" aria-label="Main">
                <template v-for="(g, gi) in groups" :key="gi">
                    <span v-if="g.head" class="navh" style="margin-top: 12px">{{ g.head }}</span>
                    <RouterLink v-for="item in g.items" :key="item.name" :to="item.to" :class="{ on: isOn(item) }" :title="item.label">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON[item.icon]"></path></svg>
                        <span class="lbl">{{ item.label }}</span>
                        <span v-if="item.count" class="ct">{{ item.count }}</span>
                    </RouterLink>
                </template>
            </nav>
            <span class="sp"></span>
            <RouterLink :to="{ name: 'settings' }" class="panel credits" :title="`Credits ${app.creditsLeft} / ${app.creditsTotal}`">
                <span class="credits__top">
                    <span class="lm lbl">Credits</span>
                    <span class="mono"><b style="font-weight: 600">{{ app.creditsLeft }}</b><span class="muted lbl"> / {{ app.creditsTotal }}</span></span>
                </span>
                <span class="bar-t"><i :style="{ width: creditsWidth, background: '#F2A007' }"></i></span>
                <span class="tx muted lbl">Resets {{ app.resetsOn }} · wallet ₹{{ app.wallet.toLocaleString('en-IN') }}</span>
            </RouterLink>
            <div class="me">
                <span class="av">{{ app.user.initials }}</span>
                <span class="grow lbl">
                    <span class="ts" style="display: block; font-weight: 500; line-height: 1.2">{{ app.user.name }}</span>
                    <span class="tx muted">{{ app.user.role }}</span>
                </span>
                <RouterLink class="btn bg ib sm lbl" :to="{ name: 'settings' }" aria-label="Settings">
                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON.settings"></path></svg>
                </RouterLink>
            </div>
        </aside>

        <div class="main">
            <header class="bar">
                <!-- phone header -->
                <RouterLink class="m-only m-logo" :to="{ name: 'home' }" aria-label="Dikho home"><Logo :name="false" :size="28" /></RouterLink>
                <span class="m-only m-title">{{ title }}</span>
                <!-- laptop header -->
                <span class="crumb d-only">{{ app.shop.name }} <b>/ {{ title }}</b></span>
                <span class="sp"></span>
                <label class="search d-only">
                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4"></path></svg>
                    <input placeholder="Search posts, customers, dates" aria-label="Search" />
                    <span class="kbd">⌘K</span>
                </label>
                <RouterLink class="btn ba voice" :to="{ name: 'create' }" aria-label="Bol ke batayein">
                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON.mic"></path></svg>
                    <span class="voice__txt">Bol ke batayein</span>
                </RouterLink>
                <button class="btn bg ib" type="button" aria-label="Notifications">
                    <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON.bell"></path></svg>
                </button>
                <button class="m-only av m-me" type="button" aria-label="Open menu" @click="menuOpen = true">{{ app.user.initials }}</button>
            </header>
            <RouterView :key="route.fullPath" />
        </div>

        <!-- phone: bottom tab bar -->
        <nav class="tabbar m-only" aria-label="Sections">
            <RouterLink v-for="t in tabs" :key="t.to" :to="t.to" :class="{ on: isOn(t), raised: t.raised }">
                <span v-if="t.raised" class="tabbar__plus"><svg class="i" viewBox="0 0 24 24" aria-hidden="true" style="stroke-width: 2.2"><path :d="ICON.plus"></path></svg></span>
                <svg v-else class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON[t.icon]"></path></svg>
                {{ t.label }}
            </RouterLink>
        </nav>

        <!-- phone: menu sheet with every section -->
        <template v-if="menuOpen">
            <div class="scrim" @click="menuOpen = false"></div>
            <div class="sheet" role="dialog" aria-modal="true" aria-label="Menu">
                <div class="sheet__head">
                    <span class="mt sm">{{ app.shop.initials }}</span>
                    <span class="grow"><b class="h-sm" style="display: block">{{ app.shop.name }}</b><span class="tx muted">{{ app.user.name }} · {{ app.user.role }} · {{ app.pkg.name }}</span></span>
                    <button class="btn bg ib" type="button" aria-label="Close menu" @click="menuOpen = false">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON.close"></path></svg>
                    </button>
                </div>
                <RouterLink :to="{ name: 'settings' }" class="panel credits credits--sheet">
                    <span class="credits__top">
                        <span class="lm">Credits</span>
                        <span class="mono"><b style="font-weight: 600">{{ app.creditsLeft }}</b><span class="muted"> / {{ app.creditsTotal }}</span></span>
                    </span>
                    <span class="bar-t"><i :style="{ width: creditsWidth, background: '#F2A007' }"></i></span>
                    <span class="tx muted">Resets {{ app.resetsOn }} · wallet ₹{{ app.wallet.toLocaleString('en-IN') }}</span>
                </RouterLink>
                <nav class="nav sheet__nav" aria-label="All sections">
                    <template v-for="(g, gi) in groups" :key="gi">
                        <span v-if="g.head" class="navh" style="margin-top: 10px">{{ g.head }}</span>
                        <RouterLink v-for="item in g.items" :key="item.name" :to="item.to" :class="{ on: isOn(item) }">
                            <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON[item.icon]"></path></svg>
                            {{ item.label }}
                            <span v-if="item.count" class="ct">{{ item.count }}</span>
                        </RouterLink>
                    </template>
                    <span class="navh" style="margin-top: 10px">Account</span>
                    <RouterLink to="/app/settings" :class="{ on: isOn({ to: '/app/settings' }) }">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON.settings"></path></svg>
                        Settings
                    </RouterLink>
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
    .side .brand :deep(.dk-logo > span) {
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
