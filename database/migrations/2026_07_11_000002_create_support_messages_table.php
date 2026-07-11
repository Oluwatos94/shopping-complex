<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\Enums\SupportMessageRoleEnum;
use ModulesShoppingComplex\Models\SupportConversation;
use ModulesShoppingComplex\Models\SupportMessage;
use ModulesShoppingComplex\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(SupportMessage::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_conversation_id')->constrained(SupportConversation::getTableName())->onDelete('cascade');
            $table->enum('role', SupportMessageRoleEnum::values());
            $table->foreignId('sender_id')->nullable()->constrained(User::getTableName())->onDelete('set null');
            $table->text('content');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['support_conversation_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(SupportMessage::getTableName());
    }
};
