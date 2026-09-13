<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Billing\Models\CoinWallet;
use ModulesShoppingComplex\Identity\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(CoinWallet::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained(User::getTableName())->cascadeOnDelete();
            $table->unsignedInteger('balance')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(CoinWallet::getTableName());
    }
};
