<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Billing\Models\StellarWallet;
use ModulesShoppingComplex\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(StellarWallet::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained(User::getTableName())->onDelete('cascade');
            $table->string('public_key')->unique();
            $table->text('encrypted_secret');
            $table->string('network')->default('testnet');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(StellarWallet::getTableName());
    }
};
