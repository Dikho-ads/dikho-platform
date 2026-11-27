<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContentItemResource;
use App\Models\ContentItem;
use App\Models\CreditTransaction;
use App\Models\Occasion;
use App\Models\Shop;
use App\Services\AccountSummary;
use App\Services\CopyWriter;
use App\Services\CreditService;
use App\Services\Playbooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentController extends Controller
{
    public function __construct(private CreditService $credits, private CopyWriter $writer, private AccountSummary $summary) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'source' => ['nullable', Rule::in(['auto', 'plan', 'manual'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'plan_id' => ['nullable', 'integer'],
            'campaign_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $items = $this->shop($request)->contentItems()
            ->with('occasion:id,slug,name')
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->whereIn('status', explode(',', $s)))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->whereIn('type', explode(',', $t)))
            ->when($filters['source'] ?? null, fn ($q, $s) => $q->where('source', $s))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('scheduled_at', '>=', now()->parse($d)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('scheduled_at', '<=', now()->parse($d)->endOfDay()))
            ->when($filters['plan_id'] ?? null, fn ($q, $id) => $q->where('plan_id', $id))
            ->when($filters['campaign_id'] ?? null, fn ($q, $id) => $q->where('campaign_id', $id))
            ->orderBy('scheduled_at')
            ->limit($filters['limit'] ?? 300)
            ->get();

        return ContentItemResource::collection($items);
    }

    public function show(Request $request, int $id): ContentItemResource
    {
        return new ContentItemResource($this->find($request, $id)->load('occasion', 'product'));
    }

    public function update(Request $request, int $id): ContentItemResource
    {
        $item = $this->find($request, $id);
        $shop = $this->shop($request);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:120'],
            'hook' => ['sometimes', 'string', 'max:160'],
            'subline' => ['sometimes', 'nullable', 'string', 'max:200'],
            'caption' => ['sometimes', 'nullable', 'string', 'max:2200'],
            'cta' => ['sometimes', 'nullable', 'string', 'max:80'],
            'terms' => ['sometimes', 'nullable', 'string', 'max:160'],
            'language' => ['sometimes', Rule::in(array_keys(Playbooks::LANGUAGES))],
            'ratio' => ['sometimes', Rule::in(['1:1', '4:5', '9:16', '4:3', 'A5'])],
            'theme' => ['sometimes', Rule::in(['brand', 'light', 'dark', 'festive'])],
            'photo_slot' => ['sometimes', 'nullable', 'string', 'max:60'],
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'channels' => ['sometimes', 'array'],
            'channels.*' => [Rule::in(['whatsapp_status', 'instagram', 'facebook', 'google', 'broadcast', 'meta_ad'])],
        ]);

        abort_if($item->status === 'posted' && array_intersect_key($data, array_flip(['hook', 'subline', 'caption', 'terms'])), 422, 'This piece is already posted; make a copy to change it.');

        if (array_key_exists('scheduled_at', $data)) {
            $data['scheduled_at'] = $this->localTime($data['scheduled_at']);
        }
        $item->fill($data);
        [$score, $checks] = $this->writer->trustCheck($shop, $item->hook, (string) $item->subline, (string) $item->terms, $item->language, (string) $item->angle);
        $item->trust_score = $score;
        $item->trust_checks = $checks;
        $item->save();

        return new ContentItemResource($item->load('occasion'));
    }

    /**
     * Makes a new piece from a short brief and returns three ways to say it. Credits for the
     * format are spent now; picking another variant later is free.
     */
    public function store(Request $request): JsonResponse
    {
        $shop = $this->shop($request);
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Playbooks::FORMATS))],
            'language' => ['nullable', Rule::in(array_keys(Playbooks::LANGUAGES))],
            'angle' => ['nullable', Rule::in(array_keys(Playbooks::ANGLES))],
            'offer' => ['nullable', 'string', 'max:120'],
            'occasion_id' => ['nullable', 'integer', Rule::exists('occasions', 'id')],
            'product_id' => ['nullable', 'integer'],
            'brief' => ['nullable', 'string', 'max:500'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $language = $data['language'] ?? ($shop->languages[0] ?? 'en');
        $occasion = isset($data['occasion_id']) ? Occasion::query()->find($data['occasion_id']) : null;
        $product = isset($data['product_id']) ? $shop->products()->find($data['product_id']) : null;
        $context = ['offer' => $data['offer'] ?? $product?->offer, 'occasion' => $occasion];
        $variants = $this->writer->variants($shop, $language, $context, $data['angle'] ?? null);
        $format = Playbooks::FORMATS[$data['type']];
        $first = $variants[0];

        $item = DB::transaction(function () use ($shop, $data, $format, $first, $occasion, $product, $language): ContentItem {
            $item = $shop->contentItems()->create([
                'occasion_id' => $occasion?->id,
                'product_id' => $product?->id,
                'type' => $data['type'],
                'angle' => $first['angle'],
                'stage' => 'buying',
                'language' => $language,
                'title' => $data['brief'] ? str($data['brief'])->limit(60)->toString() : $format['label'],
                'hook' => $first['hook'],
                'subline' => $first['subline'],
                'caption' => $first['caption'],
                'cta' => $first['cta'],
                'terms' => $first['terms'],
                'ratio' => $format['ratio'],
                'theme' => $occasion ? 'festive' : 'brand',
                'photo_slot' => $product?->photo_slot ?? "{$shop->category}-product",
                'status' => 'draft',
                'source' => 'manual',
                'scheduled_at' => $this->localTime($data['scheduled_at'] ?? null),
                'channels' => ['whatsapp_status', 'instagram', 'facebook'],
                'credits' => $format['credits'],
                'trust_score' => $first['trust_score'],
                'trust_checks' => $first['trust_checks'],
            ]);
            $this->charge($shop, $item);

            return $item;
        });

        return response()->json([
            'item' => new ContentItemResource($item->load('occasion', 'product')),
            'variants' => $variants,
            'account' => $this->summary->for($request->user()),
        ], 201);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $item = $this->find($request, $id);
        $this->transition($request, [$item], fn (ContentItem $i) => $i->status = $i->scheduled_at?->isFuture() ? 'scheduled' : 'approved');

        return $this->respond($request, $item);
    }

    public function approveBatch(Request $request): JsonResponse
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:60'], 'ids.*' => ['integer']]);
        $items = $this->shop($request)->contentItems()->whereIn('id', $data['ids'])->whereIn('status', ['draft', 'ready'])->get();
        abort_if($items->isEmpty(), 422, 'Nothing here is waiting for approval.');

        $this->transition($request, $items->all(), fn (ContentItem $i) => $i->status = $i->scheduled_at?->isFuture() ? 'scheduled' : 'approved');

        return response()->json([
            'items' => ContentItemResource::collection($items),
            'account' => $this->summary->for($request->user()),
        ]);
    }

    public function schedule(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['scheduled_at' => ['required', 'date', 'after:now']], ['scheduled_at.after' => 'Pick a time later than now.']);
        $item = $this->find($request, $id);
        $this->transition($request, [$item], function (ContentItem $i) use ($data): void {
            $i->scheduled_at = $this->localTime($data['scheduled_at']);
            $i->status = 'scheduled';
        });

        return $this->respond($request, $item);
    }

    /**
     * Marks a piece as shared now. Until the channels are connected, the page shares through the
     * phone's share sheet and this records it.
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $item = $this->find($request, $id);
        $data = $request->validate(['channels' => ['sometimes', 'array']]);
        $this->transition($request, [$item], function (ContentItem $i) use ($data): void {
            $i->status = 'posted';
            $i->posted_at = now();
            if (! empty($data['channels'])) {
                $i->channels = $data['channels'];
            }
        });

        return $this->respond($request, $item);
    }

    /**
     * Undo a share made in the last 15 minutes.
     */
    public function unpublish(Request $request, int $id): JsonResponse
    {
        $item = $this->find($request, $id);
        abort_unless($item->status === 'posted' && $item->posted_at?->gt(now()->subMinutes(15)), 422, 'This can only be undone within 15 minutes of posting.');
        $item->update(['status' => 'ready', 'posted_at' => null]);

        return $this->respond($request, $item);
    }

    public function skip(Request $request, int $id): JsonResponse
    {
        $item = $this->find($request, $id);
        abort_if($item->status === 'posted', 422, 'This piece is already posted.');

        DB::transaction(function () use ($request, $item): void {
            $charged = $this->chargedFor($item);
            if ($charged > 0) {
                $this->credits->refund($this->shop($request), $charged, "Skipped · {$item->title}", $item);
            }
            $item->update(['status' => 'skipped']);
        });

        return $this->respond($request, $item);
    }

    private function find(Request $request, int $id): ContentItem
    {
        return $this->shop($request)->contentItems()->findOrFail($id);
    }

    /**
     * @param  list<ContentItem>  $items
     */
    private function transition(Request $request, array $items, callable $change): void
    {
        $shop = $this->shop($request);

        foreach ($items as $item) {
            if (in_array($item->status, ['posted', 'skipped'], true)) {
                throw ValidationException::withMessages(['status' => "“{$item->title}” is already {$item->status}."]);
            }
        }

        DB::transaction(function () use ($shop, $items, $change): void {
            $due = array_sum(array_map(fn (ContentItem $i): int => max(0, $i->credits - $this->chargedFor($i)), $items));
            if ($due > $shop->creditBalance()) {
                throw new InsufficientBalanceException('credits', $due, $shop->creditBalance());
            }

            foreach ($items as $item) {
                $this->charge($shop, $item);
                $change($item);
                $item->save();
            }
        });
    }

    private function charge(Shop $shop, ContentItem $item): void
    {
        $due = $item->credits - $this->chargedFor($item);
        if ($due > 0) {
            $this->credits->spend($shop, $due, (Playbooks::FORMATS[$item->type]['label'] ?? $item->type).' · '.$item->title, $item);
        }
    }

    private function chargedFor(ContentItem $item): int
    {
        return (int) -CreditTransaction::query()
            ->where('subject_type', $item->getMorphClass())
            ->where('subject_id', $item->id)
            ->whereIn('reason', ['spend', 'refund'])
            ->sum('delta');
    }

    private function respond(Request $request, ContentItem $item): JsonResponse
    {
        return response()->json([
            'item' => new ContentItemResource($item->refresh()->load('occasion')),
            'account' => $this->summary->for($request->user()),
        ]);
    }
}
