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
            $table->string('issued_visitor_id', 64)->nullable()->after('buyer_identity');
            $table->string('user_agent', 255)->nullable()->after('ip_address');
            $table->index('issued_visitor_id');
        });
    }

    public function down(): void
    {
        Schema::table(ContactClick::getTableName(), function (Blueprint $table) {
            $table->dropIndex(['issued_visitor_id']);
            $table->dropColumn(['issued_visitor_id', 'user_agent']);
        });
    }
};
