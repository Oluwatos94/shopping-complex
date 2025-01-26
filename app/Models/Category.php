<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'parent_id',
    ];

    /**
     * Relationships.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_category');
    }

    public function metaTags()
    {
        return $this->morphMany(MetaTag::class, 'taggable');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function productTypes()
    {
        return $this->belongsToMany(ProductType::class, 'product_type_category');
    }
}
