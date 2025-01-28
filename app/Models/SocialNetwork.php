<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialNetwork extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'platform',            // e.g., Facebook, Instagram, TikTok
        'url',
    ];

    /**
     * Relationships.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_social_networks')
                    ->withPivot('social_network_id', 'username')
                    ->withTimestamps();
    }
}
