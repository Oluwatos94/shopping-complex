<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\Order;
use ModulesShoppingComplex\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Order::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->decimal('total', 10, 2);
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled']);
            $table->timestamps();

            $table->index(['customer_id', 'vendor_id'], 'orders_customer_vendor_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Order::getTableName());
    }
};
