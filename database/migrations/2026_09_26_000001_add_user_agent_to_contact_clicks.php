<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Billing\Models\ContactClick;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(ContactClick::getTableName(), function (Blueprint $table) {
            $table->string('user_agent', 255)->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table(ContactClick::getTableName(), function (Blueprint $table) {
            $table->dropColumn('user_agent');
        });
    }
};
