<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Models\VendorSubscription;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(VendorSubscription::getTableName(), function (Blueprint $table) {
            $table->enum('payment_method', PaymentMethodEnum::values())
                ->default(PaymentMethodEnum::PAYSTACK->value)
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table(VendorSubscription::getTableName(), function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
