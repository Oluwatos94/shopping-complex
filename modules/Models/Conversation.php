<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $vendor_id
 * @property int|null $product_id
 * @property Carbon|null $last_message_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read User $customer
 * @property-read User $vendor
 * @property-read Product|null $product
 * @property-read Collection<int, ChatMessage> $messages
 */
class Conversation extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'customer_id',
        'vendor_id',
        'product_id',
        'last_message_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

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

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Check if a user is a participant in this conversation.
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->customer_id === $userId || $this->vendor_id === $userId;
    }

    /**
     * Get the other participant in the conversation.
     * Ensures relations are loaded to prevent N+1 queries.
     */
    public function getOtherParticipant(int $userId): ?User
    {
        // Eager load relations if not already loaded to prevent N+1
        if (! $this->relationLoaded('customer') || ! $this->relationLoaded('vendor')) {
            $this->load(['customer', 'vendor']);
        }

        if ($this->customer_id === $userId) {
            return $this->vendor;
        }

        if ($this->vendor_id === $userId) {
            return $this->customer;
        }

        return null;
    }

    /**
     * Get unread message count for a user.
     */
    public function getUnreadCountFor(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ConversationFactory
    {
        return ConversationFactory::new();
    }
}
