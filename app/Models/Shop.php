<?php

namespace App\Models;

use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'owner_id', 'name', 'name_local', 'slug', 'category', 'city', 'area', 'address', 'landmark', 'pincode',
    'whatsapp', 'phone', 'opens_at', 'closes_at', 'weekly_off', 'languages', 'since_year', 'delivery_km',
    'rating', 'reviews_count', 'goal', 'audience', 'brand', 'logo_path', 'package_id', 'setup_step', 'setup_completed_at',
])]
class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory;

    public const CATEGORIES = [
        'mobile' => 'Mobile & electronics',
        'kirana' => 'Kirana & general store',
        'sweets' => 'Sweets & farsan',
        'salon' => 'Salon & beauty',
        'clinic' => 'Clinic & pharmacy',
        'coaching' => 'Coaching & classes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'audience' => 'array',
            'brand' => 'array',
            'rating' => 'float',
            'setup_completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @return HasOne<Subscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * @return HasMany<ShopMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(ShopMember::class);
    }

    /**
     * @return HasMany<CreditTransaction, $this>
     */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * @return HasMany<WalletTransaction, $this>
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Plan, $this>
     */
    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    /**
     * @return HasMany<Campaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * @return HasMany<ContentItem, $this>
     */
    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentItem::class);
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasMany<Workflow, $this>
     */
    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }

    /**
     * @return HasMany<DailyMetric, $this>
     */
    public function dailyMetrics(): HasMany
    {
        return $this->hasMany(DailyMetric::class);
    }

    /**
     * @return HasMany<Connection, $this>
     */
    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class);
    }

    /**
     * @return HasMany<FeedNotification, $this>
     */
    public function feed(): HasMany
    {
        return $this->hasMany(FeedNotification::class);
    }

    public function shortName(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];

        return implode(' ', array_slice($words, 0, 2));
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];

        return mb_strtoupper(implode('', array_map(fn (string $word): string => mb_substr($word, 0, 1), array_slice($words, 0, 2))));
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function isSetupComplete(): bool
    {
        return $this->setup_completed_at !== null;
    }

    public function creditBalance(): int
    {
        return (int) ($this->creditTransactions()->latest('id')->value('balance_after') ?? 0);
    }

    public function walletBalancePaise(): int
    {
        return (int) ($this->walletTransactions()->latest('id')->value('balance_after_paise') ?? 0);
    }
}
