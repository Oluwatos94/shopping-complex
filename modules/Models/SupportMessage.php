<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Database\Factories\SupportMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\Models\Enums\SupportMessageRoleEnum;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 * A single message in a {@see SupportConversation}, authored by the customer (USER),
 * the Gemini assistant (ASSISTANT), or a human support agent (AGENT).
 *
 * @property int $id
 * @property int $support_conversation_id
 * @property SupportMessageRoleEnum $role
 * @property int|null $sender_id
 * @property string $content
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read SupportConversation $conversation
 * @property-read User|null $sender
 */
class SupportMessage extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'support_conversation_id',
        'role',
        'sender_id',
        'content',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => SupportMessageRoleEnum::class,
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SupportConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'support_conversation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Check if the message has been read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SupportMessageFactory
    {
        return SupportMessageFactory::new();
    }
}
