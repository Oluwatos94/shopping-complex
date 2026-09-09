<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Identity\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(BillableLead::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_click_id')->nullable()->constrained(ContactClick::getTableName())->nullOnDelete();
            $table->foreignId('vendor_id')->constrained(User::getTableName())->cascadeOnDelete();
            $table->string('buyer_identity', 64);
            $table->enum('channel', ViewSourceEnum::values());
            $table->unsignedInteger('coins_charged');
            $table->enum('state', BillableLeadStateEnum::values());
            $table->timestamp('window_start');
            $table->unsignedInteger('repeat_count')->default(0);
            $table->timestamp('last_click_at')->nullable();
            $table->timestamps();

            $table->unique(['buyer_identity', 'vendor_id', 'window_start']);
            $table->index(['vendor_id', 'window_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(BillableLead::getTableName());
    }
};
