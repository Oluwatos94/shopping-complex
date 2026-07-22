<?php

namespace ModulesShoppingComplex\Catalog\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * @property int $id
 * @property int $product_id
 * @property string $attribute_name
 * @property string $attribute_value
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class ProductAttribute extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'product_id',
        'attribute_name',
        'attribute_value',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
