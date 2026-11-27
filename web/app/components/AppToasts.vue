<script setup lang="ts">
const { toasts, dismiss } = useToast();
</script>

<template>
    <div class="toasts" aria-live="polite">
        <div v-for="t in toasts" :key="t.id" class="toast" :role="t.tone === 'error' ? 'alert' : 'status'">
            <span v-if="t.tone === 'ok'" class="st ok" style="color: #fff">Done</span>
            <span v-else-if="t.tone === 'error'" class="st" style="color: #fca5a5">Problem</span>
            <span>{{ t.text }}</span>
            <template v-if="t.action">
                <span style="width: 1px; height: 18px; background: #334155"></span>
                <button
                    type="button"
                    class="lnk"
                    style="color: #f2a007; text-decoration-color: #f2a007"
                    @click="
                        t.action.run();
                        dismiss(t.id);
                    "
                >
                    {{ t.action.label }}
                </button>
            </template>
            <button type="button" class="toast__x" aria-label="Dismiss" @click="dismiss(t.id)">×</button>
        </div>
    </div>
</template>

<style scoped>
.toasts {
    position: fixed;
    left: 50%;
    bottom: 24px;
    transform: translateX(-50%);
    z-index: 40;
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: center;
    width: max-content;
    max-width: calc(100vw - 32px);
}
.toasts .toast {
    position: static;
    transform: none;
}
.toast__x {
    background: none;
    border: 0;
    color: #94a3b8;
    font-size: 18px;
    cursor: pointer;
    padding: 0 0 0 4px;
}
@media (max-width: 899px) {
    .toasts {
        bottom: calc(84px + env(safe-area-inset-bottom));
    }
}
</style>
