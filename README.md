# Dikho

**The World Should See You.** A marketing desk for every local shop: plan the month, make posts and reels in the shop's
language, run WhatsApp and Meta from one place, and see who got in touch.

This is the **demo frontend** (Laravel 13 + Vue 3). There is no backend or AI yet; every screen runs on demo data in the
browser. Product decisions live in [docs/PRODUCT.md](docs/PRODUCT.md).

## Run

```bash
composer install
npm install
npm run dev            # Vite, keep running
php artisan serve      # http://127.0.0.1:8000
```

Or build once and only run `php artisan serve`:

```bash
npm run build
```

Open `/`, then "Sign in" (any email and any code work). The demo date is fixed to 26 Nov 2026; demo state is saved in the
browser (`localStorage` key `dikho-demo-v1`); clear it to start over.

## Where things are

| Path | What |
|---|---|
| `resources/css/dikho.css` | The design system (colours, type, components, motion) |
| `resources/js/layouts/AppShell.vue` | Sidebar and top bar of the app |
| `resources/js/pages/` | Screens; `pages/studio/` is the Content Studio |
| `resources/js/components/ui/Logo.vue` | The Dikho "!" mark |
| `resources/js/stores/app.js` | Shared demo state (shop, owner, package, credits, wallet); each action maps to a future API endpoint |
