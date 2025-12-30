<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\Order;
use ModulesShoppingComplex\Models\OrderItem;
use ModulesShoppingComplex\Models\Product;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(OrderItem::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained(Order::getTableName())->onDelete('cascade');
            $table->foreignId('product_id')->constrained(Product::getTableName())->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price', 10, 2); // Snapshot of price at time of order
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(OrderItem::getTableName());
    }
};
