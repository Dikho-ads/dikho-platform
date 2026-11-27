<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Shop;
use App\Services\AccountSummary;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shop = $this->shop($request);
        $messages = $shop->messages()->latest('id')->limit(100)->get();

        return response()->json([
            'data' => $messages,
            'audiences' => $this->audiences($shop),
            'rates_paise' => ['whatsapp' => WalletService::WHATSAPP_PAISE, 'sms' => WalletService::SMS_PAISE],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $shop = $this->shop($request);
        $data = $request->validate($this->rules());
        $recipients = $this->audienceQuery($shop, $data['channel'], $data['audience'] ?? ['stage' => 'all'])->count();

        $message = $shop->messages()->create($data + ['status' => 'draft', 'recipients' => $recipients, 'cost_paise' => app(WalletService::class)->costPaise($data['channel'], $recipients)]);

        return response()->json(['data' => $message], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $shop = $this->shop($request);
        $message = $shop->messages()->findOrFail($id);
        abort_unless($message->status === 'draft', 422, 'Only drafts can be changed.');
        $data = $request->validate(array_map(fn (array $r): array => array_map(fn ($x) => $x === 'required' ? 'sometimes' : $x, $r), $this->rules()));
        $message->fill($data);
        $message->recipients = $this->audienceQuery($shop, $message->channel, $message->audience ?? ['stage' => 'all'])->count();
        $message->cost_paise = app(WalletService::class)->costPaise($message->channel, $message->recipients);
        $message->save();

        return response()->json(['data' => $message]);
    }

    /**
     * Schedules a message and holds its cost from the wallet now, so a short wallet is caught
     * before the send time rather than during it.
     */
    public function schedule(Request $request, int $id, WalletService $wallet, AccountSummary $summary): JsonResponse
    {
        $shop = $this->shop($request);
        $message = $shop->messages()->findOrFail($id);
        abort_unless($message->status === 'draft', 422, 'This message is already '.$message->status.'.');
        $data = $request->validate(['scheduled_at' => ['nullable', 'date', 'after_or_equal:now']]);
        abort_if($message->recipients === 0, 422, 'Nobody in this audience can receive '.$message->channel.' messages.');

        DB::transaction(function () use ($shop, $message, $wallet, $data): void {
            $wallet->charge($shop, $message->cost_paise, $message->channel, $message->name.' · '.$message->recipients.' people', $message);
            $message->update(['status' => 'scheduled', 'scheduled_at' => $this->localTime($data['scheduled_at'] ?? null) ?? now()]);
        });

        return response()->json(['data' => $message->refresh(), 'account' => $summary->for($request->user())]);
    }

    /**
     * @return list<array{key: string, label: string, whatsapp: int, sms: int}>
     */
    private function audiences(Shop $shop): array
    {
        $labels = ['all' => 'All customers', 'regular' => 'Regulars', 'new' => 'New this month', 'at_risk' => 'At risk', 'lapsed' => 'Stopped coming'];

        return array_map(fn (string $key): array => [
            'key' => $key,
            'label' => $labels[$key],
            'whatsapp' => $this->audienceQuery($shop, 'whatsapp', ['stage' => $key])->count(),
            'sms' => $this->audienceQuery($shop, 'sms', ['stage' => $key])->count(),
        ], array_keys($labels));
    }

    /**
     * @param  array<string, mixed>  $audience
     * @return HasMany<Customer, Shop>|Builder<Customer>
     */
    private function audienceQuery(Shop $shop, string $channel, array $audience): HasMany|Builder
    {
        return $shop->customers()
            ->when($channel === 'whatsapp', fn ($q) => $q->where('whatsapp_consent', true))
            ->when(($audience['stage'] ?? 'all') !== 'all', fn ($q) => $q->where('stage', $audience['stage']))
            ->when(! empty($audience['customer_ids']), fn ($q) => $q->whereIn('id', $audience['customer_ids']));
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(): array
    {
        return [
            'channel' => ['required', Rule::in(['whatsapp', 'sms'])],
            'name' => ['required', 'string', 'max:80'],
            'template' => ['nullable', 'string', 'max:40'],
            'language' => ['nullable', Rule::in(['en', 'hi', 'mr'])],
            'body' => ['required', 'string', 'max:1024'],
            'buttons' => ['nullable', 'array', 'max:3'],
            'audience' => ['nullable', 'array'],
            'audience.stage' => ['nullable', Rule::in(['all', 'new', 'regular', 'at_risk', 'lapsed'])],
            'campaign_id' => ['nullable', 'integer'],
        ];
    }
}
