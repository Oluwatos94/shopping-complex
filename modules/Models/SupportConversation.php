<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Database\Factories\SupportConversationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ModulesShoppingComplex\Models\Enums\SupportConversationStatusEnum;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 *
 * @property int $id
 * @property int|null $user_id
 * @property SupportConversationStatusEnum $status
 * @property Carbon|null $last_message_at
 * @property Carbon|null $escalated_at
 * @property int|null $agent_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read User|null $agent
 * @property-read Collection<int, SupportMessage> $messages
 */
class SupportConversation extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'user_id',
        'status',
        'last_message_at',
        'escalated_at',
        'agent_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SupportConversationStatusEnum::class,
            'last_message_at' => 'datetime',
            'escalated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * @return HasMany<SupportMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function isWithBot(): bool
    {
        return $this->status === SupportConversationStatusEnum::BOT;
    }

    public function isEscalated(): bool
    {
        return $this->escalated_at !== null;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SupportConversationFactory
    {
        return SupportConversationFactory::new();
    }
}
