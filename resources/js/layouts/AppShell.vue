<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useApp } from '@/stores/app';
import Logo from '@/components/ui/Logo.vue';

const app = useApp();
const route = useRoute();

// Sidebar from the newest design boards (Content Studio era). `exact` links are active only on
// their own path; the rest also stay active on their sub-pages.
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

const isOn = (item) => (item.exact ? route.path === item.to : route.path === item.to || route.path.startsWith(item.to + '/'));
const title = computed(() => route.meta.title || '');
const creditsWidth = computed(() => app.creditsPct + '%');
</script>

<template>
    <div class="app">
        <aside class="side">
            <RouterLink class="brand" :to="{ name: 'home' }" aria-label="Dikho home"><Logo /></RouterLink>
            <RouterLink class="shop" :to="{ name: 'settings' }" aria-label="Shop settings">
                <span class="mt sm">{{ app.shop.initials }}</span>
                <span class="grow"><b>{{ app.shop.name }}</b><span>{{ app.shop.city }} · {{ app.pkg.name }}</span></span>
                <svg class="i s" viewBox="0 0 24 24" aria-hidden="true" style="color: #94a3b8"><path d="M8 9l4-4 4 4M8 15l4 4 4-4"></path></svg>
            </RouterLink>
            <nav class="nav" aria-label="Main">
                <template v-for="(g, gi) in groups" :key="gi">
                    <span v-if="g.head" class="navh" style="margin-top: 12px">{{ g.head }}</span>
                    <RouterLink v-for="item in g.items" :key="item.name" :to="item.to" :class="{ on: isOn(item) }">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path :d="ICON[item.icon]"></path></svg>
                        {{ item.label }}
                        <span v-if="item.count" class="ct">{{ item.count }}</span>
                    </RouterLink>
                </template>
            </nav>
            <span class="sp"></span>
            <RouterLink :to="{ name: 'settings' }" class="panel" style="padding: 12px; display: flex; flex-direction: column; gap: 8px; text-decoration: none; color: #0f172a">
                <span style="display: flex; justify-content: space-between; align-items: baseline">
                    <span class="lm">Credits</span>
                    <span class="mono"><b style="font-weight: 600">{{ app.creditsLeft }}</b><span class="muted"> / {{ app.creditsTotal }}</span></span>
                </span>
                <span class="bar-t"><i :style="{ width: creditsWidth, background: '#F2A007' }"></i></span>
                <span class="tx muted">Resets {{ app.resetsOn }} · wallet ₹{{ app.wallet.toLocaleString('en-IN') }}</span>
            </RouterLink>
            <div style="display: flex; align-items: center; gap: 10px; padding: 4px 6px">
                <span class="av">{{ app.user.initials }}</span>
                <span class="grow">
                    <span class="ts" style="display: block; font-weight: 500; line-height: 1.2">{{ app.user.name }}</span>
                    <span class="tx muted">{{ app.user.role }}</span>
                </span>
                <RouterLink class="btn bg ib sm" :to="{ name: 'settings' }" aria-label="Settings">
                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M16 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM10 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path></svg>
                </RouterLink>
            </div>
        </aside>

        <div class="main">
            <header class="bar">
                <span class="crumb">{{ app.shop.name }} <b>/ {{ title }}</b></span>
                <span class="sp"></span>
                <label class="search">
                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4"></path></svg>
                    <input placeholder="Search posts, customers, dates" aria-label="Search" />
                    <span class="kbd">⌘K</span>
                </label>
                <RouterLink class="btn ba" :to="{ name: 'create' }">
                    <svg class="i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a3 3 0 0 1 3 3v5a3 3 0 0 1-6 0V6a3 3 0 0 1 3-3zM5 11a7 7 0 0 0 14 0M12 18v3"></path></svg>
                    Bol ke batayein
                </RouterLink>
                <button class="btn bg ib" type="button" aria-label="Notifications">
                    <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15zM10 20.5a2 2 0 0 0 4 0"></path></svg>
                </button>
            </header>
            <RouterView :key="route.fullPath" />
        </div>
    </div>
</template>
