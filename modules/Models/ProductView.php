<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Database\Factories\ProductViewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 * @property int $id
 * @property int $product_id
 * @property int $vendor_id
 * @property int|null $viewer_id
 * @property string|null $ip_address
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read User $vendor
 * @property-read User|null $viewer
 */
class ProductView extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'product_id',
        'vendor_id',
        'viewer_id',
        'ip_address',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    protected static function newFactory(): ProductViewFactory
    {
        return ProductViewFactory::new();
    }
}
