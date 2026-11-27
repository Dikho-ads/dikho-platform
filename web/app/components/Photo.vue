<script setup lang="ts">
// A reserved photo slot. Shows /images/<slot>.jpg when the file exists; until then a neutral
// placeholder that names the slot and says what photo belongs there (see docs/PHOTO_SLOTS.md). Usage: <Photo name="mobile-owner" ratio="4:5" />.
import { PHOTO_SLOTS, describeSlot } from '~/utils/photoSlots';

const props = withDefaults(defineProps<{ name?: string | null; ratio?: string; label?: boolean; rounded?: boolean }>(), {
    name: null,
    ratio: '',
    label: true,
    rounded: true,
});

const failed = ref(false);
const loaded = ref(false);
const info = computed(() => (props.name ? PHOTO_SLOTS[props.name] ?? describeSlot(props.name) : null));
const src = computed(() => (props.name ? `/images/${props.name}.jpg` : null));
watch(
    () => props.name,
    () => {
        failed.value = false;
        loaded.value = false;
    },
);
</script>

<template>
    <div class="photo" :class="{ rounded }" :style="ratio ? { aspectRatio: ratio.replace(':', ' / ') } : {}">
        <ClientOnly>
            <img v-if="src && !failed" v-show="loaded" :src="src" :alt="info?.title || ''" loading="lazy" @load="loaded = true" @error="failed = true" />
        </ClientOnly>
        <div v-if="!loaded" class="photo__ph" :title="info ? `${name}: ${info.description}` : ''">
            <svg viewBox="0 0 24 24" aria-hidden="true" class="i s"><path d="M4 7h3l2-2.5h6L17 7h3v12H4zM12 16.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"></path></svg>
            <template v-if="label && info">
                <b>{{ info.title }}</b>
                <span>{{ info.description }}</span>
            </template>
        </div>
    </div>
</template>

<style scoped>
.photo {
    position: relative;
    overflow: hidden;
    background: #e2e8f0;
    width: 100%;
    height: 100%;
    min-height: 0;
}
.photo.rounded {
    border-radius: inherit;
}
.photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.photo__ph {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    padding: 6px;
    text-align: center;
    color: #64748b;
    background: repeating-linear-gradient(135deg, #e2e8f0 0 8px, #edf1f6 8px 16px);
    font: 500 10px/1.25 var(--f);
}
.photo__ph b {
    font-weight: 600;
    color: #334155;
}
.photo__ph span {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    max-width: 22ch;
}
</style>
