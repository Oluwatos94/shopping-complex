<?php

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ModulesShoppingComplex\ModuleTraits\HasTableName;
use ModulesShoppingComplex\Order\Models\OrderItem;
use ModulesShoppingComplex\Product\Models\Product;
use ModulesShoppingComplex\User\Models\User;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $vendor_id
 * @property int|null $status_id
 * @property float $total
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Order extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'customer_id',
        'vendor_id',
        'status_id',
        'total',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }
}
