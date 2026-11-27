<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Campaign
 */
class CampaignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $days = (int) $this->starts_on->diffInDays($this->ends_on) + 1;
        $elapsed = (int) min($days, max(0, $this->starts_on->diffInDays(now()->startOfDay(), false) + 1));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'goal' => $this->goal,
            'offer' => $this->offer,
            'status' => $this->status,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on->toDateString(),
            'days' => $days,
            'day' => $elapsed,
            'budget_paise' => $this->budget_paise,
            'spent_paise' => $this->spent_paise,
            'funnel' => [
                'reach' => $this->reach,
                'enquiries' => $this->enquiries,
                'visits' => $this->visits,
                'sales' => $this->sales,
            ],
            'cost_per_enquiry_paise' => $this->enquiries ? intdiv($this->spent_paise, $this->enquiries) : null,
            'occasion' => $this->whenLoaded('occasion', fn () => $this->occasion ? ['id' => $this->occasion->id, 'name' => $this->occasion->name] : null),
            'items' => ContentItemResource::collection($this->whenLoaded('items')),
            'messages' => $this->whenLoaded('messages'),
        ];
    }
}
