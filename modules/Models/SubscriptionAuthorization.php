<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\Models\Enums\SubscriptionAuthorizationStatusEnum;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 * A vendor's one-time, business-layer consent to recurring (MPP) billing. Because Jiidaa is
 * custodial there is no on-chain allowance; this record is the policy the renewal job enforces
 * before signing each cycle's charge with the held vendor key — the charge must be ≤ `monthly_cap`
 * and made before `valid_until`.
 *
 * @property int $id
 * @property int $vendor_id
 * @property int $plan_id
 * @property float $monthly_cap
 * @property Carbon $valid_until
 * @property SubscriptionAuthorizationStatusEnum $status
 * @property Carbon $consent_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read SubscriptionPlan $plan
 * @property-read User $vendor
 */
class SubscriptionAuthorization extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'vendor_id',
        'plan_id',
        'monthly_cap',
        'valid_until',
        'status',
        'consent_at',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'monthly_cap' => 'float',
        'valid_until' => 'datetime',
        'status' => SubscriptionAuthorizationStatusEnum::class,
        'consent_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /** Whether this consent may still authorise a charge right now. */
    public function isChargeable(): bool
    {
        return $this->status === SubscriptionAuthorizationStatusEnum::ACTIVE
            && $this->valid_until->isFuture();
    }
}
