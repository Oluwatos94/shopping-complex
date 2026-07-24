<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Discovery\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * Records that a buyer reached out to a vendor through an on-platform
 * contact button (e.g. the WhatsApp "Message" links on store/product pages).
 *
 * @property int $id
 * @property int $customer_id
 * @property int $vendor_id
 * @property string $channel
 * @property Carbon $created_at
 * @property-read User $customer
 * @property-read User $vendor
 */
class VendorContact extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $table = 'vendor_contacts';

    /** {@inheritdoc} */
    public $timestamps = false;

    /** {@inheritdoc} */
    protected $fillable = [
        'customer_id',
        'vendor_id',
        'channel',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
