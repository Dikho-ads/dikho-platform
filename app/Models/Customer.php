<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['shop_id', 'name', 'phone', 'area', 'gender', 'birthday', 'whatsapp_consent', 'consent_at', 'stage', 'visits', 'spend_paise', 'first_visit_on', 'last_visit_on', 'last_purchase', 'source', 'photo_slot', 'notes'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'whatsapp_consent' => 'boolean',
            'consent_at' => 'datetime',
            'first_visit_on' => 'date',
            'last_visit_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Shop, $this>
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
