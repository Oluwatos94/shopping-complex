<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\WhatsApp\Services;

use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use ModulesShoppingComplex\WhatsApp\Jobs\SendWhatsAppMessage;

final readonly class WhatsAppApiService implements WhatsAppSender
{
    /**
     * Send a plain text message to a buyer.
     */
    public function sendText(string $to, string $body): void
    {
        SendWhatsAppMessage::dispatch($to, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $body],
        ]);
    }

    /**
     * Send a WhatsApp interactive list message (used for vendor lists and product catalogues).
     *
     * @param  array<int, array{id: string, title: string, description: string}>  $rows
     */
    public function sendList(string $to, string $header, string $body, array $rows): void
    {
        SendWhatsAppMessage::dispatch($to, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'header' => ['type' => 'text', 'text' => $header],
                'body' => ['text' => $body],
                'action' => [
                    'button' => 'View',
                    'sections' => [['rows' => $rows]],
                ],
            ],
        ]);
    }

    /**
     * Send a location request interactive message.
     * Renders a "Send Location" button the user can tap to share GPS coordinates.
     */
    public function sendLocationRequest(string $to, string $body): void
    {
        SendWhatsAppMessage::dispatch($to, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'location_request_message',
                'body' => ['text' => $body],
                'action' => ['name' => 'send_location'],
            ],
        ]);
    }

    /**
     * Send an interactive button message (used for BACK / MENU prompts).
     *
     * @param  array<int, array{id: string, title: string}>  $buttons
     */
    public function sendInteractive(string $to, string $body, array $buttons): void
    {
        $formattedButtons = array_map(
            fn (array $button) => ['type' => 'reply', 'reply' => ['id' => $button['id'], 'title' => $button['title']]],
            $buttons,
        );

        SendWhatsAppMessage::dispatch($to, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $body],
                'action' => ['buttons' => $formattedButtons],
            ],
        ]);
    }
}
