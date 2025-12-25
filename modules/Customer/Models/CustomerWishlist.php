<?php

namespace ModulesShoppingComplex\Customer\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ModulesShoppingComplex\ModuleTraits\HasTableName;
use ModulesShoppingComplex\Product\Models\Product;
use ModulesShoppingComplex\User\Models\User;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class CustomerWishlist extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'user_id',
        'product_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
