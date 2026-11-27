<script setup lang="ts">
// Settings › Shop & brand: GET shop (with products), PATCH shop with only the changed fields and
// inline field errors. Products are read-only here; they are edited in setup step 4.
const emit = defineEmits<{ status: [[string, string]] }>();
const api = useApi();
const auth = useAuth();
const toast = useToast();

// Awaited so the form below is filled before the server renders it.
const { data, pending, error, refresh } = await useApiData<{ data: any }>('shop', 'shop');
const shop = computed(() => data.value?.data);

const CATEGORIES: Record<string, string> = {
    mobile: 'Mobile & electronics',
    kirana: 'Kirana & general store',
    sweets: 'Sweets & farsan',
    salon: 'Salon & beauty',
    clinic: 'Clinic & pharmacy',
    coaching: 'Coaching & classes',
};
const DAYS: Record<string, string> = { mon: 'Monday', tue: 'Tuesday', wed: 'Wednesday', thu: 'Thursday', fri: 'Friday', sat: 'Saturday', sun: 'Sunday' };
const LANGS = ['en', 'hi', 'mr'];

const TEXT_FIELDS = ['name', 'name_local', 'category', 'city', 'area', 'address', 'landmark', 'pincode', 'whatsapp', 'phone', 'opens_at', 'closes_at', 'weekly_off', 'goal'] as const;
const NUM_FIELDS = ['since_year', 'delivery_km'] as const;

const form = reactive<Record<string, any>>({ languages: [], tagline: '' });
function reset() {
    const s = shop.value;
    if (!s) return;
    for (const f of TEXT_FIELDS) form[f] = s[f] ?? '';
    for (const f of NUM_FIELDS) form[f] = s[f] ?? '';
    form.languages = [...(s.languages ?? [])];
    form.tagline = s.brand?.tagline ?? '';
}
watch(shop, reset, { immediate: true });
watch(
    shop,
    (s) => {
        if (s) emit('status', s.setup_complete ? ['st ok', 'Set up'] : ['st warn', `Setup step ${s.setup_step} of 10`]);
    },
    { immediate: true },
);

const changes = computed(() => {
    const s = shop.value;
    if (!s) return {};
    const out: Record<string, any> = {};
    for (const f of TEXT_FIELDS) {
        const v = typeof form[f] === 'string' ? form[f].trim() : form[f];
        if ((v || null) !== (s[f] ?? null)) out[f] = v || null;
    }
    for (const f of NUM_FIELDS) {
        const v = form[f] === '' || form[f] == null ? null : Number(form[f]);
        if (v !== (s[f] ?? null)) out[f] = v;
    }
    if ([...form.languages].sort().join() !== [...(s.languages ?? [])].sort().join()) out.languages = LANGS.filter((l) => form.languages.includes(l));
    if ((form.tagline.trim() || null) !== (s.brand?.tagline || null)) out.brand = { ...(s.brand ?? {}), tagline: form.tagline.trim() || null };
    if (out.name === null) out.name = '';
    return out;
});
const dirty = computed(() => Object.keys(changes.value).length > 0);

function toggleLang(l: string) {
    form.languages = form.languages.includes(l) ? form.languages.filter((x: string) => x !== l) : [...form.languages, l];
}

const errs = ref<Record<string, string | undefined>>({});
const busy = ref(false);
async function save() {
    errs.value = {};
    if (!form.languages.length) return (errs.value = { languages: 'Pick at least one language.' });
    busy.value = true;
    try {
        const res = await api<{ data: any }>('shop', { method: 'PATCH', body: changes.value });
        data.value = res;
        if (auth.account) auth.set({ ...auth.account, shop: { ...auth.account.shop, ...res.data } });
        toast.ok('Shop details saved');
    } catch (e: any) {
        const keys = Object.keys(e.errors || {});
        if (keys.length) {
            errs.value = Object.fromEntries(keys.map((k) => [k.split('.')[0], e.field(k)]));
            toast.error('Check the highlighted fields');
        } else toast.error(e.message);
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="fade">
        <LoadState v-if="!shop" :pending="pending" :error="error" :rows="8" @retry="refresh" />
        <form v-else novalidate @submit.prevent="save">
            <div class="srow">
                <span style="min-width: 0">
                    <span class="lm">Shop</span>
                    <span class="h-sm" style="display: block; margin-top: 4px">{{ shop.name }}<template v-if="shop.name_local"> · {{ shop.name_local }}</template></span>
                    <span class="tx muted">
                        {{ shop.category_label }}{{ shop.area ? ' · ' + shop.area : '' }}{{ shop.landmark ? ', ' + shop.landmark.toLowerCase() : '' }}<template v-if="shop.opens_at && shop.closes_at"> · {{ time('2000-01-01T' + shop.opens_at) }} – {{ time('2000-01-01T' + shop.closes_at) }}</template>{{ shop.weekly_off ? ' · off ' + DAYS[shop.weekly_off] + 's' : '' }}
                    </span>
                </span>
                <span v-if="shop.rating" class="tag">★ {{ shop.rating }} · {{ num(shop.reviews_count) }} reviews</span>
            </div>

            <div class="grid">
                <div class="fld">
                    <label class="lab" for="s-name">Shop name</label><input id="s-name" v-model="form.name" class="in" maxlength="80" />
                    <span v-if="errs.name" class="err">{{ errs.name }}</span>
                </div>
                <div class="fld">
                    <label class="lab" for="s-local">Name in your language</label><input id="s-local" v-model="form.name_local" class="in" maxlength="80" lang="hi" />
                    <span v-if="errs.name_local" class="err">{{ errs.name_local }}</span>
                </div>
                <div class="fld">
                    <label class="lab" for="s-cat">What you sell</label>
                    <select id="s-cat" v-model="form.category" class="in"><option v-for="(l, k) in CATEGORIES" :key="k" :value="k">{{ l }}</option></select>
                    <span v-if="errs.category" class="err">{{ errs.category }}</span>
                </div>
                <div class="fld">
                    <label class="lab" for="s-since">Open since (year)</label><input id="s-since" v-model="form.since_year" class="in" type="number" min="1900" :max="new Date().getFullYear()" inputmode="numeric" />
                    <span v-if="errs.since_year" class="err">{{ errs.since_year }}</span>
                </div>
                <div class="fld">
                    <label class="lab" for="s-city">City</label><input id="s-city" v-model="form.city" class="in" maxlength="60" />
                    <span v-if="errs.city" class="err">{{ errs.city }}</span>
                </div>
                <div class="fld">
                    <label class="lab" for="s-area">Area</label><input id="s-area" v-model="form.area" class="in" maxlength="80" />
                    <span v-if="errs.area" class="err">{{ errs.area }}</span>
                </div>
                <div class="fld wide">
                    <label class="lab" for="s-addr">Address</label><input id="s-addr" v-model="form.address" class="in" maxlength="190" />
                    <span v-if="errs.address" class="err">{{ errs.address }}</span>
                </div>
                <div class="fld">
                    <label class="lab" for="s-lm">Landmark</label><input id="s-lm" v-model="form.landmark" class="in" maxlength="120" />
                    <span v-if="errs.landmark" class="err">{{ errs.landmark }}</span>
                </div>
                <div class="fld">
                    <label class="lab" for="s-pin">Pincode</label><input id="s-pin" v-model="form.pincode" class="in" inputmode="numeric" maxlength="6" />
                    <span v-if="errs.pincode" class="err">{{ errs.pincode }}</span>
                </div>
                <div class="fld">
                    <label class="lab" for="s-wa">WhatsApp number</label><input id="s-wa" v-model="form.whatsapp" class="in" inputmode="tel" maxlength="20" />
                    <span v-if="errs.whatsapp" class="err">{{ errs.whatsapp }}</span>
                </div>
                <div class="fld">
                    <label class="lab" for="s-ph">Shop phone</label><input id="s-ph" v-model="form.phone" class="in" inputmode="tel" maxlength="20" />
                    <span v-if="errs.phone" class="err">{{ errs.phone }}</span>
                </div>
                <div class="fld">
                    <span class="lab">Hours</span>
                    <span style="display: flex; gap: 8px; align-items: center">
                        <input v-model="form.opens_at" class="in" type="time" aria-label="Opens at" /><span class="muted">–</span><input v-model="form.closes_at" class="in" type="time" aria-label="Closes at" />
                    </span>
                    <span v-if="errs.opens_at || errs.closes_at" class="err">{{ errs.opens_at || errs.closes_at }}</span>
                </div>
                <div class="fld">
                    <label class="lab" for="s-off">Weekly off</label>
                    <select id="s-off" v-model="form.weekly_off" class="in">
                        <option value="">Open every day</option>
                        <option v-for="(l, k) in DAYS" :key="k" :value="k">{{ l }}</option>
                    </select>
                    <span v-if="errs.weekly_off" class="err">{{ errs.weekly_off }}</span>
                </div>
                <div class="fld">
                    <label class="lab" for="s-km">Delivery radius (km)</label><input id="s-km" v-model="form.delivery_km" class="in" type="number" min="0" max="50" inputmode="numeric" />
                    <span v-if="errs.delivery_km" class="err">{{ errs.delivery_km }}</span>
                </div>
                <div class="fld">
                    <label class="lab" for="s-goal">Main goal</label><input id="s-goal" v-model="form.goal" class="in" maxlength="60" />
                    <span v-if="errs.goal" class="err">{{ errs.goal }}</span>
                </div>
                <div class="fld wide">
                    <span class="lab">Languages for posts and messages</span>
                    <span class="chips">
                        <button v-for="l in LANGS" :key="l" type="button" :class="'chip' + (form.languages.includes(l) ? ' on' : '')" :aria-pressed="form.languages.includes(l)" :lang="l === 'en' ? 'en' : l" @click="toggleLang(l)">{{ LANG_LABEL[l] }}</button>
                    </span>
                    <span v-if="errs.languages" class="err">{{ errs.languages }}</span>
                </div>
            </div>

            <div class="srow">
                <span style="min-width: 0; display: flex; flex-direction: column; gap: 6px">
                    <label class="lm" for="s-tag">Tagline</label>
                    <input id="s-tag" v-model="form.tagline" class="in" maxlength="80" style="max-width: 420px" />
                    <span v-if="errs.brand" class="err">{{ errs.brand }}</span>
                </span>
                <span class="mono muted">{{ shop.brand?.feel || '' }}</span>
            </div>
            <div v-if="shop.brand?.colors?.length" class="srow">
                <span>
                    <span class="lm">Colours from the signboard</span>
                    <span style="display: flex; gap: 6px; margin-top: 8px">
                        <span v-for="c in shop.brand.colors" :key="c" :style="{ width: '28px', height: '28px', borderRadius: '7px', background: c, boxShadow: 'inset 0 0 0 1px #E2E8F0' }"></span>
                    </span>
                </span>
                <span class="mono muted" style="overflow-wrap: anywhere">{{ shop.brand.colors.join(' · ') }}</span>
            </div>
            <div class="srow">
                <span style="min-width: 0">
                    <span class="lm">Products</span>
                    <span v-if="shop.products?.length" class="prods">
                        <span v-for="p in shop.products" :key="p.id" class="tag" :class="{ dk: p.is_hero }">{{ p.name }}{{ p.offer ? ' · ' + p.offer : p.price_paise ? ' · ' + inr(p.price_paise) : '' }}</span>
                    </span>
                    <span v-else class="tx muted" style="display: block; margin-top: 4px">No products yet. Add what you sell so posts can show real prices.</span>
                </span>
                <NuxtLink class="btn bs sm" to="/setup?step=4">Edit products</NuxtLink>
            </div>

            <div class="savebar">
                <span class="tx muted">{{ dirty ? 'You have unsaved changes' : 'All saved' }}</span><span class="sp"></span>
                <button type="button" class="btn bg" :disabled="!dirty || busy" @click="reset(); errs = {}">Undo changes</button>
                <button type="submit" class="btn bp" :disabled="!dirty || busy">{{ busy ? 'Saving…' : 'Save' }}</button>
            </div>
        </form>
    </div>
</template>

<style scoped>
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(240px, 100%), 1fr));
    gap: 14px 18px;
    padding: 18px 22px;
    border-bottom: 1px solid #e2e8f0;
}
.grid .wide {
    grid-column: 1 / -1;
}
.prods {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}
.prods .tag {
    white-space: normal;
    height: auto;
    min-height: 22px;
    padding: 3px 7px;
}
.savebar {
    position: sticky;
    bottom: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    padding: 12px 22px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
}
@media (max-width: 899px) {
    .savebar {
        bottom: calc(64px + env(safe-area-inset-bottom));
    }
}
@media (max-width: 640px) {
    .grid {
        padding: 14px 16px;
    }
    .savebar {
        padding: 10px 16px;
    }
}
</style>
