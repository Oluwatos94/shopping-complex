<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use ModulesShoppingComplex\Models\Enums\ReviewStatusEnum;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $vendor_id
 * @property int|null $conversation_id
 * @property int $rating
 * @property string|null $title
 * @property string|null $comment
 * @property ReviewStatusEnum $status
 * @property int|null $moderated_by
 * @property Carbon|null $moderated_at
 * @property string|null $moderation_notes
 * @property int $helpful_count
 * @property int $not_helpful_count
 * @property string|null $vendor_response
 * @property Carbon|null $vendor_responded_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $customer
 * @property-read User $vendor
 * @property-read Conversation|null $conversation
 * @property-read User|null $moderator
 * @property-read Collection<int, ReviewVote> $votes
 */
class Review extends Model
{
    use HasFactory, HasTableName, SoftDeletes;

    /** {@inheritdoc} */
    protected $fillable = [
        'customer_id',
        'vendor_id',
        'conversation_id',
        'rating',
        'title',
        'comment',
        'status',
        'moderated_by',
        'moderated_at',
        'moderation_notes',
        'vendor_response',
        'vendor_responded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReviewStatusEnum::class,
            'moderated_at' => 'datetime',
            'vendor_responded_at' => 'datetime',
            'helpful_count' => 'integer',
            'not_helpful_count' => 'integer',
            'rating' => 'integer',
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
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * @return HasMany<ReviewVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(ReviewVote::class);
    }

    /**
     * Check if the review is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === ReviewStatusEnum::APPROVED;
    }

    /**
     * Check if the review is pending moderation.
     */
    public function isPending(): bool
    {
        return $this->status === ReviewStatusEnum::PENDING;
    }

    /**
     * Check if the review is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === ReviewStatusEnum::REJECTED;
    }

    /**
     * Check if a user has voted on this review.
     */
    public function hasUserVoted(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    /**
     * Get the user's vote on this review.
     */
    public function getUserVote(int $userId): ?ReviewVote
    {
        return $this->votes()->where('user_id', $userId)->first();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ReviewFactory
    {
        return ReviewFactory::new();
    }
}
