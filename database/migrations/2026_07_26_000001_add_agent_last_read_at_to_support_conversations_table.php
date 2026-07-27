<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Support\Models\SupportConversation;

return new class extends Migration
{
    public function up(): void
    {
        $table = SupportConversation::getTableName();

        if (Schema::hasColumn($table, 'agent_last_read_at')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->timestamp('agent_last_read_at')->nullable()->after('agent_id');
        });
    }

    public function down(): void
    {
        $table = SupportConversation::getTableName();

        if (! Schema::hasColumn($table, 'agent_last_read_at')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('agent_last_read_at');
        });
    }
};
