# Dikho

**The World Should See You.** A marketing desk for every local shop: plan the month, make posts and reels in the shop's
language, run WhatsApp and Meta from one place, and see who got in touch.

| Part | Stack | Where |
|---|---|---|
| Web app | Nuxt 4 (Vue 3, SSR, Pinia) | `web/` |
| API | Laravel 13, Sanctum bearer tokens | repo root (`app/`, `routes/api.php`) |
| Database | PostgreSQL 17 | migrations in `database/migrations` |

Product decisions: [docs/PRODUCT.md](docs/PRODUCT.md). API contract: [docs/work/real-platform-build/API.md](docs/work/real-platform-build/API.md).
Photos: [docs/PHOTO_SLOTS.md](docs/PHOTO_SLOTS.md).

## Run locally

Needs PHP 8.5 with `pdo_pgsql`, Composer, Node 24 and PostgreSQL 17 with a `dikho` database and role.

```bash
# API
composer install
cp .env.example .env          # fill DB_PASSWORD, then:
php artisan key:generate
php artisan migrate --seed    # packages, occasions, two demo shops
php artisan serve             # http://127.0.0.1:8000

# Web (second terminal)
cd web
npm install
npm run dev                   # http://localhost:3000
```

The browser only talks to Nuxt. Nuxt's server forwards `/api/*` to Laravel (`NUXT_API_BASE`, default
`http://127.0.0.1:8000/api/v1`) and keeps the session token in an httpOnly cookie.

### Demo accounts

| Email | Shop |
|---|---|
| `ramesh@saimobile.test` | Sai Mobile Point, College Road, Nashik · mobile · Dukaan |
| `sunita@kalesweets.test` | Kale Sweets, Sadashiv Peth, Pune · sweets · Badhao |

Sign-in is email + 6-digit code. With `APP_ENV=local` there is no mail server: the code is written to
`storage/logs/laravel.log` and also shown on the sign-in page. Or create a new account at `/signup`; a new shop goes
through setup and gets its own posts, named after the shop.

To start the demo data over: `php artisan migrate:fresh --seed` (deletes everything in the database).

## Tests

```bash
php artisan test --compact     # auth codes, setup, credit/wallet spending, shop isolation (SQLite in memory)
vendor/bin/pint --dirty        # PHP formatting
```

## Where things are

| Path | What |
|---|---|
| `app/Services/` | Credits and wallet ledgers, sign-in codes, copy writer and playbooks (en/hi/mr), starter content, plan builder, metrics |
| `app/Http/Controllers/Api/` | JSON endpoints; every one resolves the signed-in account's shop and never takes a shop id from input |
| `database/seeders/` | Packages, occasions (`data/occasions.json`), demo shops with 90 days of history |
| `web/app/layouts/app.vue` | Signed-in shell: sidebar (>= 1280 px), icon rail (900-1279), phone tabs (< 900) |
| `web/app/pages/` | Screens; `pages/app/studio/` is the Content Studio |
| `web/app/assets/css/dikho.css` | The design system |
| `web/server/` | Nuxt server routes: the API proxy and the session cookie |
| `resources/js/` | The old demo SPA, kept until the Nuxt app is signed off |

## Not built yet

- Payment gateway: credit and wallet top-ups are recorded with a generated UPI reference, nothing is charged.
- Posting to WhatsApp, Instagram, Facebook and Google: "Post now" records the post; channel linking stays "pending".
- AI writing and images: copy comes from the built-in playbooks; photos come from the slots above.
- Photo upload and customer import.
