<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\AnchorTransaction;
use ModulesShoppingComplex\Models\Enums\AnchorTransactionKindEnum;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(AnchorTransaction::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->foreignId('plan_id')->nullable()->constrained(SubscriptionPlan::getTableName())->nullOnDelete();
            $table->string('sep24_id')->nullable()->unique();
            $table->enum('kind', AnchorTransactionKindEnum::values());
            $table->string('billing_period')->nullable();
            $table->string('status');
            $table->string('reconciliation')->nullable();
            $table->decimal('amount', 20, 7)->nullable();
            $table->string('asset')->default('NGNC');
            $table->string('stellar_tx_hash')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'kind', 'status']);
            $table->unique(['vendor_id', 'kind', 'billing_period'], 'anchor_tx_vendor_kind_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(AnchorTransaction::getTableName());
    }
};
