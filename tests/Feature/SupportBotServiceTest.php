<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ModulesShoppingComplex\Billing\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Billing\Enums\VendorSubscriptionStatusEnum;
use ModulesShoppingComplex\Billing\Models\SubscriptionPlan;
use ModulesShoppingComplex\Billing\Models\VendorSubscription;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Shared\Contracts\AiChatClient;
use ModulesShoppingComplex\Support\Enums\SupportMessageRoleEnum;
use ModulesShoppingComplex\Support\Models\SupportConversation;
use ModulesShoppingComplex\Support\Models\SupportMessage;
use ModulesShoppingComplex\Support\Services\SupportBotService;
use RuntimeException;
use Tests\TestCase;

class FakeAiChatClient implements AiChatClient
{
    /** @var array<int, array<string, mixed>> */
    public array $payloads = [];

    /** @var array<int, array<string, mixed>> */
    private array $queue = [];

    public function __construct(
        private string $replyText = 'Hello! How can I help you today?',
        private bool $shouldThrow = false,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $responses
     */
    public function queueResponses(array $responses): void
    {
        $this->queue = $responses;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createMessage(array $payload): array
    {
        $this->payloads[] = $payload;

        if ($this->shouldThrow) {
            throw new RuntimeException('Gemini API error (HTTP 500).');
        }

        if ($this->queue !== []) {
            return array_shift($this->queue);
        }

        return [
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => $this->replyText]],
        ];
    }
}

class SupportBotServiceTest extends TestCase
{
    use RefreshDatabase;

    private function bindFakeAi(string $replyText = 'Hello! How can I help you today?', bool $shouldThrow = false): FakeAiChatClient
    {
        $fake = new FakeAiChatClient($replyText, $shouldThrow);
        $this->app->instance(AiChatClient::class, $fake);

        return $fake;
    }

    public function test_reply_persists_user_then_assistant_message(): void
    {
        $this->bindFakeAi('You can find vendors on the products page.');
        $conversation = SupportConversation::factory()->create();

        $service = $this->app->make(SupportBotService::class);
        $reply = $service->reply($conversation, 'How do I find a vendor?');

        $this->assertSame(SupportMessageRoleEnum::ASSISTANT, $reply->role);
        $this->assertSame('You can find vendors on the products page.', $reply->content);
        $this->assertNull($reply->sender_id);

        $messages = SupportMessage::query()
            ->where('support_conversation_id', $conversation->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $messages);
        $this->assertSame(SupportMessageRoleEnum::USER, $messages[0]->role);
        $this->assertSame('How do I find a vendor?', $messages[0]->content);
        $this->assertSame($conversation->user_id, $messages[0]->sender_id);
        $this->assertSame(SupportMessageRoleEnum::ASSISTANT, $messages[1]->role);

        $this->assertNotNull($conversation->fresh()->last_message_at);
    }

    public function test_history_is_ordered_and_role_mapped(): void
    {
        $fake = $this->bindFakeAi();
        $conversation = SupportConversation::factory()->create();
        $agent = User::factory()->create();

        SupportMessage::factory()->forConversation($conversation)->create(['content' => 'My order is late']);
        SupportMessage::factory()->forConversation($conversation)->fromAssistant()->create(['content' => 'Let me check that for you']);
        SupportMessage::factory()->forConversation($conversation)->fromAgent($agent)->create(['content' => 'Agent here, following up']);

        $this->app->make(SupportBotService::class)->reply($conversation, 'Any update?');

        $this->assertCount(1, $fake->payloads);
        $this->assertNotEmpty($fake->payloads[0]['system']);

        $history = $fake->payloads[0]['messages'];
        $this->assertSame(
            [
                ['role' => 'user', 'content' => 'My order is late'],
                ['role' => 'assistant', 'content' => 'Let me check that for you'],
                ['role' => 'assistant', 'content' => 'Agent here, following up'],
                ['role' => 'user', 'content' => 'Any update?'],
            ],
            $history,
        );
    }

    public function test_history_is_trimmed_to_a_window(): void
    {
        $fake = $this->bindFakeAi();
        $conversation = SupportConversation::factory()->create();

        SupportMessage::factory()->count(25)->forConversation($conversation)->create();

        $this->app->make(SupportBotService::class)->reply($conversation, 'Latest question');

        $history = $fake->payloads[0]['messages'];
        $this->assertCount(20, $history);
        $this->assertSame('Latest question', end($history)['content']);
    }

    public function test_tool_use_executes_tool_and_persists_final_reply(): void
    {
        $fake = $this->bindFakeAi();
        $fake->queueResponses([
            [
                'stop_reason' => 'tool_use',
                'content' => [
                    ['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'search_products', 'input' => ['query' => 'sneakers']],
                ],
            ],
            [
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => 'Yes, we have Red Sneakers for ₦500.']],
            ],
        ]);

        Product::factory()->create(['name' => 'Red Sneakers', 'price' => 500]);
        $conversation = SupportConversation::factory()->create();

        $reply = $this->app->make(SupportBotService::class)->reply($conversation, 'Do you sell sneakers?', 6.5244, 3.3792);

        $this->assertSame('Yes, we have Red Sneakers for ₦500.', $reply->content);
        $this->assertCount(2, $fake->payloads);
        $this->assertNotEmpty($fake->payloads[0]['tools']);

        $followUp = $fake->payloads[1]['messages'];
        $toolResultMessage = end($followUp);
        $this->assertSame('user', $toolResultMessage['role']);
        $this->assertSame('tool_result', $toolResultMessage['content'][0]['type']);
        $this->assertSame('tool_1', $toolResultMessage['content'][0]['tool_use_id']);
        $this->assertStringContainsString('Red Sneakers', $toolResultMessage['content'][0]['content']);

        $this->assertSame(
            'Yes, we have Red Sneakers for ₦500.',
            SupportMessage::query()
                ->where('support_conversation_id', $conversation->id)
                ->orderBy('id', 'desc')
                ->value('content'),
        );
    }

    public function test_search_without_location_asks_for_location_first(): void
    {
        $category = \ModulesShoppingComplex\Catalog\Models\Category::factory()->create(['name' => 'Footwear']);
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'business_name' => 'Shoe Palace',
            'category_id' => $category->id,
        ]);
        Product::factory()->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'Leather shoes',
            'tags' => ['shoes'],
        ]);

        foreach (['search_vendors', 'search_products'] as $tool) {
            $fake = $this->bindFakeAi();
            $fake->queueResponses([
                [
                    'stop_reason' => 'tool_use',
                    'content' => [
                        ['type' => 'tool_use', 'id' => 'tool_1', 'name' => $tool, 'input' => ['query' => 'shoes']],
                    ],
                ],
                [
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'Please tap the location pin so I can find shoes near you.']],
                ],
            ]);

            $conversation = SupportConversation::factory()->create();

            $this->app->make(SupportBotService::class)->reply($conversation, 'I need shoes');

            $toolResult = (string) end($fake->payloads[1]['messages'])['content'][0]['content'];
            $this->assertStringContainsString('location pin button', $toolResult, $tool);
            $this->assertStringContainsString('allow_global', $toolResult, $tool);
            $this->assertStringNotContainsString('Shoe Palace', $toolResult, $tool);
        }
    }

    public function test_vendor_search_without_location_with_allow_global_searches_platform_wide(): void
    {
        $fake = $this->bindFakeAi();
        $fake->queueResponses([
            [
                'stop_reason' => 'tool_use',
                'content' => [
                    ['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'search_vendors', 'input' => ['query' => 'Shoe Palace', 'allow_global' => true]],
                ],
            ],
            [
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => 'Shoe Palace is on Jiidaa.']],
            ],
        ]);

        $category = \ModulesShoppingComplex\Catalog\Models\Category::factory()->create(['name' => 'Footwear']);
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'business_name' => 'Shoe Palace',
            'category_id' => $category->id,
        ]);
        Product::factory()->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'Leather shoes',
            'tags' => ['shoes'],
        ]);

        $conversation = SupportConversation::factory()->create();

        $this->app->make(SupportBotService::class)->reply($conversation, 'Do you have Shoe Palace?');

        $toolResult = (string) end($fake->payloads[1]['messages'])['content'][0]['content'];
        $this->assertStringContainsString('Shoe Palace', $toolResult);
        $this->assertStringContainsString('across all of Jiidaa', $toolResult);
    }

    public function test_vendor_search_without_location_and_no_match_reports_nothing_found(): void
    {
        $fake = $this->bindFakeAi();
        $fake->queueResponses([
            [
                'stop_reason' => 'tool_use',
                'content' => [
                    ['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'search_vendors', 'input' => ['query' => 'unicorn saddles', 'allow_global' => true]],
                ],
            ],
            [
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => 'Nothing matches that yet.']],
            ],
        ]);

        $conversation = SupportConversation::factory()->create();

        $this->app->make(SupportBotService::class)->reply($conversation, 'Any unicorn saddle vendors?');

        $toolResult = (string) end($fake->payloads[1]['messages'])['content'][0]['content'];
        $this->assertStringContainsString('No vendor on Jiidaa currently matches', $toolResult);
    }

    public function test_vendor_search_with_location_returns_nearby_vendors_with_distance(): void
    {
        $fake = $this->bindFakeAi();
        $fake->queueResponses([
            [
                'stop_reason' => 'tool_use',
                'content' => [
                    ['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'search_vendors', 'input' => ['query' => 'shoes']],
                ],
            ],
            [
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => 'Shoe Palace is 0.0 km away.']],
            ],
        ]);

        $category = \ModulesShoppingComplex\Catalog\Models\Category::factory()->create(['name' => 'Footwear']);
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'business_name' => 'Shoe Palace',
            'category_id' => $category->id,
        ]);
        \ModulesShoppingComplex\Models\Address::create([
            'user_id' => $vendor->id,
            'street' => '1 Marina Rd',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'latitude' => 6.5244,
            'longitude' => 3.3792,
        ]);
        Product::factory()->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'Leather shoes',
            'tags' => ['shoes'],
        ]);

        $conversation = SupportConversation::factory()->create();

        $this->app->make(SupportBotService::class)
            ->reply($conversation, 'I need a shoe vendor', 6.5244, 3.3792);

        $toolResult = (string) end($fake->payloads[1]['messages'])['content'][0]['content'];
        $this->assertStringContainsString('Shoe Palace', $toolResult);
        $this->assertStringContainsString('km away', $toolResult);
        $this->assertStringContainsString('/vendors/'.$vendor->slug, $toolResult);
    }

    public function test_payment_status_is_scoped_to_the_conversations_user(): void
    {
        $owner = User::factory()->create(['role' => 'vendor']);
        $other = User::factory()->create(['role' => 'vendor']);

        $plan = SubscriptionPlan::create([
            'name' => 'Gold',
            'slug' => 'gold',
            'price' => 5000,
            'product_limit' => 50,
            'search_priority' => 1,
            'features' => [],
            'is_active' => true,
        ]);

        VendorSubscription::create([
            'vendor_id' => $owner->id,
            'plan_id' => $plan->id,
            'status' => VendorSubscriptionStatusEnum::ACTIVE,
            'payment_method' => PaymentMethodEnum::PAYSTACK,
            'started_at' => now(),
            'expires_at' => now()->addMonth(),
            'payment_reference' => 'REF-OWNER-1',
            'amount_paid' => 5000,
        ]);

        $paymentLookup = [
            [
                'stop_reason' => 'tool_use',
                'content' => [
                    ['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'get_payment_status', 'input' => ['reference' => 'REF-OWNER-1']],
                ],
            ],
            ['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Done.']]],
        ];

        $fake = $this->bindFakeAi();
        $fake->queueResponses($paymentLookup);
        $ownerConversation = SupportConversation::factory()->forUser($owner)->create();
        $this->app->make(SupportBotService::class)->reply($ownerConversation, 'Check my payment REF-OWNER-1');

        $ownerResult = end($fake->payloads[1]['messages'])['content'][0]['content'];
        $this->assertStringContainsString('Gold plan', $ownerResult);

        $fake = $this->bindFakeAi();
        $fake->queueResponses($paymentLookup);
        $otherConversation = SupportConversation::factory()->forUser($other)->create();
        $this->app->make(SupportBotService::class)->reply($otherConversation, 'Check payment REF-OWNER-1');

        $otherResult = end($fake->payloads[1]['messages'])['content'][0]['content'];
        $this->assertStringContainsString('No payment with reference', $otherResult);
        $this->assertStringNotContainsString('Gold', $otherResult);
    }

    public function test_account_tools_require_a_signed_in_user(): void
    {
        $fake = $this->bindFakeAi();
        $fake->queueResponses([
            [
                'stop_reason' => 'tool_use',
                'content' => [
                    ['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'get_my_subscription', 'input' => []],
                ],
            ],
            ['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Please log in.']]],
        ]);

        $conversation = SupportConversation::factory()->guest()->create();

        $this->app->make(SupportBotService::class)->reply($conversation, 'What plan am I on?');

        $result = end($fake->payloads[1]['messages'])['content'][0]['content'];
        $this->assertStringContainsString('not signed in', $result);
    }

    public function test_tool_loop_is_capped(): void
    {
        $toolUse = [
            'stop_reason' => 'tool_use',
            'content' => [
                ['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'search_products', 'input' => ['query' => 'shoes']],
            ],
        ];

        $fake = $this->bindFakeAi();
        $fake->queueResponses(array_fill(0, 12, $toolUse));

        $conversation = SupportConversation::factory()->create();

        $reply = $this->app->make(SupportBotService::class)->reply($conversation, 'Loop forever');

        $this->assertCount(11, $fake->payloads);
        $this->assertStringContainsString('human agent', $reply->content);
    }

    public function test_ai_failure_returns_friendly_fallback(): void
    {
        $this->bindFakeAi(shouldThrow: true);
        $conversation = SupportConversation::factory()->create();

        $reply = $this->app->make(SupportBotService::class)->reply($conversation, 'Help me please');

        $this->assertSame(SupportMessageRoleEnum::ASSISTANT, $reply->role);
        $this->assertStringContainsString('human agent', $reply->content);

        $this->assertSame(
            2,
            SupportMessage::query()->where('support_conversation_id', $conversation->id)->count(),
        );
    }
}
