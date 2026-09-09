<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Billing\Models\ContactLink;
use ModulesShoppingComplex\Identity\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(ContactClick::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_link_id')->constrained(ContactLink::getTableName())->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained(User::getTableName())->cascadeOnDelete();
            $table->enum('source', ViewSourceEnum::values());
            $table->string('buyer_identity', 64)->nullable();
            $table->boolean('is_billable')->default(true);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['buyer_identity', 'vendor_id', 'created_at']);
            $table->index(['is_billable', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ContactClick::getTableName());
    }
};
