<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $message
 * @property array|null $data
 * @property Carbon|null $read_at
 * @property string|null $group_key
 * @property bool $is_grouped
 * @property int $group_count
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class Notification extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'user_id',
        'type',
        'message',
        'read_at',
        'data',
        'group_key',
        'is_grouped',
        'group_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'is_grouped' => 'boolean',
            'group_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }
}
