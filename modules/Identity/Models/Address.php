<?php

namespace ModulesShoppingComplex\Identity\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ModulesShoppingComplex\Shared\Support\HasTableName;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $street
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property float|null $latitude
 * @property float|null $longitude
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Address extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'user_id',
        'street',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
