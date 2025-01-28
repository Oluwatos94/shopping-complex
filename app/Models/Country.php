<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'iso_code',    // ISO country code (e.g., US, NG)
        'phone_code'
    ];

    /**
     * Relationships.
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}
