<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTypeCategory extends Model
{
    /**
     * Relationships.
     */
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
