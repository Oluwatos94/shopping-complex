<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFeatured extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'is_featured',
        'featured_at',
    ];

    /**
     * Relationships.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
