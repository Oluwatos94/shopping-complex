<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\WhatsApp\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use ModulesShoppingComplex\Shared\Support\HasTableName;
use ModulesShoppingComplex\WhatsApp\Enums\WhatsAppSessionStateEnum;

/**
 * @property int $id
 * @property string $phone_number
 * @property WhatsAppSessionStateEnum $state
 * @property array<string, mixed>|null $data
 * @property Carbon $last_active_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class WhatsAppSession extends Model
{
    use HasTableName;

    /** {@inheritdoc} */
    protected $table = 'whatsapp_sessions';

    /** {@inheritdoc} */
    protected $fillable = [
        'phone_number',
        'state',
        'data',
        'last_active_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => WhatsAppSessionStateEnum::class,
            'data' => 'array',
            'last_active_at' => 'datetime',
        ];
    }
}
