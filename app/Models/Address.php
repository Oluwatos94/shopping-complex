<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'street',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
    ];

    /**
     * Relationships.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
