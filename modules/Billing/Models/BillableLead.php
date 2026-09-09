<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
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
}
