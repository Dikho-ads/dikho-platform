export default defineEventHandler(async (event) => {
    await relay(event, () => laravel(event)('/auth/logout', { method: 'POST' }));
    setToken(event, null);
    setResponseStatus(event, 200);
    return { ok: true };
});
