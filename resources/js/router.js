import { createRouter, createWebHistory } from 'vue-router';
import { useApp } from './stores/app';

const page = (title, name, path, load) => ({ path, name, component: load, meta: { title } });

const routes = [
    { path: '/', name: 'landing', component: () => import('./pages/Landing.vue'), meta: { public: true } },
    { path: '/pricing', name: 'pricing', component: () => import('./pages/Pricing.vue'), meta: { public: true } },
    { path: '/login', name: 'login', component: () => import('./pages/Login.vue'), meta: { public: true } },
    { path: '/setup', name: 'setup', component: () => import('./pages/Setup.vue') },
    {
        path: '/app',
        component: () => import('./layouts/AppShell.vue'),
        meta: { app: true },
        children: [
            page('Home', 'home', '', () => import('./pages/Home.vue')),
            page('Create', 'create', 'create', () => import('./pages/Create.vue')),
            page('Calendar', 'calendar', 'calendar', () => import('./pages/Calendar.vue')),
            page('Occasions', 'occasions', 'occasions', () => import('./pages/Occasions.vue')),
            page('Customers', 'customers', 'customers', () => import('./pages/Customers.vue')),
            page('Results', 'results', 'results', () => import('./pages/Results.vue')),
            page('Settings', 'settings', 'settings', () => import('./pages/Settings.vue')),
            page('Content Studio', 'studio', 'studio', () => import('./pages/studio/Overview.vue')),
            page('Auto mode', 'studio-auto', 'studio/auto', () => import('./pages/studio/Auto.vue')),
            page('Plan mode', 'studio-plan', 'studio/plan', () => import('./pages/studio/PlanBrief.vue')),
            page('Plan mode', 'studio-plan-result', 'studio/plan/result', () => import('./pages/studio/PlanResult.vue')),
            page('Editor', 'studio-editor', 'studio/editor', () => import('./pages/studio/Editor.vue')),
            page('Messages', 'studio-messages', 'studio/messages', () => import('./pages/studio/Messages.vue')),
            page('Workflows', 'studio-workflows', 'studio/workflows', () => import('./pages/studio/Workflows.vue')),
            page('Campaigns', 'studio-campaign', 'studio/campaigns', () => import('./pages/studio/Campaign.vue')),
        ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: (to, from, saved) => saved || { top: 0 },
});

router.beforeEach((to) => {
    const app = useApp();
    if (to.meta.public) return true;
    if (!app.authed) return { name: 'login', query: { next: to.fullPath } };
    if (to.matched.some((r) => r.meta.app) && !app.setupDone) return { name: 'setup' };
    return true;
});
