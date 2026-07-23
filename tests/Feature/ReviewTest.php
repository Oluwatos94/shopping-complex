<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Reviews\Enums\ReviewStatusEnum;
use ModulesShoppingComplex\Reviews\Events\ReviewReceivedEvent;
use ModulesShoppingComplex\Reviews\Models\Review;
use ModulesShoppingComplex\Reviews\Models\ReviewVote;
use ModulesShoppingComplex\WhatsApp\Enums\WhatsAppInteractionEventEnum;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendor;

    protected User $customer;

    protected User $otherCustomer;

    protected User $admin;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([ReviewReceivedEvent::class]);

        $this->vendor = User::factory()->create([
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->otherCustomer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create conversation to allow review
        $this->conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();
    }

    // ==================== Review Submission Tests ====================

    public function test_customer_can_submit_review_after_interaction(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/reviews', [
                'vendor_id' => $this->vendor->id,
                'rating' => 5,
                'title' => 'Great service!',
                'comment' => 'The vendor was very helpful and professional.',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'review' => ['id', 'customer_id', 'vendor_id', 'rating', 'status'],
                'message',
            ]);

        $this->assertDatabaseHas('reviews', [
            'customer_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
            'rating' => 5,
            'status' => ReviewStatusEnum::APPROVED->value,
        ]);
    }

    public function test_vendor_is_notified_when_review_is_submitted(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/reviews', [
                'vendor_id' => $this->vendor->id,
                'rating' => 5,
                'title' => 'Great service!',
                'comment' => 'The vendor was very helpful and professional.',
            ])
            ->assertStatus(201);

        Event::assertDispatched(ReviewReceivedEvent::class, function (ReviewReceivedEvent $event) {
            return $event->recipient->id === $this->vendor->id
                && $event->customer->id === $this->customer->id
                && $event->rating === 5;
        });
    }

    public function test_customer_cannot_review_without_interaction(): void
    {
        // otherCustomer has no conversation with vendor
        $response = $this->actingAs($this->otherCustomer)
            ->postJson('/reviews', [
                'vendor_id' => $this->vendor->id,
                'rating' => 4,
                'comment' => 'Good service',
            ]);

        $response->assertStatus(422);
    }

    public function test_customer_can_review_after_whatsapp_contact(): void
    {
        // No in-app conversation, but the customer contacted the vendor via the
        // WhatsApp bot (logged against their phone number, in international form).
        $this->otherCustomer->update(['whatsapp_number' => '+2348012345678']);

        DB::table('whatsapp_interactions')->insert([
            'phone_number' => '2348012345678',
            'event_type' => WhatsAppInteractionEventEnum::CONTACT_REQUESTED->value,
            'vendor_id' => $this->vendor->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->otherCustomer)
            ->postJson('/reviews', [
                'vendor_id' => $this->vendor->id,
                'rating' => 5,
                'comment' => 'Reached out on WhatsApp and they delivered.',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reviews', [
            'customer_id' => $this->otherCustomer->id,
            'vendor_id' => $this->vendor->id,
            'conversation_id' => null,
            'rating' => 5,
        ]);
    }

    public function test_customer_can_review_after_on_platform_contact(): void
    {
        // No in-app conversation, but the customer used an on-platform contact
        // button (recorded against their account id).
        DB::table('vendor_contacts')->insert([
            'customer_id' => $this->otherCustomer->id,
            'vendor_id' => $this->vendor->id,
            'channel' => 'whatsapp',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->otherCustomer)
            ->postJson('/reviews', [
                'vendor_id' => $this->vendor->id,
                'rating' => 4,
                'comment' => 'Messaged them from the store page.',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reviews', [
            'customer_id' => $this->otherCustomer->id,
            'vendor_id' => $this->vendor->id,
            'conversation_id' => null,
            'rating' => 4,
        ]);
    }

    public function test_customer_cannot_submit_duplicate_review(): void
    {
        // Submit first review
        Review::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        // Try to submit another
        $response = $this->actingAs($this->customer)
            ->postJson('/reviews', [
                'vendor_id' => $this->vendor->id,
                'rating' => 3,
                'comment' => 'Second review',
            ]);

        $response->assertStatus(422);
        $this->assertEquals(1, Review::where('customer_id', $this->customer->id)->count());
    }

    public function test_vendor_cannot_submit_reviews(): void
    {
        $response = $this->actingAs($this->vendor)
            ->postJson('/reviews', [
                'vendor_id' => $this->vendor->id,
                'rating' => 5,
            ]);

        $response->assertStatus(403);
    }

    public function test_review_requires_valid_rating(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/reviews', [
                'vendor_id' => $this->vendor->id,
                'rating' => 6, // Invalid
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('rating');
    }

    // ==================== Review Viewing Tests ====================

    public function test_anyone_can_view_approved_reviews(): void
    {
        Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->count(3)
            ->create();

        $response = $this->getJson("/vendors/{$this->vendor->slug}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'reviews');
    }

    public function test_pending_reviews_not_shown_publicly(): void
    {
        Review::factory()
            ->forVendor($this->vendor)
            ->pending()
            ->count(2)
            ->create();

        Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->create();

        $response = $this->getJson("/vendors/{$this->vendor->slug}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'reviews');
    }

    public function test_vendor_can_see_all_their_reviews(): void
    {
        Review::factory()
            ->forVendor($this->vendor)
            ->pending()
            ->count(2)
            ->create();

        Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->create();

        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/reviews');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'reviews');
    }

    public function test_customer_can_view_own_reviews(): void
    {
        Review::factory()
            ->forCustomer($this->customer)
            ->count(2)
            ->create();

        $response = $this->actingAs($this->customer)
            ->getJson('/my-reviews');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'reviews');
    }

    // ==================== Rating Statistics Tests ====================

    public function test_vendor_rating_stats_calculated_correctly(): void
    {
        Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->withRating(5)
            ->count(2)
            ->create();

        Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->withRating(3)
            ->create();

        $response = $this->getJson("/vendors/{$this->vendor->slug}/reviews/stats");

        $response->assertStatus(200)
            ->assertJson([
                'count' => 3,
            ]);

        // Average should be (5+5+3)/3 = 4.33
        $this->assertEquals(4.33, $response->json('average'));
    }

    public function test_pending_reviews_not_included_in_stats(): void
    {
        Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->withRating(5)
            ->create();

        Review::factory()
            ->forVendor($this->vendor)
            ->pending()
            ->withRating(1)
            ->create();

        $response = $this->getJson("/vendors/{$this->vendor->slug}/reviews/stats");

        $response->assertStatus(200)
            ->assertJson([
                'average' => 5.0,
                'count' => 1,
            ]);
    }

    // ==================== Review Moderation Tests ====================

    public function test_admin_can_approve_review(): void
    {
        $review = Review::factory()
            ->forVendor($this->vendor)
            ->pending()
            ->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/admin/reviews/{$review->id}/moderate", [
                'action' => 'approve',
                'notes' => 'Looks good',
            ]);

        $response->assertStatus(200);

        $review->refresh();
        $this->assertEquals(ReviewStatusEnum::APPROVED, $review->status);
        $this->assertEquals($this->admin->id, $review->moderated_by);
        $this->assertNotNull($review->moderated_at);
    }

    public function test_admin_can_reject_review(): void
    {
        $review = Review::factory()
            ->forVendor($this->vendor)
            ->pending()
            ->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/admin/reviews/{$review->id}/moderate", [
                'action' => 'reject',
                'notes' => 'Contains inappropriate content',
            ]);

        $response->assertStatus(200);

        $review->refresh();
        $this->assertEquals(ReviewStatusEnum::REJECTED, $review->status);
    }

    public function test_non_admin_cannot_moderate(): void
    {
        $review = Review::factory()
            ->forVendor($this->vendor)
            ->pending()
            ->create();

        $response = $this->actingAs($this->customer)
            ->postJson("/admin/reviews/{$review->id}/moderate", [
                'action' => 'approve',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_view_pending_reviews(): void
    {
        Review::factory()
            ->pending()
            ->count(3)
            ->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/admin/reviews/pending');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'reviews');
    }

    // ==================== Review Voting Tests ====================

    public function test_user_can_vote_helpful_on_review(): void
    {
        $review = Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->create();

        $response = $this->actingAs($this->otherCustomer)
            ->postJson("/reviews/{$review->id}/vote", [
                'is_helpful' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'helpful_count' => 1,
                'not_helpful_count' => 0,
            ]);

        $this->assertDatabaseHas('review_votes', [
            'review_id' => $review->id,
            'user_id' => $this->otherCustomer->id,
            'is_helpful' => true,
        ]);
    }

    public function test_user_can_vote_not_helpful_on_review(): void
    {
        $review = Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->create();

        $response = $this->actingAs($this->otherCustomer)
            ->postJson("/reviews/{$review->id}/vote", [
                'is_helpful' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'helpful_count' => 0,
                'not_helpful_count' => 1,
            ]);
    }

    public function test_user_can_change_vote(): void
    {
        $review = Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->create();

        // Vote helpful first
        $this->actingAs($this->otherCustomer)
            ->postJson("/reviews/{$review->id}/vote", ['is_helpful' => true]);

        // Change to not helpful
        $response = $this->actingAs($this->otherCustomer)
            ->postJson("/reviews/{$review->id}/vote", ['is_helpful' => false]);

        $response->assertStatus(200)
            ->assertJson([
                'helpful_count' => 0,
                'not_helpful_count' => 1,
            ]);

        $this->assertEquals(1, ReviewVote::where('review_id', $review->id)->count());
    }

    public function test_user_can_remove_vote(): void
    {
        $review = Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->withHelpfulVotes(1)
            ->create();

        ReviewVote::factory()
            ->forReview($review)
            ->forUser($this->otherCustomer)
            ->helpful()
            ->create();

        $response = $this->actingAs($this->otherCustomer)
            ->deleteJson("/reviews/{$review->id}/vote");

        $response->assertStatus(200)
            ->assertJson([
                'removed' => true,
                'helpful_count' => 0,
            ]);
    }

    public function test_user_cannot_vote_on_own_review(): void
    {
        $review = Review::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->approved()
            ->create();

        $response = $this->actingAs($this->customer)
            ->postJson("/reviews/{$review->id}/vote", [
                'is_helpful' => true,
            ]);

        $response->assertStatus(403);
    }

    public function test_cannot_vote_on_unapproved_review(): void
    {
        $review = Review::factory()
            ->forVendor($this->vendor)
            ->pending()
            ->create();

        $response = $this->actingAs($this->otherCustomer)
            ->postJson("/reviews/{$review->id}/vote", [
                'is_helpful' => true,
            ]);

        $response->assertStatus(403);
    }

    // ==================== Vendor Response Tests ====================

    public function test_vendor_can_respond_to_approved_review(): void
    {
        $review = Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->create();

        $response = $this->actingAs($this->vendor)
            ->postJson("/reviews/{$review->id}/respond", [
                'response' => 'Thank you for your kind words! We appreciate your business.',
            ]);

        $response->assertStatus(200);

        $review->refresh();
        $this->assertNotNull($review->vendor_response);
        $this->assertNotNull($review->vendor_responded_at);
    }

    public function test_vendor_cannot_respond_to_pending_review(): void
    {
        $review = Review::factory()
            ->forVendor($this->vendor)
            ->pending()
            ->create();

        $response = $this->actingAs($this->vendor)
            ->postJson("/reviews/{$review->id}/respond", [
                'response' => 'Thank you!',
            ]);

        $response->assertStatus(403);
    }

    public function test_other_vendor_cannot_respond(): void
    {
        $otherVendor = User::factory()->create(['role' => 'vendor']);

        $review = Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->create();

        $response = $this->actingAs($otherVendor)
            ->postJson("/reviews/{$review->id}/respond", [
                'response' => 'I am not the vendor!',
            ]);

        $response->assertStatus(403);
    }

    // ==================== Review Update/Delete Tests ====================

    public function test_customer_can_update_own_review(): void
    {
        $review = Review::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->approved()
            ->create();

        $response = $this->actingAs($this->customer)
            ->putJson("/reviews/{$review->id}", [
                'rating' => 4,
                'title' => 'Updated title',
                'comment' => 'Updated comment',
            ]);

        $response->assertStatus(200);

        $review->refresh();
        $this->assertEquals(4, $review->rating);
        $this->assertEquals('Updated title', $review->title);
        $this->assertEquals(ReviewStatusEnum::APPROVED, $review->status); // Auto-approved
    }

    public function test_customer_can_delete_own_review(): void
    {
        $review = Review::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $response = $this->actingAs($this->customer)
            ->deleteJson("/reviews/{$review->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('reviews', ['id' => $review->id]);
    }

    public function test_customer_cannot_delete_others_review(): void
    {
        $review = Review::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $response = $this->actingAs($this->otherCustomer)
            ->deleteJson("/reviews/{$review->id}");

        $response->assertStatus(403);
    }

    public function test_vendor_cannot_delete_review_left_on_them(): void
    {
        $review = Review::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $response = $this->actingAs($this->vendor)
            ->deleteJson("/reviews/{$review->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'deleted_at' => null]);
    }

    public function test_admin_can_delete_any_review(): void
    {
        $review = Review::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/reviews/{$review->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('reviews', ['id' => $review->id]);
    }

    // ==================== Can Review Check Tests ====================

    public function test_can_review_check_returns_true_when_eligible(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson("/vendors/{$this->vendor->slug}/reviews/can-review");

        $response->assertStatus(200)
            ->assertJson([
                'can_review' => true,
                'has_reviewed' => false,
            ]);
    }

    public function test_can_review_check_returns_false_after_review(): void
    {
        Review::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $response = $this->actingAs($this->customer)
            ->getJson("/vendors/{$this->vendor->slug}/reviews/can-review");

        $response->assertStatus(200)
            ->assertJson([
                'can_review' => false,
                'has_reviewed' => true,
            ]);
    }

    public function test_can_review_check_returns_false_without_interaction(): void
    {
        $response = $this->actingAs($this->otherCustomer)
            ->getJson("/vendors/{$this->vendor->slug}/reviews/can-review");

        $response->assertStatus(200)
            ->assertJson([
                'can_review' => false,
                'has_reviewed' => false,
            ]);
    }

    // ==================== Pagination Tests ====================

    public function test_reviews_are_paginated(): void
    {
        Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->count(25)
            ->create();

        $response = $this->getJson("/vendors/{$this->vendor->slug}/reviews?per_page=10");

        $response->assertStatus(200)
            ->assertJsonCount(10, 'reviews')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 3);
    }

    // ==================== Authorization Tests ====================

    public function test_unauthenticated_user_cannot_submit_review(): void
    {
        $response = $this->postJson('/reviews', [
            'vendor_id' => $this->vendor->id,
            'rating' => 5,
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_can_view_public_reviews(): void
    {
        Review::factory()
            ->forVendor($this->vendor)
            ->approved()
            ->create();

        $response = $this->getJson("/vendors/{$this->vendor->slug}/reviews");

        $response->assertStatus(200);
    }
}
