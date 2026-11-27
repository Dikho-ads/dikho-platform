// Indian formatting helpers. Money from the API is always in paise.

export const num = (n: number | null | undefined) => (n == null ? '—' : Number(n).toLocaleString('en-IN'));

/** ₹ from paise. Whole rupees unless `paise: true`. */
export const inr = (paise: number | null | undefined, opts: { paise?: boolean } = {}) => {
    if (paise == null) return '—';
    const value = paise / 100;
    return '₹' + value.toLocaleString('en-IN', opts.paise ? { minimumFractionDigits: 2, maximumFractionDigits: 2 } : { maximumFractionDigits: 0 });
};

/** ₹ from whole rupees (for inputs that work in rupees). */
export const rupees = (n: number) => '₹' + Number(n).toLocaleString('en-IN');

export const pct = (n: number | null | undefined) => (n == null ? '—' : (n > 0 ? '+' : '') + n + '%');

const asDate = (v: string | Date) => (v instanceof Date ? new Date(v) : new Date(v.length === 10 ? v + 'T00:00:00' : v));

/** "26 Nov"; adds the year when it is not this year. */
export const day = (v: string | Date) => {
    const x = asDate(v);
    const sameYear = x.getFullYear() === new Date().getFullYear();
    return x.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', ...(sameYear ? {} : { year: 'numeric' }) });
};

/** "THU 26 NOV" eyebrow labels. */
export const eyebrowDate = (v: string | Date) => {
    const x = asDate(v);
    return (x.toLocaleDateString('en-IN', { weekday: 'short' }) + ' ' + x.getDate() + ' ' + x.toLocaleDateString('en-IN', { month: 'short' })).toUpperCase();
};

export const weekday = (v: string | Date, style: 'short' | 'narrow' | 'long' = 'short') => asDate(v).toLocaleDateString('en-IN', { weekday: style });

/** "7:30 PM". */
export const time = (v: string | Date) => asDate(v).toLocaleTimeString('en-IN', { hour: 'numeric', minute: '2-digit', hour12: true }).toUpperCase();

export const monthName = (v: string | Date, style: 'long' | 'short' = 'long') => asDate(v).toLocaleDateString('en-IN', { month: style });

/** "today", "tomorrow", "in 3 days", "2 days ago". */
export const relative = (v: string | Date) => {
    const a = new Date();
    a.setHours(0, 0, 0, 0);
    const b = asDate(v);
    b.setHours(0, 0, 0, 0);
    const days = Math.round((b.getTime() - a.getTime()) / 86400000);
    if (days === 0) return 'today';
    if (days === 1) return 'tomorrow';
    if (days === -1) return 'yesterday';
    return days > 0 ? `in ${days} days` : `${-days} days ago`;
};

/** Local date as YYYY-MM-DD (not UTC). */
export const isoDate = (v: Date = new Date()) => `${v.getFullYear()}-${String(v.getMonth() + 1).padStart(2, '0')}-${String(v.getDate()).padStart(2, '0')}`;

export const LANG_LABEL: Record<string, string> = { en: 'Hinglish', hi: 'हिन्दी', mr: 'मराठी' };

export const CHANNEL_LABEL: Record<string, string> = {
    whatsapp_status: 'WhatsApp Status',
    instagram: 'Instagram',
    facebook: 'Facebook',
    google: 'Google',
    broadcast: 'WhatsApp broadcast',
    meta_ad: 'Meta ad',
    walk_in: 'Walk-in (QR)',
};

/** Type dot class used by the design (.ty.post / .video / .camp / .ad / .free). */
export const typeDot = (type: string) =>
    (({ reel: 'video', carousel: 'post', post: 'post', status: 'free', google_post: 'post', ad: 'ad', whatsapp: 'camp', sms: 'camp', print: 'post', review_reply: 'free' }) as Record<string, string>)[type] ||
    'post';
