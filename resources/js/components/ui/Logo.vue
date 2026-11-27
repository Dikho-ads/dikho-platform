<script setup>
// Dikho logo, always drawn from the PNG artwork in resources/images:
//   logo1 - white D on navy (logo_with_blue_Background.png). Used everywhere for now.
//   logo2 - white D, no background (logo_without_Background.png).
// resources/images is not web-served: importing the files lets Vite bundle them and hand back a hashed URL.
// The artwork's D sits right of centre (about 27% empty on the left, 11% on the right), so the
// image is shifted and slightly enlarged inside a navy tile to centre the mark optically.
import { computed } from 'vue';
import logo1 from '@/../images/logo_with_blue_Background.png';
import logo2 from '@/../images/logo_without_Background.png';

const LOGO_FILES = { logo1, logo2 };

const props = defineProps({
    size: { type: Number, default: 32 },
    name: { type: Boolean, default: true },
    variant: { type: String, default: 'logo1', validator: (value) => ['logo1', 'logo2'].includes(value) },
});

const imageSrc = computed(() => LOGO_FILES[props.variant] ?? LOGO_FILES.logo1);
const hasBackground = computed(() => props.variant === 'logo1');
/** The wordmark scales with the mark so larger logos keep their proportions. */
const nameSize = computed(() => Math.max(16, Math.round(props.size * 0.6)));
</script>

<template>
    <span class="dk-logo" :style="{ '--logo-size': `${size}px`, '--name-size': `${nameSize}px` }">
        <span class="dk-logo__tile" :class="{ filled: hasBackground }">
            <img class="dk-logo__img" :src="imageSrc" :width="size" :height="size" alt="" aria-hidden="true" />
        </span>
        <span v-if="name" class="dk-logo__name">Dikho</span>
    </span>
</template>

<style scoped>
.dk-logo {
    display: inline-flex;
    align-items: center;
    gap: calc(var(--logo-size) * 0.32);
}

.dk-logo__tile {
    position: relative;
    flex-shrink: 0;
    width: var(--logo-size);
    height: var(--logo-size);
    overflow: hidden;
    border-radius: 24%;
}

.dk-logo__tile.filled {
    background: #0d1129;
    box-shadow: 0 1px 2px rgba(13, 17, 41, 0.18), 0 4px 10px -4px rgba(13, 17, 41, 0.35);
}

.dk-logo__img {
    display: block;
    width: 70%;
    height: 70%;
    /* Tuned by eye on the login screen: the mark sits inset in the navy tile with even breathing room. */
    transform: translate(17%, 19.9%) scale(1.1);
}

.dk-logo__name {
    font: 700 var(--name-size) / 1 var(--f);
    letter-spacing: -0.03em;
}
</style>
