<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'attribute_name',
        'attribute_value',
    ];

    /**
     * Relationships.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
