<script setup lang="ts">
// A ContentItem drawn in the `.pst` post design: photo slot, hook, optional subline / terms,
// shop name footer. Used by Home, Create and the Editor. Spans only, so it can sit inside a button.
// <PostCard :item="item" style="font-size: 9.4px" />
const props = withDefaults(
    defineProps<{
        item: any;
        tag?: string;
        ratio?: string | null;
        photo?: 'top' | 'bottom' | 'none' | null;
        sub?: boolean;
        termsAs?: 'terms' | 'pr';
        foot?: string | null;
        footWa?: boolean;
        short?: boolean;
    }>(),
    { tag: 'div', ratio: null, photo: null, sub: false, termsAs: 'terms', foot: null, footWa: false, short: false },
);

const auth = useAuth();
const shop = computed(() => auth.shop);

const RATIO_CLASS: Record<string, string> = { '4:5': 'r45', '9:16': 'r916', '1:1': 'r11' };
const RATIO_STYLE: Record<string, string> = { '4:3': '4 / 3', A5: '148 / 210', '16:9': '16 / 9' };
const THEME_CLASS: Record<string, string> = { brand: '', light: 'w', dark: 'k', festive: 'y' };

const r = computed(() => props.ratio || props.item?.ratio || '4:5');
const cls = computed(() => ['pst', RATIO_CLASS[r.value] || '', THEME_CLASS[props.item?.theme] ?? ''].filter(Boolean).join(' '));
const aspect = computed(() => (RATIO_STYLE[r.value] ? { aspectRatio: RATIO_STYLE[r.value] } : {}));
// Trust posts lead with the photo on top; everything else puts the words first.
const where = computed(() => props.photo || (props.item?.angle === 'trust' || r.value === '9:16' ? 'top' : 'bottom'));
const lang = computed(() => props.item?.language || 'en');
const footText = computed(() => {
    if (props.foot != null) return props.foot;
    const s = shop.value;
    if (props.item?.angle === 'trust' && s?.rating) return `★ ${s.rating}` + (s.reviews_count ? ` · ${num(s.reviews_count)}` : '');
    if (props.item?.angle === 'local' && s?.area) return s.area;
    return s?.whatsapp ? 'WhatsApp' : '';
});
</script>

<template>
    <component :is="tag" :class="cls" :style="aspect">
        <slot name="over" />
        <span v-if="where === 'top' && item?.photo_slot" class="pi pc-photo"><Photo :name="item.photo_slot" :label="false" :rounded="false" /></span>
        <span class="pad" :style="where === 'bottom' ? 'flex: 1; justify-content: flex-end' : where === 'none' ? 'flex: 1; justify-content: center' : ''">
            <slot name="before" />
            <span class="hk" :lang="lang">{{ item?.hook }}</span>
            <span v-if="sub && item?.subline && !short" class="sub" :lang="lang">{{ item.subline }}</span>
            <span v-if="item?.terms" :class="termsAs" :lang="lang">{{ item.terms }}</span>
            <slot name="after" />
        </span>
        <span v-if="where === 'bottom' && item?.photo_slot" class="pi pc-photo" style="flex: 0 0 26%"><Photo :name="item.photo_slot" :label="false" :rounded="false" /></span>
        <span class="ft"><b>{{ short ? shop?.short_name || shop?.name : shop?.name }}</b><span v-if="footText" :class="footWa ? 'wa' : ''">{{ footText }}</span></span>
    </component>
</template>

<style scoped>
.pc-photo {
    padding: 0 !important;
    display: block !important;
    overflow: hidden;
}
.pst .ft b {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.pst .ft > span {
    flex-shrink: 0;
}
.pst .hk {
    overflow-wrap: anywhere;
}
</style>
