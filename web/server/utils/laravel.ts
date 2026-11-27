import type { H3Event } from 'h3';

export const TOKEN_COOKIE = 'dikho_token';

/** Calls the Laravel API from the Nuxt server, adding the bearer token from the httpOnly cookie. */
export function laravel(event: H3Event) {
    const config = useRuntimeConfig(event);
    const token = getCookie(event, TOKEN_COOKIE);

    return $fetch.create({
        baseURL: config.apiBase,
        headers: {
            Accept: 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            'X-Forwarded-For': getRequestIP(event, { xForwardedFor: true }) || '',
        },
    });
}

export function setToken(event: H3Event, token: string | null) {
    if (token) {
        setCookie(event, TOKEN_COOKIE, token, {
            httpOnly: true,
            sameSite: 'lax',
            secure: !import.meta.dev,
            path: '/',
            maxAge: 60 * 60 * 24 * 30,
        });
    } else {
        deleteCookie(event, TOKEN_COOKIE, { path: '/' });
    }
}

/** Passes Laravel's status and JSON body through unchanged, so pages see the real message. */
export async function relay<T>(event: H3Event, call: () => Promise<T>): Promise<T> {
    try {
        return await call();
    } catch (error: any) {
        const status = error?.response?.status ?? 502;
        if (status === 401) setToken(event, null);
        setResponseStatus(event, status);
        return (error?.data ?? { message: 'The server could not be reached. Try again in a moment.' }) as T;
    }
}
