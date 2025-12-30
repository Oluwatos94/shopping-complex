<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\CustomerWishlist;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(CustomerWishlist::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(User::getTableName())->cascadeOnDelete();
            $table->foreignId('product_id')->constrained(Product::getTableName())->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(CustomerWishlist::getTableName());
    }
};
