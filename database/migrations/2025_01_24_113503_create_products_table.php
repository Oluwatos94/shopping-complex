<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Product::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->foreignId('category_id')->constrained(Category::getTableName())->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('pay_on_delivery')->default(false);
            $table->boolean('is_returnable')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'is_active', 'created_at'], 'idx_products_vendor_active_created');
            $table->index(['category_id', 'is_active', 'created_at'], 'idx_products_category_active_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Product::getTableName());
    }
};
