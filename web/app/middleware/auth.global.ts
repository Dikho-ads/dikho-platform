// Who can open what: marketing pages are open; /login and /signup are for signed-out people;
// /setup needs an account; /app/** needs an account whose shop has finished setup.
const PUBLIC = ['/', '/pricing'];
const GUEST = ['/login', '/signup'];

export default defineNuxtRouteMiddleware(async (to) => {
    const auth = useAuth();
    await auth.load();

    if (PUBLIC.includes(to.path)) return;

    if (GUEST.includes(to.path)) {
        if (!auth.signedIn) return;
        if (!auth.setupDone) return navigateTo('/setup');
        const next = typeof to.query.next === 'string' && to.query.next.startsWith('/app') ? to.query.next : '/app';
        return navigateTo(next);
    }

    if (!auth.signedIn) return navigateTo({ path: '/login', query: { next: to.fullPath } });

    if (to.path === '/setup') return;

    if (to.path.startsWith('/app') && !auth.setupDone) return navigateTo('/setup');
});
