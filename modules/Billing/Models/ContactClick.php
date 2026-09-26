<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * An observed buyer click on a vendor contact link — the event billing is
 * charged against. Append-only: every hit on /c/{token} writes a row.
 *
 * @property int $id
 * @property int $contact_link_id
 * @property int $vendor_id
 * @property ViewSourceEnum $source
 * @property string|null $buyer_identity E.164 phone for bot leads, visitor_id for web
 * @property bool $is_billable
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property-read User|null $vendor
 * @property-read ContactLink|null $link
 */
class ContactClick extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $table = 'contact_clicks';

    /** {@inheritdoc} */
    public $timestamps = false;

    /** {@inheritdoc} */
    protected $fillable = [
        'contact_link_id',
        'vendor_id',
        'source',
        'buyer_identity',
        'is_billable',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /** {@inheritdoc} */
    protected function casts(): array
    {
        return [
            'source' => ViewSourceEnum::class,
            'is_billable' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(ContactLink::class, 'contact_link_id');
    }
}
