# Dikho - product notes (demo frontend)

Name: **Dikho**. Logo: an exclamation mark whose dot is the marigold accent. Tagline: **The World Should See You**.
Use only "Dikho" in the product, code and docs (earlier working names are retired).
Sources: "Bharat-First AI Marketing Platform - Build & Business Plan" (19 Nov 2026) and the Design canvas
https://claude.ai/artifact/UMe5S55PHPxhhQQ3gacECe, page "Web platform" (the screens below are built from it).

## 1. What we sell

A marketing desk for every local shop: plan the month, make posts and reels in the shop's language, run WhatsApp and Meta
from one place, and see who actually got in touch. Subscriptions are **monthly packages of credits**; each piece of work
costs a fixed number of credits, and message fees come from a prepaid **wallet**.

| Package | Price / month | Credits / month | In short |
|---|---|---|---|
| Shuruaat | ₹0 | 10 | Free daily posts |
| Dukaan | ₹199 | 150 | 10 AI posts + 4 reels |
| Badhao | ₹499 | 400 | Adds win-back + ads |
| Dost | ₹1,999 | 1,000 | With a human marketer |

Credits reset on the 1st; up to 25% roll over. Sample costs from the design: reel 15 cr, post 6 cr, ad 20 cr, win-back 10 cr.
Packages and credit maths live in `resources/js/stores/app.js` (`PACKAGES`, `spend`, `creditsLeft`).

Every creative still follows the lead formula (section 4). We never promise a count of leads (ASCI, and honesty).

## 2. Decisions

| Date | Topic | Decision |
|---|---|---|
| 2026-11-19 | Location | Separate project, `D:\Projects\prachar`, not inside TMBill |
| 2026-11-19 | Stack | Laravel 13 + Vue 3 SPA (Pinia, Vue Router, Tailwind 4) in one repo; Sanctum JSON API later |
| 2026-11-19 | AI | Our own AI backend services (RunPod Serverless or fal.ai, still being chosen), built as a **separate project**. This app only calls our API |
| 2026-11-26 | Name | **Dikho**, "!" logo, tagline "The World Should See You" |
| 2026-11-26 | Design | The web platform follows the Design canvas (slate + one marigold accent, Geist / Geist Mono / Anek). Its stylesheet is `resources/css/dikho.css` |
| 2026-11-26 | Model | The design wins over the 2026-11-19 rules: **credits + wallet** replace slot-only plans, and a **Results** screen counts chats, calls and QR scans (replaces "no lead tracking") |
| 2026-11-26 | This phase | Demo frontend: no backend, no AI calls, demo data in the browser. The Phase 1 screens are backed up in `_backup/2026-11-26-phase1/` |
| 2026-11-26 | App | Web first (laptop layout, 1280-1440). The mobile app is a later project on the same API |

## 3. Screens

| Route | Screen (design board) |
|---|---|
| `/` | Landing, restyled to the new look |
| `/pricing` | Packages and what credits buy |
| `/login` | Sign in (w-Login) |
| `/setup` | Shop setup, 10 steps with a live preview (w-Setup) |
| `/app` | Dashboard: today's ready posts, needs you, this week, coming up (w-Home) |
| `/app/create` | Create studio: brief, 3 versions, trust check, publish (w-Create) |
| `/app/calendar` | Monthly plan calendar with credit receipt (w-Plan) |
| `/app/occasions` | Festivals and local events (w-Campaigns) |
| `/app/customers` | Lifecycle, consent, win-back (w-Customers) |
| `/app/results` | Chats, calls, QR (w-Results) |
| `/app/settings` | Plan, credits, wallet, team, connections (w-Settings) |
| `/app/studio` | Content Studio overview (w-Studio) |
| `/app/studio/auto` | Auto mode (w-Auto) |
| `/app/studio/plan`, `/app/studio/plan/result` | Plan mode brief and generated plan (w-PlanBrief, w-PlanResult) |
| `/app/studio/editor` | Content editor, one idea in 8 formats (w-Editor) |
| `/app/studio/messages` | WhatsApp and SMS messages (w-Messages) |
| `/app/studio/workflows` | Workflows (w-Workflows) |
| `/app/studio/campaigns` | Campaign detail (w-CampaignDetail) |

Shared shell: `resources/js/layouts/AppShell.vue` (sidebar, top bar). Shared demo state: shop, owner, package, credits,
wallet in `stores/app.js`, saved to localStorage (`dikho-demo-v1`). Demo date: 26 Nov 2026. Each screen keeps its own
demo content.

## 4. The lead formula (every creative)

Hook -> Offer -> Trust -> WhatsApp + number -> Deadline. The **Lead-ready check** (7 items) shows on every piece:
short hook, clear offer, number, deadline, shop name + place, customer language, readable second line.

## 5. Regional touch in the MVP

- Occasion calendar 19 Nov 2026 - 31 Jan 2027 for Maharashtra / Nashik (dates checked against panchang sources; regional
  tithi can shift a day).
- Cultural rules per occasion: bhandara yellow for Champa Shashti, black-and-gold Sankranti, no deity images next to
  prices, Flag Code (tricolour colours only, never the flag), no snow at Christmas, no alcohol on New Year's Eve.
- Copy written natively in Marathi, Hindi, English (`data/playbooks.js`, `data/occasions.js`, `data/i18n.js`).
  Have a native speaker review it before real use.
- Story formats keep the top 150px and bottom 170px clear of WhatsApp / Instagram overlays.
- Local pulse: Nashik Simhastha Kumbh 2027 (first Amrit Snan 2 Aug 2027).

## 6. Known gaps / next decisions

- Laptop-first: the sidebar stacks on top below 800 px; a proper phone layout is the mobile app's job.
- Backend: every store action maps to a future endpoint (auth, shop, package, credits spend, wallet top-up).
- Native-speaker review of all Marathi / Hindi copy.
- AI service contract (inputs, outputs, batching) to be written with the RunPod / fal.ai decision.

## Appendix: Phase 1 planner rules (2026-11-19, kept for the backend planning service)

The Phase 1 demo built a month plan from fixed-cost slots. That UI is retired, but its rules are the starting point for
Plan mode's backend.

### How a plan is built

**Slot** = one deliverable of a fixed type. Each type has a fixed internal cost in units.

| Slot type | What the owner gets | Units |
|---|---|---|
| Festival post | Greeting + offer, 1:1 post + 9:16 status | 1 |
| Offer post | Weekly offer from the category playbook | 1 |
| Campaign | 3 pieces: teaser, offer day, last call | 4 |
| Ad set | 1:1, 4:5, 9:16 + ad text + boost guide | 3 |
| Flyer | A5 pamphlet with the month's top offers | 2 |
| Banner | 2:1 shop flex for the big occasion | 2 |

**Packages** (prices incl. GST, from the plan PDF; lead-tracking features removed):

| Package | Price | Recipe per month | Units |
|---|---|---|---|
| Shuruaat | ₹0 | 4 festival posts, 1 language, "Made with" mark | 4 |
| Dukaan | ₹199 | 6 festival, 4 offer, 1 campaign, 1 ad set, 1 flyer | 19 |
| Badhao | ₹499 | 8 festival, 8 offer, 2 campaigns, 2 ad sets, flyer, banner | 34 |
| Marketing Dost | ₹1,999 | 10 festival, 12 offer, 3 campaigns, 4 ad sets, 2 flyers, banner + human marketer | 52 |
| Tyohaar Pack | ₹49 / festival | 1 campaign, 2 festival posts, 1 banner | 8 |

**Planner rules** (Phase 1 code: `_backup/2026-11-26-phase1/resources/js/engine/planner.js`; to move to the backend):

1. Window = the month, or join date to month end (pro-rated recipe).
2. Occasions are filtered by scope (India / state / district) and scored per category (`weight`).
3. Festival posts go on the day itself; a season is greeted only when it starts in the window.
4. Campaigns go to sale-heavy occasions first: teaser 4 days before, offer 2 days before, last call on the day
   (people shop before a festival). A campaign needs 3 distinct days inside the window.
5. Ad sets ride on campaign offer days, else salary week.
6. Offer posts: salary week first (1st-5th), then Thursdays and Sundays, then spread.
7. Banner 7 days before the biggest occasion; flyer in week one.
8. Fewer occasions than festival slots: the spare slots become offer posts of equal value, with a note.
9. Every slot carries a plain-language `why`.

**Lifecycle**: next month's plan is ready on the 18th, review by the 25th, auto-approved on the 26th. Each day's
pieces appear on Today.

### Customisation that never changes our cost

| Change | Cost to us | Rule |
|---|---|---|
| Text, offer, price, deadline | Re-render only | Free, unlimited |
| Language | All languages written at plan time (batch) | Free |
| Hook style | The 3 variants are made at plan time | Free |
| Template, colours, product picture | Re-render only | Free |
| Date, which festival a post is for | Festival scenes are pre-made once for all shops | Free |
| New AI design | AI scene + copy | Fixed per slot (`aiRegens`, 2-3) |
| Add a slot | - | Not allowed |
| Change a slot's type | - | Equal-value swap only (`SWAPS`), e.g. 1 campaign = 4 offer posts |

So cost per subscriber is known in advance: units x unit cost + a capped number of AI redesigns.

