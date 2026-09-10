<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Exceptions\LedgerIsAppendOnlyException;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * One append-only movement of coins. Amounts are signed, so a vendor's balance
 * is always the sum of their entries and every row records the balance it left
 * behind. Entries that add coins carry the date those coins expire.
 *
 * @property int $id
 * @property int $vendor_id
 * @property CoinLedgerTypeEnum $type
 * @property int $amount
 * @property int $balance_after
 * @property int|null $lot_id
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property-read User|null $vendor
 * @property-read Model|null $reference
 * @property-read CoinLedgerEntry|null $lot
 */
class CoinLedgerEntry extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $table = 'coin_ledger';

    /** {@inheritdoc} */
    public $timestamps = false;

    /** {@inheritdoc} */
    protected $fillable = [
        'vendor_id',
        'type',
        'amount',
        'balance_after',
        'lot_id',
        'reference_type',
        'reference_id',
        'expires_at',
        'created_at',
    ];

    /** {@inheritdoc} */
    protected function casts(): array
    {
        return [
            'type' => CoinLedgerTypeEnum::class,
            'amount' => 'integer',
            'balance_after' => 'integer',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Bulk writes never hydrate a model, so they would miss the event guards
     * below. Route them through a builder that refuses instead.
     *
     * @param  Builder  $query
     * @return AppendOnlyBuilder<self>
     */
    public function newEloquentBuilder($query): AppendOnlyBuilder
    {
        /** @var AppendOnlyBuilder<self> $builder */
        $builder = new AppendOnlyBuilder($query);

        return $builder;
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LedgerIsAppendOnlyException('Coin ledger entries cannot be modified.');
        });

        static::deleting(function (): never {
            throw new LedgerIsAppendOnlyException('Coin ledger entries cannot be deleted.');
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The credit entry whose coins this entry drew down.
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(self::class, 'lot_id');
    }
}
