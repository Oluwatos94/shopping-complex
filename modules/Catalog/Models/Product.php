<?php

namespace ModulesShoppingComplex\Catalog\Models;

use Carbon\Carbon;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Media\Models\Media;
use ModulesShoppingComplex\Models\CustomerWishlist;
use ModulesShoppingComplex\Shared\Support\HasTableName;

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
 * @property Carbon|null $deleted_at
 * @property-read User|null $vendor
 * @property-read Collection<int, Media> $media
 * @property list<array{id: int, url: string, type: string, is_primary: bool}> $images View-only media descriptors attached for the frontend; not a database column.
 */
class Product extends Model
{
    use HasFactory, HasTableName, SoftDeletes;

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
        'pay_on_delivery',
        'is_returnable',
        'tags',
    ];

    /** {@inheritdoc} */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function wishlist()
    {
        return $this->hasMany(CustomerWishlist::class);
    }

    /**
     * Use slug for route model binding instead of numeric ID.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
