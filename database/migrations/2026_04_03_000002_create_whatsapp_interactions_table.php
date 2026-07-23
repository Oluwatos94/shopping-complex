<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\WhatsApp\Enums\WhatsAppInteractionEventEnum;
use ModulesShoppingComplex\WhatsApp\Models\WhatsAppInteraction;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(WhatsAppInteraction::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20);
            $table->enum('event_type', WhatsAppInteractionEventEnum::values());
            $table->string('search_query')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained(User::getTableName())->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained(Product::getTableName())->onDelete('set null');
            $table->decimal('buyer_latitude', 10, 7)->nullable();
            $table->decimal('buyer_longitude', 10, 7)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('vendor_id');
            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(WhatsAppInteraction::getTableName());
    }
};
