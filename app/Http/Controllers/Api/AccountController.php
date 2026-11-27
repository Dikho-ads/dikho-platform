<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Playbooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Team, connected channels, the notification feed and the Content Studio overview.
 */
class AccountController extends Controller
{
    public function team(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->shop($request)->members()->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'manager' THEN 1 ELSE 2 END")->get(['id', 'name', 'phone', 'role', 'permissions', 'invited_at', 'joined_at'])]);
    }

    public function invite(Request $request): JsonResponse
    {
        $shop = $this->shop($request);
        abort_unless($shop->owner_id === $request->user()->id, 403, 'Only the owner can invite people.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'digits:10', 'regex:/^[6-9]/'],
            'role' => ['required', Rule::in(['manager', 'staff'])],
        ]);
        $member = $shop->members()->create($data + ['invited_at' => now(), 'permissions' => $data['role'] === 'manager' ? ['approve', 'post', 'customers'] : ['post']]);

        return response()->json(['data' => $member], 201);
    }

    public function connections(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->shop($request)->connections()->orderBy('id')->get(['id', 'provider', 'status', 'account', 'connected_at'])]);
    }

    /**
     * Records the account to link. The provider's own sign-in (Meta, Google) completes it later;
     * until then the row stays pending.
     */
    public function updateConnection(Request $request, string $provider): JsonResponse
    {
        abort_unless(in_array($provider, ['whatsapp', 'instagram', 'facebook', 'google', 'meta_ads', 'upi'], true), 404);
        $data = $request->validate(['account' => ['nullable', 'string', 'max:120'], 'disconnect' => ['sometimes', 'boolean']]);
        $connection = $this->shop($request)->connections()->firstOrCreate(['provider' => $provider], ['status' => 'not_linked']);
        $connection->update(! empty($data['disconnect'])
            ? ['status' => 'not_linked', 'account' => null, 'connected_at' => null]
            : ['status' => 'pending', 'account' => $data['account'] ?? $connection->account]);

        return response()->json(['data' => $connection]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $feed = $this->shop($request)->feed()->latest('id')->limit(30)->get();

        return response()->json(['data' => $feed, 'unread' => $feed->whereNull('read_at')->count()]);
    }

    public function readNotifications(Request $request): JsonResponse
    {
        $this->shop($request)->feed()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * How much Dikho knows about the shop (the "business brain"), this month's mix and counts.
     */
    public function studio(Request $request): JsonResponse
    {
        $shop = $this->shop($request)->load('products');
        $monthStart = now()->startOfMonth();
        $monthItems = $shop->contentItems()->whereBetween('scheduled_at', [$monthStart, $monthStart->copy()->endOfMonth()])->whereNot('status', 'skipped')->get(['type', 'status', 'stage', 'credits']);

        $brain = [
            ['key' => 'basics', 'label' => 'Shop basics', 'done' => filled($shop->name) && filled($shop->category), 'link' => '/app/settings'],
            ['key' => 'location', 'label' => 'Location and landmark', 'done' => filled($shop->area) && filled($shop->landmark), 'link' => '/app/settings'],
            ['key' => 'contact', 'label' => 'WhatsApp and hours', 'done' => filled($shop->whatsapp) && filled($shop->opens_at), 'link' => '/app/settings'],
            ['key' => 'products', 'label' => 'Products and prices', 'done' => $shop->products->count() >= 3, 'link' => '/app/settings'],
            ['key' => 'audience', 'label' => 'Who buys', 'done' => ! empty($shop->audience), 'link' => '/app/settings'],
            ['key' => 'brand', 'label' => 'Colours and tagline', 'done' => ! empty($shop->brand), 'link' => '/app/settings'],
            ['key' => 'customers', 'label' => 'Customer list', 'done' => $shop->customers()->count() >= 20, 'link' => '/app/customers'],
            ['key' => 'results', 'label' => 'A month of results', 'done' => $shop->dailyMetrics()->count() >= 30, 'link' => '/app/results'],
        ];

        return response()->json([
            'brain' => $brain,
            'brain_percent' => (int) round(count(array_filter($brain, fn (array $b): bool => $b['done'])) / count($brain) * 100),
            'month' => [
                'label' => $monthStart->format('F Y'),
                'total' => $monthItems->count(),
                'posted' => $monthItems->where('status', 'posted')->count(),
                'by_type' => $monthItems->countBy('type')->map(fn (int $n, string $type): array => ['type' => $type, 'label' => Playbooks::FORMATS[$type]['label'] ?? $type, 'count' => $n])->values(),
                'by_stage' => $monthItems->countBy(fn ($i) => $i->stage ?? 'awareness'),
            ],
            'counts' => [
                'auto_waiting' => $shop->contentItems()->where('source', 'auto')->whereIn('status', ['draft', 'ready'])->where('scheduled_at', '>=', now()->startOfDay())->count(),
                'drafts' => $shop->contentItems()->where('status', 'draft')->count(),
                'scheduled' => $shop->contentItems()->where('status', 'scheduled')->count(),
                'messages' => $shop->messages()->count(),
                'workflows_active' => $shop->workflows()->where('active', true)->count(),
                'workflows' => $shop->workflows()->count(),
                'campaigns_live' => $shop->campaigns()->where('status', 'live')->count(),
            ],
            'formats' => collect(Playbooks::FORMATS)->map(fn (array $f, string $k): array => ['type' => $k] + $f)->values(),
            'languages' => collect(Playbooks::LANGUAGES)->map(fn (string $label, string $k): array => ['code' => $k, 'label' => $label])->values(),
        ]);
    }
}
