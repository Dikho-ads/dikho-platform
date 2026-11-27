// Exchanges an emailed code for a token and keeps the token in an httpOnly cookie.
export default defineEventHandler(async (event) => {
    const body = await readBody(event);
    const result: any = await relay(event, () => laravel(event)('/auth/verify', { method: 'POST', body }));
    if (result?.token) {
        setToken(event, result.token);
        return { account: result.account };
    }
    return result;
});
