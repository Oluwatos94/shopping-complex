<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'shared_id',
        'shared_type',
        'platform',     // e.g., Facebook, Twitter
    ];

    /**
     * Relationships.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shared()
    {
        return $this->morphTo();
    }
}
