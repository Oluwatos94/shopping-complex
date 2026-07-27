<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Support\Models;

use Carbon\Carbon;
use Database\Factories\SupportConversationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\HasTableName;
use ModulesShoppingComplex\Support\Enums\SupportConversationStatusEnum;
use ModulesShoppingComplex\Support\Enums\SupportMessageRoleEnum;

/**
 * @property int $id
 * @property int|null $user_id
 * @property SupportConversationStatusEnum $status
 * @property Carbon|null $last_message_at
 * @property Carbon|null $escalated_at
 * @property int|null $agent_id
 * @property Carbon|null $agent_last_read_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read User|null $agent
 * @property-read Collection<int, SupportMessage> $messages
 * @property-read SupportMessage|null $lastMessage
 * @property-read SupportMessage|null $lastCustomerMessage
 */
class SupportConversation extends Model
{
    use HasFactory, HasTableName;

    public const GUEST_SESSION_KEY = 'support_conversation_id';

    /** {@inheritdoc} */
    protected $fillable = [
        'user_id',
        'status',
        'last_message_at',
        'escalated_at',
        'agent_id',
        'agent_last_read_at',
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
            'agent_last_read_at' => 'datetime',
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

    /**
     * The most recent message in the thread, for inbox previews.
     *
     * @return HasOne<SupportMessage, $this>
     */
    public function lastMessage(): HasOne
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }

    /**
     * The most recent customer-authored message, used to decide whether the
     * thread has unread activity relative to {@see $agent_last_read_at}.
     *
     * @return HasOne<SupportMessage, $this>
     */
    public function lastCustomerMessage(): HasOne
    {
        return $this->hasOne(SupportMessage::class)->ofMany(
            ['id' => 'max'],
            fn (Builder $query) => $query->where('role', SupportMessageRoleEnum::USER),
        );
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
