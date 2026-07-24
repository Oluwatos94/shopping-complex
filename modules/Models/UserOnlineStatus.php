<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $last_seen_at
 * @property bool $is_online
 * @property string|null $socket_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class UserOnlineStatus extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'user_id',
        'last_seen_at',
        'is_online',
        'socket_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'is_online' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
