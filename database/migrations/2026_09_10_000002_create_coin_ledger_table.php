<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Models\CoinLedgerEntry;
use ModulesShoppingComplex\Identity\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(CoinLedgerEntry::getTableName(), function (Blueprint $table) {
            $table->id();
            // Restrict, not cascade: a cascade deletes committed financial history
            // without ever passing through the model's append-only guard.
            $table->foreignId('vendor_id')->constrained(User::getTableName())->restrictOnDelete();
            $table->enum('type', CoinLedgerTypeEnum::values());
            $table->integer('amount');
            $table->unsignedInteger('balance_after');
            $table->foreignId('lot_id')->nullable()->constrained(CoinLedgerEntry::getTableName())->nullOnDelete();
            $table->nullableMorphs('reference');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['expires_at', 'vendor_id']);
            $table->index(['vendor_id', 'lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(CoinLedgerEntry::getTableName());
    }
};
