<?php

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 * @property int $id
 * @property string $url
 * @property string $type
 * @property int $model_id
 * @property string $model_type
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Media extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'url',
        'type',
        'model_id',
        'model_type',
    ];

    public function model()
    {
        return $this->morphTo();
    }
}
