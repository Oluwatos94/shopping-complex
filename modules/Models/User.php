<?php

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use ModulesShoppingComplex\Models\Enums\ReviewStatusEnum;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

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

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function vendorOnboarding()
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    /**
     * Reviews written by this user (as customer).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    /**
     * Reviews received by this user (as vendor).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function receivedReviews()
    {
        return $this->hasMany(Review::class, 'vendor_id');
    }

    /**
     * Get approved reviews received by this vendor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function approvedReviews()
    {
        return $this->receivedReviews()->where('status', ReviewStatusEnum::APPROVED);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wishlist()
    {
        return $this->hasMany(CustomerWishlist::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }

    /**
     * Users who follow this vendor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'vendor_followers', 'vendor_id', 'follower_id')
            ->withTimestamps();
    }

    /**
     * Vendors this user is following.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function following()
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
