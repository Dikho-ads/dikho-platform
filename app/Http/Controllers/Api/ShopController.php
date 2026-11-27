<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShopResource;
use App\Models\Package;
use App\Models\Shop;
use App\Services\AccountSummary;
use App\Services\ShopSetup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    public function __construct(private AccountSummary $summary) {}

    public function packages(): JsonResponse
    {
        return response()->json(Package::query()->orderBy('sort')->get(['id', 'name', 'price_paise', 'credits', 'blurb', 'features']));
    }

    public function show(Request $request): ShopResource
    {
        return new ShopResource($this->shop($request)->load('products'));
    }

    public function update(Request $request): ShopResource
    {
        $shop = $this->shop($request);
        // Package changes go through billing so they are charged and logged.
        $rules = $this->rules();
        unset($rules['package_id']);
        $shop->update($request->validate($rules));

        return new ShopResource($shop->load('products'));
    }

    /**
     * Saves one setup step. Every field is optional so the owner can go back and forth; the
     * step number only moves forward.
     */
    public function setupStep(Request $request, int $step): JsonResponse
    {
        abort_unless($step >= 1 && $step <= 10, 404);
        $shop = $this->shop($request);

        $data = $request->validate($this->rules() + [
            'products' => ['sometimes', 'array', 'max:8'],
            'products.*.name' => ['required', 'string', 'max:80'],
            'products.*.price' => ['nullable', 'string', 'max:40'],
            'products.*.price_paise' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($shop, $data, $step): void {
            if (array_key_exists('products', $data)) {
                $shop->products()->delete();
                foreach (array_values($data['products']) as $i => $product) {
                    $shop->products()->create([
                        'name' => $product['name'],
                        'price_paise' => $product['price_paise'] ?? null,
                        'offer' => $product['price'] ?? null,
                        'photo_slot' => "{$shop->category}-product-".($i + 1),
                        'is_hero' => $i === 0,
                    ]);
                }
                unset($data['products']);
            }

            $shop->fill($data);
            $shop->setup_step = max($shop->setup_step, min(10, $step + 1));
            $shop->save();
        });

        return response()->json(['shop' => new ShopResource($shop->load('products'))]);
    }

    public function completeSetup(Request $request, ShopSetup $setup): JsonResponse
    {
        $shop = $this->shop($request);
        $request->validate(['package_id' => ['sometimes', Rule::exists('packages', 'id')]]);

        if ($request->filled('package_id')) {
            $shop->update(['package_id' => $request->string('package_id')->toString()]);
        }

        $setup->complete($shop);

        return response()->json($this->summary->for($request->user()));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:80'],
            'name_local' => ['sometimes', 'nullable', 'string', 'max:80'],
            'category' => ['sometimes', Rule::in(array_keys(Shop::CATEGORIES))],
            'city' => ['sometimes', 'nullable', 'string', 'max:60'],
            'area' => ['sometimes', 'nullable', 'string', 'max:80'],
            'address' => ['sometimes', 'nullable', 'string', 'max:190'],
            'landmark' => ['sometimes', 'nullable', 'string', 'max:120'],
            'pincode' => ['sometimes', 'nullable', 'digits:6'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:20'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'opens_at' => ['sometimes', 'nullable', 'date_format:H:i'],
            'closes_at' => ['sometimes', 'nullable', 'date_format:H:i'],
            'weekly_off' => ['sometimes', 'nullable', Rule::in(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])],
            'languages' => ['sometimes', 'array', 'min:1'],
            'languages.*' => [Rule::in(['en', 'hi', 'mr'])],
            'since_year' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'delivery_km' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:50'],
            'goal' => ['sometimes', 'nullable', 'string', 'max:60'],
            'audience' => ['sometimes', 'nullable', 'array'],
            'brand' => ['sometimes', 'nullable', 'array'],
            'package_id' => ['sometimes', Rule::exists('packages', 'id')],
        ];
    }
}
