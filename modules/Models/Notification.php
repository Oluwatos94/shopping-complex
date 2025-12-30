<?php

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ModulesShoppingComplex\ModuleTraits\HasTableName;
use ModulesShoppingComplex\User\Models\User;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $message
 * @property array|null $data
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Notification extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'user_id',
        'type',
        'message',
        'read_at',
        'data',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
