# Photo slots

Every photo in the web app sits in a named **slot**. Until a file exists, the slot shows a neutral placeholder that names
it and describes the photo. To fill a slot, save a JPG as `web/public/images/<slot>.jpg`; every screen that uses the slot
picks it up on the next load. No code change is needed.

The list lives in `web/app/utils/photoSlots.ts`; keep the two in step.

## Rules for every photo

- Real shops, real people, real products. No stock-site images (they need licences and look fake to customers).
- People who can be recognised must have agreed to appear. Customers quoted in posts need written consent.
- No deity images next to prices; no national flag in ads (Flag Code).
- JPG, sRGB, at least the minimum size below, under 600 KB after compression. Keep the subject centred: slots crop to
  their ratio.

## Shop slots (one set per category)

Categories: `mobile`, `kirana`, `sweets`, `salon`, `clinic`, `coaching`. Replace `<c>` with the category.

| Slot | Ratio | Min size | What to shoot |
|---|---|---|---|
| `<c>-front` | 4:3 | 1600×1200 | The shop from the street, signboard readable, daylight |
| `<c>-inside` | 4:3 | 1600×1200 | Inside: counter and shelves, lights on |
| `<c>-owner` | 4:5 | 1080×1350 | The owner smiling behind the counter, face clearly visible |
| `<c>-product` | 4:5 | 1080×1350 | The best seller, close up (mobile: 5G phone in hand; kirana: ration kit; sweets: modak or kaju katli; salon: finished haircut; clinic: consultation room; coaching: class at the board, with consent) |
| `<c>-product-1` … `<c>-product-5` | 1:1 | 1080×1080 | Catalogue products in setup order, plain background |

Content items carry `photo_slot` from the server (for example `mobile-owner` for trust posts and `mobile-product` for
offers), so filling these six slots per category makes most posts show real photos.

## Occasion slots

`occasion-<slug>`, 4:5, at least 1080×1350, one per occasion in `database/seeders/data/occasions.json` (for example
`occasion-wedding-season`, `occasion-makar-sankranti`). A real local scene: marigolds and a mandap for weddings, til-gul on
a plate for Sankranti. No clip art.

## Marketing and sign-in

| Slot | Ratio | Min size | What to shoot |
|---|---|---|---|
| `landing-hero` | 4:5 | 1200×1500 | A shop owner sharing a post from a phone at the counter |
| `landing-owner-1` | 1:1 | 800×800 | Mobile shop owner with phones on display |
| `landing-owner-2` | 1:1 | 800×800 | Sweet shop owner with trays of mithai |
| `landing-owner-3` | 1:1 | 800×800 | Kirana owner at a stocked counter |
| `login-billboard` | 3:4 | 1200×1600 | Evening market street with lit shop signboards |

Shops will later upload their own photos per slot from Setup; that upload API is not built yet.
