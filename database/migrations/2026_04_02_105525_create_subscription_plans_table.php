<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\SubscriptionPlan;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(SubscriptionPlan::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->decimal('price', 10, 2);
            $table->integer('product_limit');
            $table->integer('search_priority')->default(0);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(SubscriptionPlan::getTableName());
    }
};
