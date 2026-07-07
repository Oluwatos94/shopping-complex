<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\Models\Enums\AnchorReconciliationEnum;
use ModulesShoppingComplex\Models\Enums\AnchorTransactionKindEnum;
use ModulesShoppingComplex\Models\Enums\Sep24StatusEnum;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 * A single interaction with the anchor: a SEP-24 deposit or an MPP charge.
 *
 * @property int $id
 * @property int $vendor_id
 * @property int|null $plan_id
 * @property string|null $sep24_id
 * @property AnchorTransactionKindEnum $kind
 * @property string|null $billing_period
 * @property string $status
 * @property AnchorReconciliationEnum|null $reconciliation
 * @property float|null $amount
 * @property string $asset
 * @property string|null $stellar_tx_hash
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read User $vendor
 */
class AnchorTransaction extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'vendor_id',
        'plan_id',
        'sep24_id',
        'kind',
        'billing_period',
        'status',
        'reconciliation',
        'amount',
        'asset',
        'stellar_tx_hash',
        'started_at',
        'completed_at',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'kind' => AnchorTransactionKindEnum::class,
        'reconciliation' => AnchorReconciliationEnum::class,
        'amount' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === Sep24StatusEnum::COMPLETED->value;
    }

    public function isFailed(): bool
    {
        return ($status = Sep24StatusEnum::tryFrom($this->status)) !== null && $status->isFailure();
    }

    public function isAmountMismatch(): bool
    {
        return $this->reconciliation === AnchorReconciliationEnum::AMOUNT_MISMATCH;
    }
}
