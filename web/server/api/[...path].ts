// Every other /api/* call goes to Laravel's /api/v1/* with the session's token.
export default defineEventHandler(async (event) => {
    const path = event.context.params?.path || '';
    const method = event.method;
    const body = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) ? await readBody(event).catch(() => undefined) : undefined;
    return relay(event, () => laravel(event)('/' + path, { method, body, query: getQuery(event) }));
});
