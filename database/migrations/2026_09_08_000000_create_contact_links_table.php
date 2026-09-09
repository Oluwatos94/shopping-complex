<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Models\ContactLink;
use ModulesShoppingComplex\Identity\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(ContactLink::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->string('token', 32)->unique();
            $table->foreignId('vendor_id')->constrained(User::getTableName())->cascadeOnDelete();
            $table->enum('source', ViewSourceEnum::values());
            $table->string('buyer_identity', 64)->nullable();
            $table->string('prefilled_message', 500)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ContactLink::getTableName());
    }
};
