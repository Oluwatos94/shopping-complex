<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\LeadCreditReasonEnum;
use ModulesShoppingComplex\Billing\Enums\LeadUnbilledReasonEnum;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * One charged introduction between a buyer and a vendor. A pair is charged at
 * most once per window; further clicks inside it count as repeats on this row.
 *
 * @property int $id
 * @property int|null $contact_click_id
 * @property int $vendor_id
 * @property string $buyer_identity
 * @property ViewSourceEnum $channel
 * @property int $coins_charged
 * @property BillableLeadStateEnum $state
 * @property LeadUnbilledReasonEnum|null $unbilled_reason
 * @property LeadCreditReasonEnum|null $credit_reason
 * @property string|null $delivered_number
 * @property string|null $buyer_search
 * @property string|null $buyer_area
 * @property Carbon $window_start
 * @property int $repeat_count
 * @property Carbon|null $last_click_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User|null $vendor
 * @property-read ContactClick|null $openingClick
 */
class BillableLead extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $table = 'billable_leads';

    /** {@inheritdoc} */
    protected $fillable = [
        'contact_click_id',
        'vendor_id',
        'buyer_identity',
        'channel',
        'coins_charged',
        'state',
        'unbilled_reason',
        'credit_reason',
        'delivered_number',
        'buyer_search',
        'buyer_area',
        'window_start',
        'repeat_count',
        'last_click_at',
    ];

    /** {@inheritdoc} */
    protected function casts(): array
    {
        return [
            'channel' => ViewSourceEnum::class,
            'state' => BillableLeadStateEnum::class,
            'unbilled_reason' => LeadUnbilledReasonEnum::class,
            'credit_reason' => LeadCreditReasonEnum::class,
            'coins_charged' => 'integer',
            'repeat_count' => 'integer',
            'window_start' => 'datetime',
            'last_click_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function openingClick(): BelongsTo
    {
        return $this->belongsTo(ContactClick::class, 'contact_click_id');
    }

    /**
     * Leads withheld from billing because they tripped an abuse guard, for review.
     *
     * @param  Builder<BillableLead>  $query
     * @return Builder<BillableLead>
     */
    public function scopeFlagged(Builder $query): Builder
    {
        return $query->whereIn('unbilled_reason', LeadUnbilledReasonEnum::flagged());
    }

    /**
     * @param  Builder<BillableLead>  $query
     * @return Builder<BillableLead>
     */
    public function scopeCredited(Builder $query): Builder
    {
        return $query->whereNotNull('credit_reason');
    }
}
