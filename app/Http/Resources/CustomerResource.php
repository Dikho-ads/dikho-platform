<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Customer
 */
class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'initials' => mb_strtoupper(implode('', array_map(fn (string $w): string => mb_substr($w, 0, 1), array_slice($words, 0, 2)))),
            'phone' => $this->phone,
            'area' => $this->area,
            'gender' => $this->gender,
            'birthday' => $this->birthday?->toDateString(),
            'whatsapp_consent' => $this->whatsapp_consent,
            'consent_at' => $this->consent_at?->toIso8601String(),
            'stage' => $this->stage,
            'visits' => $this->visits,
            'spend_paise' => $this->spend_paise,
            'first_visit_on' => $this->first_visit_on?->toDateString(),
            'last_visit_on' => $this->last_visit_on?->toDateString(),
            'days_since_visit' => $this->last_visit_on ? (int) $this->last_visit_on->diffInDays(now()->startOfDay()) : null,
            'last_purchase' => $this->last_purchase,
            'source' => $this->source,
            'photo_slot' => $this->photo_slot,
            'notes' => $this->notes,
        ];
    }
}
