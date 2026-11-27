<?php

namespace App\Http\Resources;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Plan
 */
class PlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'month' => $this->month->format('Y-m'),
            'status' => $this->status,
            'brief' => $this->brief ?? (object) [],
            'strategy' => $this->strategy ?? (object) [],
            'credits_budget' => $this->credits_budget,
            'wallet_budget_paise' => $this->wallet_budget_paise,
            'version' => $this->version,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'items' => ContentItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
