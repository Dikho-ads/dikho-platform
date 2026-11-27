<script setup lang="ts">
// Wraps a page section that loads data: skeleton while pending, a retry box on error, an empty
// message when there is nothing, else the default slot.
// <LoadState :pending="pending" :error="error" :empty="!data?.length" empty-text="No customers yet" @retry="refresh">…</LoadState>
withDefaults(defineProps<{ pending?: boolean; error?: any; empty?: boolean; emptyText?: string; emptyHint?: string; rows?: number }>(), {
    pending: false,
    error: null,
    empty: false,
    emptyText: 'Nothing here yet.',
    emptyHint: '',
    rows: 3,
});
defineEmits<{ retry: [] }>();
</script>

<template>
    <div v-if="pending" class="ls" aria-busy="true" aria-label="Loading">
        <span v-for="i in rows" :key="i" class="sk" :style="{ width: 100 - ((i * 17) % 40) + '%' }"></span>
    </div>
    <div v-else-if="error" class="ls ls--msg" role="alert">
        <b class="h-sm">Could not load this</b>
        <span class="tx muted">{{ error?.message || error?.data?.message || 'Check the internet and try again.' }}</span>
        <button type="button" class="btn bs sm" @click="$emit('retry')">Try again</button>
    </div>
    <div v-else-if="empty" class="ls ls--msg">
        <b class="h-sm">{{ emptyText }}</b>
        <span v-if="emptyHint" class="tx muted">{{ emptyHint }}</span>
        <slot name="empty" />
    </div>
    <slot v-else />
</template>

<style scoped>
.ls {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 18px;
}
.ls--msg {
    align-items: flex-start;
}
.sk {
    display: block;
    height: 14px;
    border-radius: 6px;
    background: linear-gradient(90deg, #eef2f7 25%, #f8fafc 50%, #eef2f7 75%);
    background-size: 200% 100%;
    animation: sk 1.2s ease-in-out infinite;
}
@keyframes sk {
    to {
        background-position: -200% 0;
    }
}
</style>
