<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\WhatsApp\Repositories;

use ModulesShoppingComplex\WhatsApp\Models\WhatsAppInteraction;

class WhatsAppInteractionRepository
{
    /**
     * Append a new interaction event to the log.
     * This is an event log — no deduplication, every call creates a new row.
     *
     * @param  array<string, mixed>  $data  Expected keys: phone_number, event_type,
     *                                      and optionally: search_query, vendor_id,
     *                                      product_id, buyer_latitude, buyer_longitude
     */
    public function log(array $data): WhatsAppInteraction
    {
        return WhatsAppInteraction::create($data);
    }
}
