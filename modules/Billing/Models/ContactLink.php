<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * A minted /c/{token} link. The buyer identity lives here rather than inside
 * the token so it never travels in a URL that can be forwarded, proxied or
 * written to an access log.
 *
 * @property int $id
 * @property string $token
 * @property int $vendor_id
 * @property ViewSourceEnum $source
 * @property string|null $buyer_identity
 * @property string|null $prefilled_message
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property-read User|null $vendor
 */
class ContactLink extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $table = 'contact_links';

    /** {@inheritdoc} */
    public $timestamps = false;

    /** {@inheritdoc} */
    protected $fillable = [
        'token',
        'vendor_id',
        'source',
        'buyer_identity',
        'prefilled_message',
        'expires_at',
        'created_at',
    ];

    /** {@inheritdoc} */
    protected function casts(): array
    {
        return [
            'source' => ViewSourceEnum::class,
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(ContactClick::class);
    }
}
