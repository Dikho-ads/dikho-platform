<script setup>
// Dikho mark: an exclamation point whose dot is the one marigold accent.
defineProps({
    size: { type: Number, default: 24 },
    name: { type: Boolean, default: true },
    light: { type: Boolean, default: false },
});
</script>

<template>
    <span class="dk-logo">
        <svg :width="size" :height="size" viewBox="0 0 32 32" aria-hidden="true">
            <rect width="32" height="32" rx="8" :fill="light ? '#FFFFFF' : '#0F172A'" />
            <path d="M16 7.5v11" :stroke="light ? '#0F172A' : '#FFFFFF'" stroke-width="3.6" stroke-linecap="round" fill="none" />
            <circle cx="16" cy="24.2" r="2.4" fill="#F2A007" />
        </svg>
        <span v-if="name">Dikho</span>
    </span>
</template>

<style scoped>
.dk-logo {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}
.dk-logo > span {
    font: 600 16px/1 var(--f);
    letter-spacing: -0.02em;
}
</style>
