<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Conversation::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained(Product::getTableName())->onDelete('set null');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            // Ensure unique conversation between customer and vendor (optionally per product)
            $table->unique(['customer_id', 'vendor_id', 'product_id'], 'unique_conversation');

            // Indexes for efficient querying
            $table->index(['customer_id', 'last_message_at']);
            $table->index(['vendor_id', 'last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Conversation::getTableName());
    }
};
