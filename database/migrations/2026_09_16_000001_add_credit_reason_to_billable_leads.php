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
            $table->string('credit_reason')->nullable()->after('unbilled_reason');
            $table->string('delivered_number', 20)->nullable()->after('credit_reason');
            $table->index(['credit_reason', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table(BillableLead::getTableName(), function (Blueprint $table) {
            $table->dropIndex(['credit_reason', 'created_at']);
            $table->dropColumn(['credit_reason', 'delivered_number']);
        });
    }
};
