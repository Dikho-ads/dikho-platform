<script setup lang="ts">
const { pending, answer } = useConfirm();

function onKey(e: KeyboardEvent) {
    if (pending.value && e.key === 'Escape') answer(false);
}
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <template v-if="pending">
        <div class="scrim" style="z-index: 30" @click="answer(false)"></div>
        <div class="dlg" role="alertdialog" aria-modal="true" :aria-label="pending.title">
            <h2 class="h-md">{{ pending.title }}</h2>
            <p v-if="pending.body" class="ts muted">{{ pending.body }}</p>
            <div v-if="pending.cost" class="receipt" style="padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px">
                <div class="ln"><span class="muted">cost</span><span>{{ pending.cost }}</span></div>
            </div>
            <div style="display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap">
                <button type="button" class="btn bs" @click="answer(false)">Cancel</button>
                <button type="button" class="btn bp" :style="pending.danger ? 'background: #b91c1c' : ''" autofocus @click="answer(true)">{{ pending.confirm || 'Confirm' }}</button>
            </div>
        </div>
    </template>
</template>

<style scoped>
.dlg {
    position: fixed;
    z-index: 31;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: min(420px, calc(100vw - 32px));
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
}
</style>
