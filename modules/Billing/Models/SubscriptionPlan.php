<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property float $price
 * @property int $product_limit
 * @property int $search_priority
 * @property array|null $features
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class SubscriptionPlan extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'slug',
        'price',
        'product_limit',
        'search_priority',
        'features',
        'is_active',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'price' => 'float',
        'product_limit' => 'integer',
        'search_priority' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function vendorSubscriptions(): HasMany
    {
        return $this->hasMany(VendorSubscription::class, 'plan_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isFree(): bool
    {
        return $this->slug === 'free';
    }
}
