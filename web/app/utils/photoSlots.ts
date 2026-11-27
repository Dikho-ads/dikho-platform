// Reserved photo slots. Drop a JPG named <slot>.jpg into web/public/images/ and every place that
// uses the slot shows it. Keep this list in step with docs/PHOTO_SLOTS.md.

export type SlotInfo = { title: string; description: string; ratio: string; min: string };

const CATEGORY_NAMES: Record<string, string> = {
    mobile: 'mobile shop',
    kirana: 'kirana store',
    sweets: 'sweet shop',
    salon: 'salon',
    clinic: 'clinic',
    coaching: 'coaching centre',
};

const BEST_SELLER: Record<string, string> = {
    mobile: 'a 5G phone held in hand at the counter',
    kirana: 'a monthly ration kit bag, items visible',
    sweets: 'a plate of fresh modak or kaju katli, close up',
    salon: 'a finished haircut, side profile, in the salon chair',
    clinic: 'the consultation room, clean and well lit',
    coaching: 'a class in progress, students facing the board (consent needed)',
};

function categorySlots(category: string): Record<string, SlotInfo> {
    const shop = CATEGORY_NAMES[category];
    return {
        [`${category}-front`]: { title: 'Shop front', description: `The ${shop} from the street, signboard readable, in daylight`, ratio: '4:3', min: '1600×1200' },
        [`${category}-inside`]: { title: 'Inside', description: `Inside the ${shop}: counter and shelves, lights on`, ratio: '4:3', min: '1600×1200' },
        [`${category}-owner`]: { title: 'Owner at the counter', description: 'The owner smiling behind the counter, face clearly visible', ratio: '4:5', min: '1080×1350' },
        [`${category}-product`]: { title: 'Best seller', description: BEST_SELLER[category] ?? 'Your best-selling product, close up', ratio: '4:5', min: '1080×1350' },
        ...Object.fromEntries(
            [1, 2, 3, 4, 5].map((n) => [
                `${category}-product-${n}`,
                { title: `Product ${n}`, description: `Product ${n} from your catalogue, on a plain background`, ratio: '1:1', min: '1080×1080' },
            ]),
        ),
    };
}

export const PHOTO_SLOTS: Record<string, SlotInfo> = {
    ...Object.keys(CATEGORY_NAMES).reduce((all, c) => ({ ...all, ...categorySlots(c) }), {} as Record<string, SlotInfo>),
    'landing-hero': { title: 'Hero', description: 'A shop owner in India sharing a post from a phone at the counter', ratio: '4:5', min: '1200×1500' },
    'landing-owner-1': { title: 'Owner story 1', description: 'Mobile shop owner with phones on display', ratio: '1:1', min: '800×800' },
    'landing-owner-2': { title: 'Owner story 2', description: 'Sweet shop owner with trays of mithai', ratio: '1:1', min: '800×800' },
    'landing-owner-3': { title: 'Owner story 3', description: 'Kirana owner at a stocked counter', ratio: '1:1', min: '800×800' },
    'login-billboard': { title: 'Sign-in panel', description: 'Evening market street with lit shop signboards', ratio: '3:4', min: '1200×1600' },
};

/** Fallback for slots not in the list, e.g. occasion-<slug> or customer photos. */
export function describeSlot(slot: string): SlotInfo {
    if (slot.startsWith('occasion-')) {
        const name = slot.slice(9).replace(/-/g, ' ');
        return { title: 'Occasion', description: `A local, real scene for ${name}: no stock clip art, no deity next to prices`, ratio: '4:5', min: '1080×1350' };
    }
    return { title: 'Photo', description: slot.replace(/-/g, ' '), ratio: '4:5', min: '1080×1350' };
}
