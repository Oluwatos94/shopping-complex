<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Data\VendorUpdate;

class VendorUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $vendor,
        public VendorUpdate $update,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->update->subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vendor-update',
            with: [
                'vendor' => $this->vendor,
                'update' => $this->update,
            ],
        );
    }
}
