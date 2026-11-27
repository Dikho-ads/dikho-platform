# Dikho: real platform build (Nuxt SSR + Laravel API + PostgreSQL)

Started 2026-11-27. The previous session stopped because it ran inside the TMBill project, whose permission rules deny
`php artisan migrate*` and `.env` access. Continue from a Claude Code session opened in `D:\Projects\prachar`.

## Owner decisions (2026-11-27)

| Topic | Decision |
|---|---|
| Goal | Make Dikho look and work like a real, incorporated product. Business owners use it without confusion. |
| Architecture | **Nuxt 4** frontend (Vue 3, SSR, Vue Router via `pages/`, Pinia) in `web/`; **Laravel 13** JSON API (Sanctum bearer tokens) in the repo root. |
| Database | **PostgreSQL 17**, fresh schema from Laravel migrations. Production later runs the same migrations. |
| Personalisation | Everything follows the signed-in account and its shop (name, owner, city, area, category, languages). No hard-coded "Sai Mobile" / "Ramesh" in pages. |
| Demo accounts | Seed real, working accounts (see Seeds). Sign-in is email + 6-digit code. |
| Photos | Reserved photo slots (see Photo slots). The owner adds real photos later; each slot shows its description until the file exists. No stock-site scraping (sites need API keys). |
| Delivery | Everything in one go (backend + Nuxt port of every screen + personalisation + photo slots), then one review. |
| Design | Keep the existing look: `resources/css/dikho.css` (from the Design canvas), responsive rules (>=1280 sidebar, 900-1279 rail, <900 Mobile v2 shell with bottom tabs). |

## Machine state (done)

- PostgreSQL 17.6 binaries at `C:\PostgreSQL17\pgsql`, cluster at `C:\PostgreSQL17\data`, port 5432, log `C:\PostgreSQL17\postgres.log`.
  Started with `pg_ctl` (not a service: registering needs admin). To make it a service, run in an **admin** prompt:
  `C:\PostgreSQL17\pgsql\bin\pg_ctl register -N postgresql-17 -D C:\PostgreSQL17\data -S auto` then `sc start postgresql-17`.
  Manual start: `C:\PostgreSQL17\pgsql\bin\pg_ctl -D C:\PostgreSQL17\data -l C:\PostgreSQL17\postgres.log start`.
- Superuser credentials: `C:\Users\RUSHIKESH\.dikho\postgres-superuser.txt`. App DB `dikho` / role `dikho`: credentials in
  `C:\Users\RUSHIKESH\.dikho\dikho-db.txt` (copy its `DB_*` lines into `.env`).
- `C:\php\php.ini`: `pdo_pgsql` and `pgsql` enabled (backup `C:\Users\RUSHIKESH\.dikho\php.ini.bak`).
- `laravel/sanctum` installed; config + `personal_access_tokens` migration published.

## Backend (in progress)

Written: `database/migrations/2026_11_27_100000_create_dikho_tables.php` (users gained `phone`, `locale`, nullable
`password`). Not yet migrated. Tables: login_codes, packages, shops, shop_members, subscriptions, credit_transactions,
wallet_transactions, products, occasions, plans, campaigns, content_items, customers, messages, workflows, daily_metrics,
connections, notifications_feed.

Next:
1. `.env`: DB_* from the file above, `MAIL_MAILER=log`, `APP_URL=http://127.0.0.1:8000`, `FRONTEND_URL=http://localhost:3000`.
2. `php artisan migrate`.
3. Models (`App\Models\*`) with relations and casts; `Shop` scoping: every controller resolves `$request->user()->shop`
   and never trusts a shop id from input.
4. Services: `CreditService` (grant/spend with row lock + ledger, balance = last `balance_after`), `WalletService`,
   `CopyWriter` (category x angle x language templates, en/hi/mr; port of the old `engine/copy.js` in
   `_backup/2026-11-26-phase1`), `StarterContent` (on setup complete: next 14 days of content from occasions + category
   playbook), `LoginCodeService` (6 digits, hashed, 10 min, 5 attempts, throttle; in `local` env the API also returns
   `debug_code` so the demo works without email).
5. API (`routes/api.php`, all JSON, `auth:sanctum` except auth + packages):
   - `POST /auth/request-code {email, purpose: login|signup, name?, phone?}`, `POST /auth/verify {email, code, purpose, name?, phone?}` -> `{token, user}`, `POST /auth/logout`, `GET /me`
   - `GET /packages`
   - `GET|PATCH /shop`, `POST /shop/setup/{step}`, `POST /shop/setup/complete`
   - `GET /dashboard` (greeting, KPIs this week vs last, ready posts, needs-you, chats per day, coming up)
   - `GET /content` (filters: status, type, from, to), `GET|PATCH /content/{id}`, `POST /content` (brief -> 3 variants, spends credits), `POST /content/{id}/approve|publish|schedule|skip`, `POST /content/approve-batch`
   - `GET /plans/{month}`, `POST /plans` (brief -> plan + items), `POST /plans/{id}/approve`
   - `GET /occasions?from&to`
   - `GET /campaigns`, `GET /campaigns/{id}`, `POST|PATCH /campaigns`
   - `GET /customers` (stage, q, paginated), `GET|PATCH /customers/{id}`, `POST /customers`, `POST /customers/win-back {ids}`
   - `GET /messages`, `POST /messages`, `POST /messages/{id}/schedule` (wallet cost: WhatsApp marketing 86 paise, SMS 25 paise per recipient)
   - `GET /workflows`, `PATCH /workflows/{id}`
   - `GET /results?range=week|last_week|month` (totals, per day, per channel, top pieces, best hook, ad money, win-back)
   - `GET /billing`, `POST /billing/package`, `POST /credits/topup`, `POST /wallet/topup`, `GET|POST /team`, `GET|PATCH /connections`, `GET /notifications`
   - `GET /studio` (business brain completeness, this month's mix, counts)
6. Seeders (realistic, internally consistent numbers; results derived from `daily_metrics` and `content_items`):
   - Packages: shuruaat ₹0/10 cr, dukaan ₹199/150, badhao ₹499/400, dost ₹1,999/1,000.
   - Occasions 2026-11 .. 2027-03 for India / Maharashtra / Nashik / Pune (from `_backup/.../data/occasions.js`).
   - Demo shop 1: owner Ramesh, Sai Mobile Point, Mobile & electronics, College Road, Nashik, since 2014, Dukaan plan,
     96 of 150 credits left, wallet ₹1,240, 212 customers (148 WhatsApp consent, 27 at risk, 14 lapsed), 90 days of
     metrics, last 60 days of posted content with metrics, 14 upcoming items, 3 ready today, campaign "Shaadi Exchange Mela",
     5 workflows, messages this week.
   - Demo shop 2: owner Sunita, Kale Sweets, Sweets, Sadashiv Peth, Pune, Badhao plan, own numbers.
   - Use plausible Maharashtrian names, `.test` email domains (never real domains), 10-digit numbers starting 9 with
     `00000` in the middle so they cannot be real (e.g. 98200 00147).
7. CORS for `http://localhost:3000`; rate limits on auth.

## Frontend (Nuxt 4 in `web/`)

- Port every current page from `resources/js/pages/**` and `layouts/AppShell.vue`; keep markup/classes, replace demo
  constants with API data via Pinia stores (`auth`, `shop`, `billing`, plus per-page `useAsyncData`/`$fetch` through one
  `useApi()` composable that adds the bearer token from an httpOnly-safe cookie on server and client).
- Routes: `/`, `/pricing`, `/login`, `/signup`, `/setup`, `/app/**` (same paths as today). Route middleware: guest-only
  for auth pages, `auth` + `setup-complete` for `/app/**`.
- Personalise every string that names the shop, owner, city, area, category, products, prices, languages.
- Loading skeletons, empty states, error toasts with retry, optimistic updates where safe, form validation messages,
  confirm dialogs for credit/wallet spends, Indian number formatting (`en-IN`), relative dates.
- `<Photo slot="...">` component: renders `/images/<slot>.jpg` if present, else a neutral placeholder that names the slot
  and describes the photo. Slot list with descriptions, aspect ratio and minimum size in `docs/PHOTO_SLOTS.md`;
  per-category slots (mobile, kirana, sweets, salon, clinic, coaching) so a new shop gets matching images.
- Keep the Laravel-served SPA only until the Nuxt app reaches parity, then remove `resources/js` (backed up in git).

## Checks before calling it done

- `php artisan test` smoke tests for auth + credit spend + tenant isolation (shop A cannot read shop B).
- Playwright pass: sign up -> setup -> dashboard as a new shop shows that shop's name/city everywhere; demo login works;
  no horizontal overflow at 1440/1366/1280/1100/1024/768/390; no console errors.
- Update `docs/PRODUCT.md`, `README.md` (run both servers, demo credentials).
