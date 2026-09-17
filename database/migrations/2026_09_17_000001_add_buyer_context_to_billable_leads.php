<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Billing\Models\BillableLead;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(BillableLead::getTableName(), function (Blueprint $table) {
            $table->string('buyer_search', 120)->nullable()->after('delivered_number');
            $table->string('buyer_area', 120)->nullable()->after('buyer_search');
        });
    }

    public function down(): void
    {
        Schema::table(BillableLead::getTableName(), function (Blueprint $table) {
            $table->dropColumn(['buyer_search', 'buyer_area']);
        });
    }
};
