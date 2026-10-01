<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;

/**
 * Accept-to-charge leads: a lead can now wait for the vendor (pending) and end
 * without a charge (declined / expired). The timestamps record when the vendor
 * answered and until when they may still accept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(BillableLead::getTableName(), function (Blueprint $table) {
            $table->enum('state', BillableLeadStateEnum::values())->change();
            $table->timestamp('expires_at')->nullable()->after('last_click_at');
            $table->timestamp('accepted_at')->nullable()->after('expires_at');
            $table->timestamp('responded_at')->nullable()->after('accepted_at');
            $table->index(['state', 'expires_at']);
            $table->index(['buyer_identity', 'state']);
        });
    }

    public function down(): void
    {
        // Rows in the new states have no legacy equivalent; fold them into
        // 'unbilled' so the narrower enum can be restored.
        DB::table(BillableLead::getTableName())
            ->whereIn('state', ['pending', 'declined', 'expired'])
            ->update(['state' => 'unbilled']);

        Schema::table(BillableLead::getTableName(), function (Blueprint $table) {
            $table->dropIndex(['state', 'expires_at']);
            $table->dropIndex(['buyer_identity', 'state']);
            $table->dropColumn(['expires_at', 'accepted_at', 'responded_at']);
            $table->enum('state', ['charged', 'unbilled'])->change();
        });
    }
};
