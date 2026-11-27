<?php

namespace App\Http\Resources;

use App\Models\ContentItem;
use App\Services\Playbooks;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContentItem
 */
class ContentItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => Playbooks::FORMATS[$this->type]['label'] ?? ucfirst($this->type),
            'angle' => $this->angle,
            'stage' => $this->stage,
            'language' => $this->language,
            'language_label' => Playbooks::LANGUAGES[$this->language] ?? $this->language,
            'title' => $this->title,
            'hook' => $this->hook,
            'subline' => $this->subline,
            'caption' => $this->caption,
            'cta' => $this->cta,
            'terms' => $this->terms,
            'ratio' => $this->ratio,
            'theme' => $this->theme,
            'photo_slot' => $this->photo_slot,
            'status' => $this->status,
            'source' => $this->source,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'posted_at' => $this->posted_at?->toIso8601String(),
            'channels' => $this->channels ?? [],
            'credits' => $this->credits,
            'trust_score' => $this->trust_score,
            'trust_checks' => $this->trust_checks ?? [],
            'why' => Playbooks::ANGLE_WHY[$this->angle] ?? null,
            'metrics' => [
                'views' => $this->views,
                'chats' => $this->chats,
                'calls' => $this->calls,
                'qr_scans' => $this->qr_scans,
            ],
            'plan_id' => $this->plan_id,
            'campaign_id' => $this->campaign_id,
            'occasion' => $this->whenLoaded('occasion', fn () => $this->occasion ? ['id' => $this->occasion->id, 'slug' => $this->occasion->slug, 'name' => $this->occasion->name] : null),
            'product' => $this->whenLoaded('product', fn () => $this->product ? ['id' => $this->product->id, 'name' => $this->product->name] : null),
        ];
    }
}
