<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Reviews\Models;

use Carbon\Carbon;
use Database\Factories\ReviewVoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * @property int $id
 * @property int $review_id
 * @property int $user_id
 * @property bool $is_helpful
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read Review $review
 * @property-read User $user
 */
class ReviewVote extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'review_id',
        'user_id',
        'is_helpful',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_helpful' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Review, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ReviewVoteFactory
    {
        return ReviewVoteFactory::new();
    }
}
