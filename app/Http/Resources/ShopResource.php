<?php

namespace App\Http\Resources;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shop
 */
class ShopResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_local' => $this->name_local,
            'short_name' => $this->shortName(),
            'initials' => $this->initials(),
            'slug' => $this->slug,
            'category' => $this->category,
            'category_label' => $this->categoryLabel(),
            'city' => $this->city,
            'area' => $this->area,
            'address' => $this->address,
            'landmark' => $this->landmark,
            'pincode' => $this->pincode,
            'whatsapp' => $this->whatsapp,
            'phone' => $this->phone,
            'opens_at' => $this->opens_at ? substr($this->opens_at, 0, 5) : null,
            'closes_at' => $this->closes_at ? substr($this->closes_at, 0, 5) : null,
            'weekly_off' => $this->weekly_off,
            'languages' => $this->languages ?? ['en'],
            'since_year' => $this->since_year,
            'years' => $this->since_year ? max(1, now()->year - $this->since_year) : null,
            'delivery_km' => $this->delivery_km,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'goal' => $this->goal,
            'audience' => $this->audience ?? [],
            'brand' => $this->brand ?? (object) [],
            'logo_path' => $this->logo_path,
            'package_id' => $this->package_id,
            'setup_step' => $this->setup_step,
            'setup_complete' => $this->isSetupComplete(),
            'products' => $this->whenLoaded('products', fn () => $this->products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price_paise' => $p->price_paise,
                'mrp_paise' => $p->mrp_paise,
                'offer' => $p->offer,
                'photo_slot' => $p->photo_slot,
                'is_hero' => $p->is_hero,
            ])),
        ];
    }
}
