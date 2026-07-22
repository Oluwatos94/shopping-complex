<?php

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use ModulesShoppingComplex\Billing\Models\VendorSubscription;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Models\Enums\ReviewStatusEnum;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * @property int $id
 * @property int|null $role_id
 * @property string $role
 * @property string $name
 * @property string|null $slug
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $phone
 * @property string|null $whatsapp_number
 * @property string|null $google_id
 * @property string|null $bio
 * @property string|null $business_name
 * @property int|null $category_id
 * @property string|null $available_hours
 * @property string|null $session_id
 * @property-read Category|null $category
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $products
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Review> $reviews
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Notification> $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CustomerWishlist> $wishlist
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Media> $media
 * @property-read Address|null $address
 * @property-read VendorOnboarding|null $vendorOnboarding
 * @property-read \Illuminate\Database\Eloquent\Collection<int, VendorSubscription> $subscriptions
 * @property-read int|null $products_count
 * @property-read int|null $active_products_count
 * @property-read int|null $reviews_count
 * @property-read float|null $reviews_avg_rating
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasTableName, Notifiable;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'slug',
        'email',
        'password',
        'role',
        'phone',
        'whatsapp_number',
        'google_id',
        'bio',
        'business_name',
        'category_id',
        'available_hours',
        'email_verified_at',
    ];

    /** {@inheritdoc} */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    /** {@inheritdoc} */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function address(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function vendorOnboarding(): HasOne
    {
        return $this->hasOne(VendorOnboarding::class);
    }

    /**
     * Check if vendor has completed verification (approved onboarding).
     */
    public function isVendorVerified(): bool
    {
        if ($this->role !== 'vendor') {
            return false;
        }

        return $this->vendorOnboarding?->status === VendorOnboardingStatusEnum::APPROVED;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    /** Reviews written by this user (as customer). */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    /** Reviews received by this user (as vendor). */
    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'vendor_id');
    }

    /** Get approved reviews received by this vendor. */
    public function approvedReviews(): HasMany
    {
        return $this->receivedReviews()->where('status', ReviewStatusEnum::APPROVED);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function wishlist(): HasMany
    {
        return $this->hasMany(CustomerWishlist::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    /** Users who follow this vendor. */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vendor_followers', 'vendor_id', 'follower_id')
            ->withTimestamps();
    }

    /** Vendors this user is following. */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vendor_followers', 'follower_id', 'vendor_id')
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(VendorSubscription::class, 'vendor_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
