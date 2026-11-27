<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Services\AccountSummary;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'stage' => ['nullable', Rule::in(['new', 'regular', 'at_risk', 'lapsed'])],
            'q' => ['nullable', 'string', 'max:60'],
            'consent' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);
        $shop = $this->shop($request);

        $page = $shop->customers()
            ->when($data['stage'] ?? null, fn ($q, $s) => $q->where('stage', $s))
            ->when(isset($data['consent']), fn ($q) => $q->where('whatsapp_consent', (bool) $data['consent']))
            ->when($data['q'] ?? null, function ($q, $term) {
                $like = '%'.mb_strtolower($term).'%';
                $digits = preg_replace('/\D/', '', $term);
                $q->where(fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->when(strlen($digits) >= 3, fn ($p) => $p->orWhere('phone', 'like', '%'.$digits.'%')));
            })
            ->orderByRaw("CASE stage WHEN 'at_risk' THEN 0 WHEN 'lapsed' THEN 1 WHEN 'new' THEN 2 ELSE 3 END")
            ->orderByDesc('last_visit_on')
            ->paginate($data['per_page'] ?? 25);

        $counts = $shop->customers()->selectRaw('stage, count(*) as n')->groupBy('stage')->pluck('n', 'stage');

        return response()->json([
            'data' => CustomerResource::collection($page->items()),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()],
            'summary' => [
                'total' => (int) $counts->sum(),
                'new' => (int) ($counts['new'] ?? 0),
                'regular' => (int) ($counts['regular'] ?? 0),
                'at_risk' => (int) ($counts['at_risk'] ?? 0),
                'lapsed' => (int) ($counts['lapsed'] ?? 0),
                'whatsapp_consent' => $shop->customers()->where('whatsapp_consent', true)->count(),
                'birthdays_this_month' => $shop->customers()->whereNotNull('birthday')->get(['birthday'])->filter(fn ($c) => $c->birthday->month === now()->month)->count(),
            ],
        ]);
    }

    public function show(Request $request, int $id): CustomerResource
    {
        return new CustomerResource($this->shop($request)->customers()->findOrFail($id));
    }

    public function store(Request $request): CustomerResource
    {
        $shop = $this->shop($request);
        $data = $request->validate($this->rules(true) + [
            'phone' => ['required', 'digits:10', 'regex:/^[6-9]/', Rule::unique('customers')->where('shop_id', $shop->id)],
        ], ['phone.unique' => 'This number is already in your customers.']);

        $customer = $shop->customers()->create($data + [
            'stage' => 'new',
            'visits' => 1,
            'first_visit_on' => now()->toDateString(),
            'last_visit_on' => now()->toDateString(),
            'consent_at' => ! empty($data['whatsapp_consent']) ? now() : null,
            'source' => $data['source'] ?? 'walk_in',
        ]);

        return new CustomerResource($customer);
    }

    public function update(Request $request, int $id): CustomerResource
    {
        $customer = $this->shop($request)->customers()->findOrFail($id);
        $data = $request->validate($this->rules(false));
        if (array_key_exists('whatsapp_consent', $data) && $data['whatsapp_consent'] && ! $customer->whatsapp_consent) {
            $data['consent_at'] = now();
        }
        $customer->update($data);

        return new CustomerResource($customer);
    }

    /**
     * Sends a personal WhatsApp offer to each chosen customer who gave consent. Paid from the
     * wallet at Meta's rate; customers without consent are skipped and reported back.
     */
    public function winBack(Request $request, WalletService $wallet, AccountSummary $summary): JsonResponse
    {
        $shop = $this->shop($request);
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer'],
            'offer' => ['nullable', 'string', 'max:160'],
        ]);

        $customers = $shop->customers()->whereIn('id', $data['ids'])->get();
        $reachable = $customers->where('whatsapp_consent', true);
        abort_if($reachable->isEmpty(), 422, 'None of these customers agreed to WhatsApp messages.');

        $message = DB::transaction(function () use ($shop, $reachable, $wallet, $data) {
            $cost = $wallet->costPaise('whatsapp', $reachable->count());
            $message = $shop->messages()->create([
                'channel' => 'whatsapp',
                'name' => 'Win-back · '.$reachable->count().' customers',
                'template' => 'win_back',
                'language' => $shop->languages[0] ?? 'en',
                'body' => 'Namaste {name}! Aapko {shop} mein miss kiya. '.($data['offer'] ?? 'Is hafte aapke liye ek khaas offer hai.').' Reply karein ya dukaan aayein.',
                'audience' => ['customer_ids' => $reachable->pluck('id')->values()],
                'status' => 'sending',
                'scheduled_at' => now(),
                'recipients' => $reachable->count(),
                'cost_paise' => $cost,
            ]);
            $wallet->charge($shop, $cost, 'whatsapp', $message->name, $message);

            return $message;
        });

        return response()->json([
            'message_id' => $message->id,
            'sent_to' => $reachable->count(),
            'skipped_no_consent' => $customers->count() - $reachable->count(),
            'cost_paise' => $message->cost_paise,
            'account' => $summary->for($request->user()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $creating): array
    {
        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:80'],
            'area' => ['sometimes', 'nullable', 'string', 'max:80'],
            'gender' => ['sometimes', 'nullable', Rule::in(['f', 'm', 'o'])],
            'birthday' => ['sometimes', 'nullable', 'date', 'before:today'],
            'whatsapp_consent' => ['sometimes', 'boolean'],
            'stage' => ['sometimes', Rule::in(['new', 'regular', 'at_risk', 'lapsed'])],
            'last_purchase' => ['sometimes', 'nullable', 'string', 'max:120'],
            'source' => ['sometimes', 'nullable', Rule::in(['walk_in', 'whatsapp', 'instagram', 'meta_ad', 'referral', 'google'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
