<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Identity\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(User::getTableName(), function (Blueprint $table) {
            $table->string('referral_code', 32)->nullable()->unique()->after('available_hours');
            $table->foreignId('referred_by')->nullable()->after('referral_code')
                ->constrained(User::getTableName())->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table(User::getTableName(), function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by');
            $table->dropUnique(['referral_code']);
            $table->dropColumn('referral_code');
        });
    }
};
