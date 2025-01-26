<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('status_id')->constrained('orders_status')->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->string('payment_method')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_status')->default('pending'); // e.g., 'pending', 'paid', 'failed'
            $table->string('shipping_status')->default('pending'); // e.g., 'pending', 'shipped', 'delivered'
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
