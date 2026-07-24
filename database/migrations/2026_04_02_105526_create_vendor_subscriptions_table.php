<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Billing\Enums\VendorSubscriptionStatusEnum;
use ModulesShoppingComplex\Billing\Models\SubscriptionPlan;
use ModulesShoppingComplex\Billing\Models\VendorSubscription;
use ModulesShoppingComplex\Identity\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(VendorSubscription::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->foreignId('plan_id')->constrained(SubscriptionPlan::getTableName());
            $table->enum('status', VendorSubscriptionStatusEnum::values())->default(VendorSubscriptionStatusEnum::ACTIVE->value);
            $table->dateTime('started_at');
            $table->dateTime('expires_at');
            $table->string('payment_reference')->nullable()->unique();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VendorSubscription::getTableName());
    }
};
