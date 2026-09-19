<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\Billing\Enums\CoinPurchaseStatusEnum;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * One vendor's attempt to buy a coin pack, through Paystack (redirect) or the
 * Stellar anchor (SEP-24 deposit). Created pending at checkout and flipped to
 * completed exactly once when payment is confirmed, so a replayed webhook or a
 * repeated status poll cannot credit twice. An abandoned payment simply stays
 * pending and credits nothing.
 *
 * @property int $id
 * @property int $vendor_id
 * @property string $pack
 * @property int $price
 * @property int $coins
 * @property int $bonus_coins
 * @property string $reference
 * @property CoinPurchaseStatusEnum $status
 * @property Carbon|null $paid_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User|null $vendor
 */
class CoinPurchase extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $table = 'coin_purchases';

    /** {@inheritdoc} */
    protected $fillable = [
        'vendor_id',
        'pack',
        'price',
        'coins',
        'bonus_coins',
        'reference',
        'status',
        'paid_at',
    ];

    /** {@inheritdoc} */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'coins' => 'integer',
            'bonus_coins' => 'integer',
            'status' => CoinPurchaseStatusEnum::class,
            'paid_at' => 'datetime',
        ];
    }

    public function isCompleted(): bool
    {
        return $this->status === CoinPurchaseStatusEnum::COMPLETED;
    }

    public function totalCoins(): int
    {
        return $this->coins + $this->bonus_coins;
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
