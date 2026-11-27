import tailwindcss from '@tailwindcss/vite';

// Dikho web: server-rendered Vue that talks to the Laravel JSON API (see docs/work/real-platform-build/API.md).
export default defineNuxtConfig({
    compatibilityDate: '2026-11-01',
    devtools: { enabled: false },
    modules: ['@pinia/nuxt'],
    css: ['~/assets/css/app.css'],
    vite: { plugins: [tailwindcss()] },
    runtimeConfig: {
        // Laravel API, called only from the Nuxt server (browser calls go through /api/*, which adds the
        // token from the httpOnly cookie). Override with NUXT_API_BASE.
        apiBase: 'http://127.0.0.1:8000/api/v1',
    },
    app: {
        head: {
            htmlAttrs: { lang: 'en' },
            title: 'Dikho',
            titleTemplate: '%s · Dikho',
            meta: [
                { name: 'viewport', content: 'width=device-width, initial-scale=1, viewport-fit=cover' },
                { name: 'theme-color', content: '#0D1129' },
                { name: 'description', content: 'Plan the month, make posts and reels in your language, and run WhatsApp and Meta from one place.' },
            ],
            link: [
                { rel: 'icon', type: 'image/png', href: '/images/logo_with_blue_Background.png' },
                { rel: 'apple-touch-icon', href: '/images/logo_with_blue_Background.png' },
                { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
                { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
                {
                    rel: 'stylesheet',
                    href: 'https://fonts.googleapis.com/css2?family=Anek+Devanagari:wdth,wght@75..125,400..800&family=Anek+Latin:wdth,wght@75..125,400..800&family=Geist+Mono:wght@400..600&family=Geist:wght@300..700&display=swap',
                },
            ],
        },
    },
});
