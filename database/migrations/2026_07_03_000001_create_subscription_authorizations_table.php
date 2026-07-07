<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\Enums\SubscriptionAuthorizationStatusEnum;
use ModulesShoppingComplex\Models\SubscriptionAuthorization;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(SubscriptionAuthorization::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained(User::getTableName())->onDelete('cascade');
            $table->foreignId('plan_id')->constrained(SubscriptionPlan::getTableName())->cascadeOnDelete();
            $table->decimal('monthly_cap', 20, 2);
            $table->dateTime('valid_until');
            $table->enum('status', SubscriptionAuthorizationStatusEnum::values())
                ->default(SubscriptionAuthorizationStatusEnum::ACTIVE->value);
            $table->dateTime('consent_at');
            $table->timestamps();

            $table->index(['status', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(SubscriptionAuthorization::getTableName());
    }
};
