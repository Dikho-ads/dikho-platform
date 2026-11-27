// App-wide toasts and confirm dialogs. <AppToasts /> and <ConfirmDialog /> in app.vue render them.

type Toast = { id: number; text: string; tone: 'ok' | 'error' | 'info'; action?: { label: string; run: () => void } };

export function useToast() {
    const toasts = useState<Toast[]>('toasts', () => []);

    function show(text: string, opts: { tone?: Toast['tone']; action?: Toast['action']; ms?: number } = {}) {
        const id = Date.now() + Math.random();
        toasts.value = [...toasts.value.slice(-2), { id, text, tone: opts.tone ?? 'ok', action: opts.action }];
        if (import.meta.client) setTimeout(() => dismiss(id), opts.ms ?? (opts.action ? 6000 : 3600));
        return id;
    }

    function dismiss(id: number) {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    }

    return {
        toasts,
        ok: (text: string, action?: Toast['action']) => show(text, { tone: 'ok', action }),
        error: (text: string, action?: Toast['action']) => show(text, { tone: 'error', action, ms: 6000 }),
        info: (text: string) => show(text, { tone: 'info' }),
        dismiss,
    };
}

type ConfirmRequest = { title: string; body?: string; confirm?: string; cost?: string; danger?: boolean; resolve: (ok: boolean) => void };

/** await confirm({ title, body, cost }) -> true when the person agrees. Use before spending credits or wallet money. */
export function useConfirm() {
    const pending = useState<ConfirmRequest | null>('confirm', () => null);

    function confirm(opts: Omit<ConfirmRequest, 'resolve'>): Promise<boolean> {
        return new Promise((resolve) => {
            pending.value = { ...opts, resolve };
        });
    }

    function answer(ok: boolean) {
        pending.value?.resolve(ok);
        pending.value = null;
    }

    return { pending, confirm, answer };
}
