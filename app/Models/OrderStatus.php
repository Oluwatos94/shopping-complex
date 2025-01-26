<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relationships.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'status_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class, 'status_id');
    }
}
