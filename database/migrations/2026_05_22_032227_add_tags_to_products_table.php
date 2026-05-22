<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\Product;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(Product::getTableName(), function (Blueprint $table) {
            $table->json('tags')->nullable()->after('is_returnable');
        });
    }

    public function down(): void
    {
        Schema::table(Product::getTableName(), function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
