<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
        'vendor_id',
        'brand_id',
        'product_type_id'
    ];

    /**
     * Relationships.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Relationships.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function metaTags()
    {
        return $this->morphMany(MetaTag::class, 'taggable');
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function featured()
    {
        return $this->hasOne(ProductFeatured::class);
    }

    public function pricingBrands()
    {
        return $this->hasMany(ProductPricingBrand::class);
    }
}
