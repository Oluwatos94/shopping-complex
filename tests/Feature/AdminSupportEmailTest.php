<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Support\Models\SupportConversation;
use Tests\TestCase;

class AdminSupportEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Render the blade without hitting a real mail server.
        config(['mail.default' => 'array']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function conversationFor(?User $customer): SupportConversation
    {
        return SupportConversation::factory()->create(['user_id' => $customer?->id]);
    }

    public function test_an_admin_emails_the_customer(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'email' => 'jane@example.com']);
        $conversation = $this->conversationFor($customer);

        $this->actingAs($this->admin())
            ->post("/admin/support/conversations/{$conversation->id}/email", [
                'subject' => 'Re: your enquiry',
                'message' => 'Hi Jane, here is the information you asked for.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $messages = Mail::getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);

        $email = $messages->first()->getOriginalMessage();
        $this->assertSame('Re: your enquiry', $email->getSubject());
        $this->assertSame('jane@example.com', $email->getTo()[0]->getAddress());
        $this->assertStringContainsString('information you asked for', (string) $email->getHtmlBody());
    }

    public function test_it_validates_the_subject_and_message(): void
    {
        $conversation = $this->conversationFor(User::factory()->create(['role' => 'customer']));

        $this->actingAs($this->admin())
            ->post("/admin/support/conversations/{$conversation->id}/email", ['subject' => ''])
            ->assertSessionHasErrors(['subject', 'message']);
    }

    public function test_a_guest_conversation_has_no_email_target(): void
    {
        $conversation = $this->conversationFor(null);

        $this->actingAs($this->admin())
            ->post("/admin/support/conversations/{$conversation->id}/email", [
                'subject' => 'Hello',
                'message' => 'Just checking in.',
            ])
            ->assertSessionHas('error');
    }

    public function test_a_non_admin_cannot_email_a_customer(): void
    {
        $conversation = $this->conversationFor(User::factory()->create(['role' => 'customer']));

        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->post("/admin/support/conversations/{$conversation->id}/email", [
                'subject' => 'Hello',
                'message' => 'Hi there.',
            ])
            ->assertForbidden();
    }
}
