<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 * A custodial Stellar keypair the platform holds on a vendor's behalf, so recurring
 * settlement can sign each cycle without the vendor present. The seed is encrypted at
 * rest via the `encrypted` cast on the `encrypted_secret` column, read through `secret`.
 *
 * @property int $id
 * @property int $vendor_id
 * @property string $public_key
 * @property string $encrypted_secret Stellar secret seed (S...); decrypted transparently on read.
 * @property string $secret Alias of $encrypted_secret with a non-misleading name.
 * @property string $network
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read User $vendor
 */
class StellarWallet extends Model
{
    use HasTableName;

    /** {@inheritdoc} Write the seed via `encrypted_secret` so the cast encrypts it. */
    protected $fillable = [
        'vendor_id',
        'public_key',
        'encrypted_secret',
        'network',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'encrypted_secret' => 'encrypted',
    ];

    /** {@inheritdoc} Never serialise the seed (API/Inertia payloads). */
    protected $hidden = [
        'encrypted_secret',
        'secret',
    ];

    protected function secret(): Attribute
    {
        return Attribute::get(fn (): string => $this->encrypted_secret);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
