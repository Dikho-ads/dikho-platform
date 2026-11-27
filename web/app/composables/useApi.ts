// One way to call the API from any page or store. Requests go to the Nuxt server (/api/*), which
// adds the session's token and forwards to Laravel. Errors come back as ApiError with the server's
// own message, field errors and code (e.g. insufficient_credits).

export class ApiError extends Error {
    constructor(
        message: string,
        public status: number,
        public errors: Record<string, string[]> = {},
        public code?: string,
        public data?: any,
    ) {
        super(message);
    }

    /** First message for a field, for showing under an input. */
    field(name: string): string | undefined {
        return this.errors[name]?.[0];
    }
}

export function toApiError(error: any): ApiError {
    if (error instanceof ApiError) return error;
    const status = error?.response?.status ?? error?.statusCode ?? 0;
    const data = error?.data ?? {};
    const message =
        status === 0
            ? 'You seem to be offline. Check the internet and try again.'
            : data.message || (status >= 500 ? 'Something went wrong on our side. Try again in a moment.' : 'That did not work. Try again.');
    return new ApiError(message, status, data.errors || {}, data.code, data);
}

type Options = { method?: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE'; body?: any; query?: Record<string, any> };

export function useApi() {
    // On the server, useRequestFetch forwards the visitor's cookies to our own /api routes.
    const fetcher = import.meta.server ? useRequestFetch() : $fetch;

    async function api<T = any>(path: string, options: Options = {}): Promise<T> {
        try {
            return (await fetcher(`/api/${path.replace(/^\//, '')}`, options as any)) as T;
        } catch (error) {
            const err = toApiError(error);
            if (import.meta.client && err.status === 401) {
                useAuth().clear();
                await navigateTo({ path: '/login', query: { next: useRoute().fullPath } });
            }
            if (import.meta.client && err.code === 'setup_incomplete') {
                await navigateTo('/setup');
            }
            throw err;
        }
    }

    return api;
}

/**
 * Loads page data with SSR, a pending flag for skeletons and refresh() for retry buttons.
 * The key should change when the inputs change (e.g. include the month or filter).
 */
export function useApiData<T = any>(
    key: string | (() => string),
    path: string | (() => string),
    options: { query?: () => Record<string, any>; watch?: any[]; lazy?: boolean } = {},
) {
    const api = useApi();
    return useAsyncData<T>(key as any, () => api<T>(typeof path === 'function' ? path() : path, { query: options.query?.() }), {
        watch: options.watch,
        lazy: options.lazy,
    });
}
