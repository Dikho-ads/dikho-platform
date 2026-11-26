// Demo state shared across screens. Each screen keeps its own demo content; only values that
// several screens show (shop, owner, package, credits, wallet) live here. Every action maps to a
// future API call (see docs/PRODUCT.md).

import { defineStore } from 'pinia';

// The demo runs on a fixed date so the sample content stays meaningful.
export const DEMO_TODAY = '2026-11-26';
const KEY = 'dikho-demo-v1';

export const PACKAGES = [
    { id: 'shuruaat', name: 'Shuruaat', price: 0, credits: 10, blurb: 'Free daily posts' },
    { id: 'dukaan', name: 'Dukaan', price: 199, credits: 150, blurb: '10 AI posts + 4 reels' },
    { id: 'badhao', name: 'Badhao', price: 499, credits: 400, blurb: 'Adds win-back + ads' },
    { id: 'dost', name: 'Dost', price: 1999, credits: 1000, blurb: 'With a human marketer' },
];
export const PKG_BY_ID = Object.fromEntries(PACKAGES.map((p) => [p.id, p]));

export const inr = (n) => '₹' + Number(n).toLocaleString('en-IN');

function fresh() {
    return {
        authed: false,
        setupDone: false,
        user: { name: 'Ramesh', initials: 'RP', role: 'Owner', email: '' },
        shop: {
            name: 'Sai Mobile Point',
            short: 'Sai Mobile',
            initials: 'SM',
            city: 'Nashik',
            area: 'College Road',
            category: 'Mobile shop',
            whatsapp: '+91 98765 43210',
        },
        packageId: 'dukaan',
        creditsUsed: 54,
        creditsExtra: 0, // bought top-up packs, on top of the package for this month
        wallet: 1240,
        resetsOn: '1 Dec',
    };
}

function load() {
    try {
        const raw = localStorage.getItem(KEY);
        return raw ? { ...fresh(), ...JSON.parse(raw) } : null;
    } catch {
        return null;
    }
}

export function save(state) {
    try {
        localStorage.setItem(KEY, JSON.stringify(state));
    } catch {
        // Private mode or blocked storage: the demo still works, it just forgets.
    }
}

export const useApp = defineStore('app', {
    state: () => load() || fresh(),

    getters: {
        today: () => DEMO_TODAY,
        pkg: (s) => PKG_BY_ID[s.packageId] || PKG_BY_ID.dukaan,
        creditsTotal() {
            return this.pkg.credits + this.creditsExtra;
        },
        creditsLeft() {
            return Math.max(0, this.creditsTotal - this.creditsUsed);
        },
        creditsPct() {
            return this.creditsTotal ? Math.round((this.creditsLeft / this.creditsTotal) * 100) : 0;
        },
    },

    actions: {
        // POST /api/auth/otp + /api/auth/verify
        login(email) {
            this.authed = true;
            if (email) this.user.email = email;
        },
        logout() {
            this.authed = false;
        },
        // PATCH /api/shop
        updateShop(patch) {
            Object.assign(this.shop, patch);
        },
        finishSetup(patch = {}) {
            if (patch.shop) this.updateShop(patch.shop);
            if (patch.packageId && PKG_BY_ID[patch.packageId]) this.packageId = patch.packageId;
            this.setupDone = true;
        },
        // POST /api/subscription/package
        setPackage(id) {
            if (PKG_BY_ID[id]) this.packageId = id;
        },
        // Spends credits if enough are left; returns whether it did.
        spend(n) {
            if (n > this.creditsLeft) return false;
            this.creditsUsed += n;
            return true;
        },
        refund(n) {
            this.creditsUsed = Math.max(0, this.creditsUsed - n);
        },
        // POST /api/credits/topup
        addCredits(n) {
            this.creditsExtra += n;
        },
        // POST /api/wallet/topup
        topUp(amount) {
            this.wallet += amount;
        },
        reset() {
            this.$patch(fresh());
        },
    },
});
