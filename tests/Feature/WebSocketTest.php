<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use ModulesShoppingComplex\Models\Order;
use ModulesShoppingComplex\Models\OrderItem;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\User;
use Tests\TestCase;

class WebSocketTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendor;

    protected User $customer;

    protected User $otherVendor;

    protected Product $product;

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var User $vendor */
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);
        $this->vendor = $vendor;

        /** @var User $customer */
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $this->customer = $customer;

        /** @var User $otherVendor */
        $otherVendor = User::factory()->create([
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);
        $this->otherVendor = $otherVendor;

        // Create a product owned by the vendor
        $this->product = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        // Create an order with this product - ensure vendor is set for chat channel tests
        $this->order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
            'status' => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => $this->product->price,
        ]);
    }

    public function test_user_can_authorize_for_their_own_private_channel(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-App.Models.User.{$this->customer->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(200);
    }

    public function test_user_cannot_authorize_for_another_users_private_channel(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-App.Models.User.{$this->vendor->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_customer_can_authorize_for_their_order_channel(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-orders.{$this->order->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(200);
    }

    public function test_vendor_can_authorize_for_order_channel_with_their_product(): void
    {
        $response = $this->actingAs($this->vendor)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-orders.{$this->order->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(200);
    }

    public function test_vendor_cannot_authorize_for_other_vendors_order_channel(): void
    {
        $response = $this->actingAs($this->otherVendor)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-orders.{$this->order->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_customer_cannot_authorize_for_another_customers_order_channel(): void
    {
        /** @var User $otherCustomer */
        $otherCustomer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($otherCustomer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-orders.{$this->order->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_vendor_can_authorize_for_their_own_vendor_channel(): void
    {
        $response = $this->actingAs($this->vendor)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-vendors.{$this->vendor->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(200);
    }

    public function test_vendor_cannot_authorize_for_another_vendors_channel(): void
    {
        $response = $this->actingAs($this->otherVendor)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-vendors.{$this->vendor->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_customer_cannot_authorize_for_vendor_channel(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-vendors.{$this->vendor->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_customer_with_same_id_cannot_access_vendor_channel(): void
    {
        // Create a customer with specific ID
        /** @var User $customerWithId5 */
        $customerWithId5 = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // Try to access vendor channel with matching ID (security hole test)
        $response = $this->actingAs($customerWithId5)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-vendors.{$customerWithId5->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_vendor_can_authorize_for_their_product_channel(): void
    {
        $response = $this->actingAs($this->vendor)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-products.{$this->product->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(200);
    }

    public function test_vendor_cannot_authorize_for_other_vendors_product_channel(): void
    {
        $response = $this->actingAs($this->otherVendor)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-products.{$this->product->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_customer_cannot_authorize_for_product_channel(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-products.{$this->product->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_users_can_authorize_for_chat_channel_if_participants(): void
    {
        // Customer should be able to authorize
        $response = $this->actingAs($this->customer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-chat.{$this->customer->id}.{$this->vendor->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(200);

        // Vendor should be able to authorize
        $response = $this->actingAs($this->vendor)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-chat.{$this->customer->id}.{$this->vendor->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(200);
    }

    public function test_users_cannot_authorize_for_chat_channel_if_not_participants(): void
    {
        $response = $this->actingAs($this->otherVendor)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-chat.{$this->customer->id}.{$this->vendor->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_customers_cannot_chat_with_each_other(): void
    {
        /** @var User $otherCustomer */
        $otherCustomer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-chat.{$this->customer->id}.{$otherCustomer->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_vendors_cannot_chat_with_each_other(): void
    {
        $response = $this->actingAs($this->vendor)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-chat.{$this->vendor->id}.{$this->otherVendor->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_chat_requires_business_relationship(): void
    {
        /** @var User $randomVendor */
        $randomVendor = User::factory()->create([
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);

        // Customer tries to chat with vendor they never ordered from
        $response = $this->actingAs($this->customer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-chat.{$this->customer->id}.{$randomVendor->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_vendor_can_authorize_for_online_vendors_presence_channel(): void
    {
        $response = $this->actingAs($this->vendor)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'presence-online-vendors',
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(200);
    }

    public function test_customer_cannot_authorize_for_online_vendors_presence_channel(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'presence-online-vendors',
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }

    public function test_users_can_authorize_for_customer_support_presence_channel(): void
    {
        // Customer should be able to authorize
        $response = $this->actingAs($this->customer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'presence-customer-support',
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(200);

        // Vendor should be able to authorize
        $response = $this->actingAs($this->vendor)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'presence-customer-support',
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(200);
    }

    public function test_channel_authentication_requires_authenticated_user(): void
    {
        $this->postJson('/broadcasting/auth', [
            'channel_name' => "private-orders.{$this->order->id}",
            'socket_id' => '123.456',
        ])
            ->assertStatus(403);
    }

    public function test_authenticated_user_can_authorize_for_valid_channel(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-orders.{$this->order->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(200);
    }

    public function test_authenticated_user_cannot_authorize_for_invalid_channel(): void
    {
        $otherOrder = Order::factory()->create([
            'customer_id' => $this->vendor->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-orders.{$otherOrder->id}",
                'socket_id' => '123.456',
            ]);

        $response->assertStatus(403);
    }
}
