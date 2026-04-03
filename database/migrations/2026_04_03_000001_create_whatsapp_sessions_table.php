<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\Enums\WhatsAppSessionStateEnum;
use ModulesShoppingComplex\Models\WhatsAppSession;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(WhatsAppSession::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20)->unique();
            $table->enum('state', WhatsAppSessionStateEnum::values())->default(WhatsAppSessionStateEnum::IDLE->value);
            $table->json('data')->nullable();
            $table->timestamp('last_active_at')->useCurrent();
            $table->timestamps();

            $table->index('last_active_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(WhatsAppSession::getTableName());
    }
};
