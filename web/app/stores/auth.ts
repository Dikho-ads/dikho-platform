import { defineStore } from 'pinia';

// The signed-in account as the API's AccountSummary returns it (GET /me): user, shop, package,
// credits, wallet and nav counts. Any API response that carries `account` refreshes it via set().
export type Account = {
    user: { id: number; name: string; first_name: string; initials: string; email: string; phone: string | null; role: string | null; role_label: string | null };
    shop: any | null;
    package: { id: string; name: string; price_paise: number; credits: number } | null;
    credits: { left: number; used: number; total: number; percent_left: number; resets_on: string } | null;
    wallet_paise: number;
    counts: { auto_pending: number; workflows_active: number; campaigns_live: number; notifications_unread: number } | null;
};

export const useAuth = defineStore('auth', {
    state: () => ({ account: null as Account | null, loaded: false }),

    getters: {
        signedIn: (s) => !!s.account,
        user: (s) => s.account?.user ?? null,
        shop: (s) => s.account?.shop ?? null,
        setupDone: (s) => !!s.account?.shop?.setup_complete,
    },

    actions: {
        set(account: Account | null | undefined) {
            if (account) this.account = account;
        },
        clear() {
            this.account = null;
        },
        async load(force = false) {
            if (this.loaded && !force) return this.account;
            try {
                this.account = await useApi()<Account>('me');
            } catch {
                this.account = null;
            }
            this.loaded = true;
            return this.account;
        },
        async requestCode(payload: { email: string; purpose: 'login' | 'signup'; name?: string; phone?: string }) {
            return useApi()<{ sent: boolean; email: string; expires_in: number; debug_code?: string }>('auth/request-code', { method: 'POST', body: payload });
        },
        async verify(payload: { email: string; code: string; purpose: 'login' | 'signup'; name?: string; phone?: string }) {
            const res = await useApi()<{ account: Account }>('auth/verify', { method: 'POST', body: payload });
            this.account = res.account;
            this.loaded = true;
            return res.account;
        },
        async logout() {
            await useApi()('auth/logout', { method: 'POST' }).catch(() => null);
            this.account = null;
            await navigateTo('/login');
        },
    },
});
