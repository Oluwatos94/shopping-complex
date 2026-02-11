<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\ProductView;
use ModulesShoppingComplex\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(ProductView::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained(Product::getTableName())->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->foreignId('viewer_id')->nullable()->constrained(User::getTableName())->onDelete('set null');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ProductView::getTableName());
    }
};
