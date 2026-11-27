# Dikho API contract (Laravel `/api/v1`)

The Nuxt app calls these through its own server: `useApi()('dashboard')` → Nuxt `/api/dashboard` → Laravel
`/api/v1/dashboard` with the bearer token from the httpOnly cookie `dikho_token`. Pages never see the token.

Conventions
- Money is **paise** (`*_paise`, integers). Show with `inr(paise)` from `web/app/utils/format.ts`.
- Dates are `YYYY-MM-DD`; times are ISO 8601 with offset (Asia/Kolkata).
- Errors: `422 {message, errors:{field:[msg]}}`, `402 {message, code:'insufficient_credits'|'insufficient_wallet', needed, available}`,
  `409 {code:'no_shop'|'setup_incomplete'}`, `404`, `401`. In the web app these arrive as `ApiError` (`e.message`, `e.field('email')`, `e.code`).
- Lists returned by an Eloquent resource collection are wrapped: `{ data: [...] }`. A single resource returned on its own is wrapped
  too: `{ data: {...} }`. Objects and collections nested inside custom JSON responses (e.g. `dashboard.ready`, `item`, `plan`) are plain, not wrapped.
- Any response containing `account` carries the fresh AccountSummary; call `useAuth().set(res.account)` so the sidebar credits,
  wallet and counts update.

## AccountSummary (`GET /me`; also `account` in many responses)
```
{ user: {id, name, first_name, initials, email, phone, role, role_label},
  shop: Shop, package: {id, name, price_paise, credits},
  credits: {left, used, total, percent_left, resets_on},
  wallet_paise, counts: {auto_pending, workflows_active, campaigns_live, notifications_unread} }
```
Shop: `{id, name, name_local, short_name, initials, slug, category (mobile|kirana|sweets|salon|clinic|coaching), category_label, city, area,
address, landmark, pincode, whatsapp, phone, opens_at "HH:MM", closes_at, weekly_off (mon..sun|null), languages ['en'|'hi'|'mr'], since_year,
years, delivery_km, rating, reviews_count, goal, audience {types:[], hubs:[]}, brand {colors:[], tagline, feel}, logo_path, package_id,
setup_step, setup_complete, products?: [{id, name, price_paise, mrp_paise, offer, photo_slot, is_hero}]}`

Languages: `en` is shown as "Hinglish", `hi` "हिन्दी", `mr` "मराठी" (`LANG_LABEL`).

## Auth (no token)
- `POST auth/request-code {email, purpose:'login'|'signup', name?, phone? (10 digits)}` → `{sent, email, expires_in, debug_code?}`.
  `debug_code` exists only in local/testing: show it on the page as "Demo code: 123456" so the demo works without email.
  422 when login email has no account ("Create one instead") or signup email exists.
- `POST auth/verify {email, code, purpose, name?, phone?}` → `{account}` (Nuxt sets the cookie). Signup creates the user and a shop
  named "<Name>'s shop" with `setup_complete:false`.
- `POST auth/logout`. Use the store: `useAuth().requestCode()`, `.verify()`, `.logout()`.
- `GET packages` → `[{id, name, price_paise, credits, blurb, features:[]}]` (shuruaat 0/10, dukaan 19900/150, badhao 49900/400, dost 199900/1000).

## Shop & setup (token; works before setup is complete)
- `GET shop` → `{data: Shop}` with products. `PATCH shop {any Shop field except package_id}` → `{data: Shop}`.
- `POST shop/setup/{1..10}` with any Shop fields plus `products: [{name, price?: "₹1,200 – 4,500", price_paise?}]` → `{shop}`.
  Steps (Setup page): 1 basics (name, name_local, category, since_year), 2 location (city, area, address, landmark, pincode, delivery_km),
  3 contact (whatsapp, phone, opens_at, closes_at, weekly_off), 4 products, 5 audience ({types, hubs}, languages), 6 brand
  ({colors, tagline, feel}), 7 photos (nothing to send yet), 8 goal, 9 customers (optional, nothing to send yet), 10 package_id.
- `POST shop/setup/complete {package_id?}` → AccountSummary. Grants credits, makes 14 days of content, workflows, channel rows.

## Everything below needs a finished shop (409 setup_incomplete otherwise)

`GET dashboard` →
```
{ greeting: {name, date, city, summary},
  kpis: [{key:'views'|'chats'|'calls'|'qr_scans', value, previous, change(% or null)}],   // last 7 days vs the 7 before
  ready: ContentItem[] (today, status ready), posted_today,
  chats_per_day: [{date, views, chats, calls, qr_scans}] (7 days, oldest first),
  insights: {best_day, best_time, best_hook, cost_per_chat_paise},
  needs_you: [{kind:'plan'|'batch'|'winback'|'photos', title, detail, badge, link, credits?, items?: ContentItem[]}],
  coming_up: Occasion[] (with planned_items) }
```

ContentItem:
```
{id, type (post|status|carousel|reel|ad|whatsapp|google_post|print|sms|review_reply), type_label, angle (offer|trust|local|newness|festival|tip),
 stage (awareness|trust|buying|return), language, language_label, title, hook, subline, caption, cta, terms, ratio ('4:5'|'9:16'|'1:1'|'4:3'|'A5'),
 theme (brand|light|dark|festive), photo_slot, status (draft|ready|approved|scheduled|posted|skipped), source (auto|plan|manual),
 scheduled_at, posted_at, channels [], credits, trust_score, trust_checks [{key,label,ok,tip}], why,
 metrics {views, chats, calls, qr_scans}, plan_id, campaign_id, occasion? {id, slug, name}, product? {id, name}}
```
- `GET content?status=a,b&type=&source=auto|plan|manual&from=YYYY-MM-DD&to=&plan_id=&campaign_id=&limit=` → `{data: ContentItem[]}` by scheduled_at.
- `GET content/{id}` → `{data}`; `PATCH content/{id} {title, hook, subline, caption, cta, terms, language, ratio, theme, photo_slot, scheduled_at, channels}` → `{data}` (trust check recomputed).
- `POST content {type, language?, angle?, offer?, occasion_id?, product_id?, brief?, scheduled_at?}` → `201 {item, variants:[{key:'A'|'B'|'C', angle, language, hook, subline, caption, cta, terms, why, trust_score, trust_checks}], account}`.
  Spends the format's credits (post 2, status 2, carousel 5, reel 15, ad 6, whatsapp 3, google_post 2, print 4, sms 1). Picking another variant = PATCH the item with that variant's text (free).
- `POST content/{id}/approve` | `schedule {scheduled_at}` | `publish {channels?}` | `unpublish` (within 15 min) | `skip` → `{item, account}`.
  Credits are charged once per item, on the first of approve/schedule/publish; skip refunds. 402 if not enough credits.
- `POST content/approve-batch {ids:[]}` → `{items: ContentItem[], account}`.

Plans:
- `GET plans/{YYYY-MM}` → `{plan: Plan|null}`; `GET plans/id/{id}` → `{data: Plan}`.
- `POST plans {month?, goal?, goal_label?, current_enquiries?, target?, audience?, areas?, languages? ['en','hi','mr'], products?, occasions?, channels?, tone?, credits?, wallet_paise?, notes?}` → `{data: Plan}` (status review, items as drafts; costs nothing yet).
- `POST plans/{id}/approve` → `{plan, account}`; spends the plan's credits once, schedules every draft item.
- Plan: `{id, month 'YYYY-MM', status, brief, strategy: {goal {label,target,today}, kpis [{label,value,note}], reasons [{title,detail}], funnel [{key,name,label,share,count,formats}], wallet_split {meta_ads_paise, whatsapp_paise, sms_paise}|null}, credits_budget, wallet_budget_paise, version, approved_at, items: ContentItem[]}`

Occasions: `GET occasions?from&to` (default today → +4 months) → `{data: [{id, slug, name, name_hi, name_mr, kind (festival|local|season|national|moment), region, starts_on, ends_on, days_away, relevance (0..1 for this shop's category), rule, photo_slot, planned_items}]}`.

Campaigns: `GET campaigns` → `{data: Campaign[]}` (live first); `GET campaigns/{id}` → `{data}` with items + messages; `POST campaigns {name, starts_on, ends_on, goal?, offer?, occasion_id?, budget_paise?}`; `PATCH campaigns/{id}` (+status).
Campaign: `{id, name, goal, offer, status (draft|scheduled|live|ended), starts_on, ends_on, days, day, budget_paise, spent_paise, funnel {reach, enquiries, visits, sales}, cost_per_enquiry_paise, occasion?, items?, messages?}`

Customers:
- `GET customers?stage=new|regular|at_risk|lapsed&q=&consent=1&per_page=&page=` → `{data: Customer[], meta {current_page,last_page,total,per_page}, summary {total,new,regular,at_risk,lapsed,whatsapp_consent,birthdays_this_month}}`.
- Customer: `{id, name, initials, phone, area, gender, birthday, whatsapp_consent, consent_at, stage, visits, spend_paise, first_visit_on, last_visit_on, days_since_visit, last_purchase, source, photo_slot, notes}`.
- `POST customers {name, phone (10 digits), area?, gender?, birthday?, whatsapp_consent?, source?, notes?}`; `PATCH customers/{id}`; `GET customers/{id}`.
- `POST customers/win-back {ids:[], offer?}` → `{message_id, sent_to, skipped_no_consent, cost_paise, account}`; wallet pays 86 paise per WhatsApp recipient (402 insufficient_wallet).

Messages: `GET messages` → `{data: Message[], audiences: [{key: all|regular|new|at_risk|lapsed, label, whatsapp, sms}], rates_paise {whatsapp:86, sms:25}}`;
`POST messages {channel: whatsapp|sms, name, body, template?, language?, buttons?, audience? {stage}, campaign_id?}` → `201 {data}` (recipients + cost computed; only consented people for WhatsApp);
`PATCH messages/{id}` (drafts); `POST messages/{id}/schedule {scheduled_at?}` → `{data, account}` (wallet charged now).
Message: `{id, channel, name, template, language, body, buttons, audience, status (draft|scheduled|sending|sent), scheduled_at, sent_at, recipients, delivered, read, replies, cost_paise, campaign_id}`.

Workflows: `GET workflows` → `{data: [{id, name, trigger, description, steps [{after:'3d', action:'whatsapp'|'sms', text}], active, sent_count, reply_count, last_run_at}]}`; `PATCH workflows/{id} {active?, name?, steps?}`.

Results: `GET results?range=week|last_week|month` →
`{range {key, from, to, days}, totals {views|chats|calls|qr_scans|offer_codes|ad_spend_paise: {value, previous, change}}, per_day [...], per_channel [{channel, views, chats, calls, qr_scans, ad_spend_paise}], top: ContentItem[], insights {...}, ads {spend_paise, chats}, win_back {sent, replies, cost_paise}, posted}`.

Billing: `GET billing` → `{account, subscription {package_id, status, autopay, autopay_upi, period_start, period_end, next_package_id}, packages [...], credit_packs [{credits, price_paise}], credit_history [{id, delta, balance_after, reason, description, created_at}], wallet_history [{id, amount_paise, balance_after_paise, reason, description, reference, created_at}]}`.
`POST billing/package {package_id}` (upgrade now + extra credits; downgrade at renewal) → same as GET billing. `POST credits/topup {credits: 50|150|400}` → `{account}`. `POST wallet/topup {amount_paise (10000..5000000)}` → `{account}`.
No payment gateway is wired yet: top-ups are recorded with a generated UPI reference. Say "Paid by UPI" in the UI, and confirm before spending.

Team & channels: `GET team` → `{data: [{id, name, phone, role, permissions, invited_at, joined_at}]}`; `POST team {name, phone, role: manager|staff}` (owner only).
`GET connections` → `{data: [{id, provider (whatsapp|instagram|facebook|google|meta_ads|upi), status (connected|pending|not_linked), account, connected_at}]}`; `PATCH connections/{provider} {account?, disconnect?}`.
Notifications: `GET notifications` → `{data, unread}`; `POST notifications/read`.

Studio: `GET studio` → `{brain [{key,label,done,link}], brain_percent, month {label,total,posted,by_type [{type,label,count}], by_stage {}}, counts {auto_waiting, drafts, scheduled, messages, workflows_active, workflows, campaigns_live}, formats [{type,label,credits,ratio}], languages [{code,label}]}`.

## Demo accounts (local)
- `ramesh@saimobile.test` — Sai Mobile Point, College Road, Nashik (mobile, Dukaan).
- `sunita@kalesweets.test` — Kale Sweets, Sadashiv Peth, Pune (sweets, Badhao).
Sign in with the email; the 6-digit code is shown on the page in local mode.
