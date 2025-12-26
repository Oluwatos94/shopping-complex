<?php

namespace ModulesShoppingComplex\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use ModulesShoppingComplex\Customer\Models\CustomerBasket;
use ModulesShoppingComplex\Customer\Models\CustomerWishlist;
use ModulesShoppingComplex\Media\Models\Media;
use ModulesShoppingComplex\ModuleTraits\HasTableName;
use ModulesShoppingComplex\Notification\Models\Notification;
use ModulesShoppingComplex\Order\Models\Order;
use ModulesShoppingComplex\Product\Models\Product;
use ModulesShoppingComplex\Review\Models\Review;
use ModulesShoppingComplex\Shared\Models\Address;

/**
 * @property int $id
 * @property int|null $role_id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property string|null $phone
 * @property string|null $google_id
 * @property string|null $bio
 * @property string|null $business_name
 * @property string|null $session_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable
{
    use HasFactory, HasTableName, Notifiable;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'google_id',
        'bio',
        'business_name',
    ];

    /** {@inheritdoc} */
    protected $hidden = [
        'password',
        'remember_token',
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

    public function products()
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    public function ordersAsCustomer()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function ordersAsVendor()
    {
        return $this->hasMany(Order::class, 'vendor_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function basket()
    {
        return $this->hasMany(CustomerBasket::class);
    }

    public function wishlist()
    {
        return $this->hasMany(CustomerWishlist::class);
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }
}
