<?php

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ModulesShoppingComplex\ModuleTraits\HasTableName;
use ModulesShoppingComplex\Product\Models\Product;
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Category extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'description',
        'slug',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
