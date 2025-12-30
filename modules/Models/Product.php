<?php

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 * @property int $id
 * @property int $vendor_id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property float $price
 * @property int $stock
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Product extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'description',
        'price',
        'vendor_id',
        'category_id',
        'slug',
        'stock',
        'is_active',
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function basket()
    {
        return $this->hasMany(CustomerBasket::class);
    }

    public function wishlist()
    {
        return $this->hasMany(CustomerWishlist::class);
    }
}
