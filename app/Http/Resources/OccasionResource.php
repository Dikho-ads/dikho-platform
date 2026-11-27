<?php

namespace App\Http\Resources;

use App\Models\Occasion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Occasion
 */
class OccasionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $category = $request->attributes->get('shop')?->category;
        $relevance = $category ? (float) ($this->weights[$category] ?? 0.5) : null;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'name_hi' => $this->name_hi,
            'name_mr' => $this->name_mr,
            'kind' => $this->kind,
            'region' => $this->region,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on->toDateString(),
            'days_away' => (int) now()->startOfDay()->diffInDays($this->starts_on, false),
            'relevance' => $relevance,
            'rule' => $this->rule,
            'photo_slot' => $this->photo_slot,
            'planned_items' => $this->when(isset($this->planned_items), fn () => (int) $this->planned_items),
        ];
    }
}
