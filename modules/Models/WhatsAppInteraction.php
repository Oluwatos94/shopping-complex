<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ModulesShoppingComplex\Models\Enums\WhatsAppInteractionEventEnum;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 * @property int $id
 * @property string $phone_number
 * @property WhatsAppInteractionEventEnum $event_type
 * @property string|null $search_query
 * @property int|null $vendor_id
 * @property int|null $product_id
 * @property float|null $buyer_latitude
 * @property float|null $buyer_longitude
 * @property Carbon $created_at
 * @property-read User|null $vendor
 * @property-read Product|null $product
 */
class WhatsAppInteraction extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $table = 'whatsapp_interactions';

    /** {@inheritdoc} */
    public $timestamps = false;

    /** {@inheritdoc} */
    protected $fillable = [
        'phone_number',
        'event_type',
        'search_query',
        'vendor_id',
        'product_id',
        'buyer_latitude',
        'buyer_longitude',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => WhatsAppInteractionEventEnum::class,
            'buyer_latitude' => 'float',
            'buyer_longitude' => 'float',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
