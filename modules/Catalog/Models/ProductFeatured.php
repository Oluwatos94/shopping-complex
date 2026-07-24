<?php

namespace ModulesShoppingComplex\Catalog\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * @property int $id
 * @property int $product_id
 * @property bool $is_featured
 * @property Carbon|null $featured_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class ProductFeatured extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'product_id',
        'is_featured',
        'featured_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
