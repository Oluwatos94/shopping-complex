<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaToProduct extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'media_id',
        'product_id',
    ];

    /**
     * Relationships.
     */
    public function media()
    {
        return $this->belongsTo(MediaUploaded::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
