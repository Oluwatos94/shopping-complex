<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaTag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'taggable_id',
        'taggable_type',
        'category_id',
        'meta_value',
        'meta_key',
    ];

    /**
     * Relationships.
     */
    public function taggable()
    {
        return $this->morphTo();
    }
}
