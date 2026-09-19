<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Billing\Enums\AnchorTransactionKindEnum;
use ModulesShoppingComplex\Billing\Models\AnchorTransaction;
use ModulesShoppingComplex\Billing\Models\CoinPurchase;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(AnchorTransaction::getTableName(), function (Blueprint $table) {
            $table->foreignId('coin_purchase_id')->nullable()->after('plan_id')
                ->constrained(CoinPurchase::getTableName())->nullOnDelete();
        });

        $kinds = "'".implode("','", AnchorTransactionKindEnum::values())."'";
        DB::statement('ALTER TABLE '.AnchorTransaction::getTableName()." MODIFY kind ENUM({$kinds}) NOT NULL");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE '.AnchorTransaction::getTableName()." MODIFY kind ENUM('deposit','mpp_charge') NOT NULL");

        Schema::table(AnchorTransaction::getTableName(), function (Blueprint $table) {
            $table->dropConstrainedForeignId('coin_purchase_id');
        });
    }
};
