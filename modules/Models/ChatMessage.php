<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $sender_id
 * @property string $content
 * @property string|null $attachment_path
 * @property string|null $attachment_type
 * @property string|null $attachment_name
 * @property Carbon|null $delivered_at
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Conversation $conversation
 * @property-read User $sender
 */
class ChatMessage extends Model
{
    use HasFactory, HasTableName, SoftDeletes;

    /** {@inheritdoc} */
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'attachment_path',
        'attachment_type',
        'attachment_name',
        'delivered_at',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
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
     * Check if the message has been delivered.
     */
    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }

    /**
     * Check if the message has an attachment.
     */
    public function hasAttachment(): bool
    {
        return $this->attachment_path !== null;
    }

    /**
     * Get the attachment URL.
     */
    public function getAttachmentUrl(): ?string
    {
        if (! $this->hasAttachment()) {
            return null;
        }

        return asset('storage/'.$this->attachment_path);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ChatMessageFactory
    {
        return ChatMessageFactory::new();
    }
}
