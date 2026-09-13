<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Billing\Enums\CoinPurchaseStatusEnum;
use ModulesShoppingComplex\Billing\Models\CoinPurchase;
use ModulesShoppingComplex\Identity\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(CoinPurchase::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained(User::getTableName())->cascadeOnDelete();
            $table->string('pack');
            $table->unsignedInteger('price');
            $table->unsignedInteger('coins');
            $table->unsignedInteger('bonus_coins')->default(0);
            $table->string('reference')->unique();
            $table->enum('status', CoinPurchaseStatusEnum::values())->default(CoinPurchaseStatusEnum::PENDING->value);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(CoinPurchase::getTableName());
    }
};
