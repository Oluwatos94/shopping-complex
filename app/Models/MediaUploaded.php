<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaUploaded extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uploaded_by',
        'media_name',
        'media_path',
        'media_type_id',
        'media_size',
        'uploaded_at',
    ];

    /**
     * Relationships.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function mediaType()
    {
        return $this->belongsTo(MediaType::class, 'media_type_id');
    }
}
