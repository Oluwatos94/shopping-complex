<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\Models\Enums\VendorSubscriptionStatusEnum;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 * @property int $id
 * @property int $vendor_id
 * @property int $plan_id
 * @property VendorSubscriptionStatusEnum $status
 * @property Carbon $started_at
 * @property Carbon $expires_at
 * @property string|null $payment_reference
 * @property float|null $amount_paid
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read SubscriptionPlan $plan
 * @property-read User $vendor
 */
class VendorSubscription extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'vendor_id',
        'plan_id',
        'status',
        'started_at',
        'expires_at',
        'payment_reference',
        'amount_paid',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'status' => VendorSubscriptionStatusEnum::class,
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'amount_paid' => 'float',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === VendorSubscriptionStatusEnum::ACTIVE
            && $this->expires_at->isFuture();
    }
}
