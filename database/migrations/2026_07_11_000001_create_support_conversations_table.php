<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\Enums\SupportConversationStatusEnum;
use ModulesShoppingComplex\Models\SupportConversation;
use ModulesShoppingComplex\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(SupportConversation::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained(User::getTableName())->onDelete('cascade');
            $table->enum('status', SupportConversationStatusEnum::values())
                ->default(SupportConversationStatusEnum::BOT->value);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained(User::getTableName())->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'last_message_at']);
            $table->index(['status', 'escalated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(SupportConversation::getTableName());
    }
};
