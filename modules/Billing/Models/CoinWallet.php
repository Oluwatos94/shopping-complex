<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * A vendor's prepaid coin balance. The balance is a cache of the ledger and
 * is only ever written by {@see CoinWalletService},
 * which appends the matching ledger entry in the same transaction.
 *
 * @property int $id
 * @property int $vendor_id
 * @property int $balance
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User|null $vendor
 */
class CoinWallet extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $table = 'coin_wallets';

    /** {@inheritdoc} */
    protected $fillable = [
        'vendor_id',
        'balance',
    ];

    /** {@inheritdoc} */
    protected function casts(): array
    {
        return [
            'balance' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CoinLedgerEntry::class, 'vendor_id', 'vendor_id');
    }
}
