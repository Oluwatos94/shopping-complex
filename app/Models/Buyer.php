<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'preferences',
    ];


    /**
     * Relationships.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
