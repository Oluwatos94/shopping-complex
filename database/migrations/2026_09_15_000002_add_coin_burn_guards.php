<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Identity\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(User::getTableName(), function (Blueprint $table) {
            $table->unsignedInteger('daily_coin_cap')->nullable()->after('lead_coin_cost_override');
        });

        Schema::table(BillableLead::getTableName(), function (Blueprint $table) {
            $table->string('unbilled_reason')->nullable()->after('state');
            $table->index(['unbilled_reason', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table(User::getTableName(), function (Blueprint $table) {
            $table->dropColumn('daily_coin_cap');
        });

        Schema::table(BillableLead::getTableName(), function (Blueprint $table) {
            $table->dropIndex(['unbilled_reason', 'created_at']);
            $table->dropColumn('unbilled_reason');
        });
    }
};
