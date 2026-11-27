<?php

namespace App\Models;

use Database\Factories\ContentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['shop_id', 'plan_id', 'campaign_id', 'occasion_id', 'product_id', 'type', 'angle', 'stage', 'language', 'title', 'hook', 'subline', 'caption', 'cta', 'terms', 'ratio', 'theme', 'photo_slot', 'status', 'source', 'scheduled_at', 'posted_at', 'channels', 'credits', 'trust_score', 'trust_checks', 'views', 'chats', 'calls', 'qr_scans'])]
class ContentItem extends Model
{
    /** @use HasFactory<ContentItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'posted_at' => 'datetime',
            'channels' => 'array',
            'trust_checks' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Shop, $this>
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<Occasion, $this>
     */
    public function occasion(): BelongsTo
    {
        return $this->belongsTo(Occasion::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
