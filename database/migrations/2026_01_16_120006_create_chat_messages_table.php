<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\ChatMessage;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(ChatMessage::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained(Conversation::getTableName())->onDelete('cascade');
            $table->foreignId('sender_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->text('content');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_type')->nullable(); // 'image', 'document', etc.
            $table->string('attachment_name')->nullable(); // Original filename
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['conversation_id', 'read_at']);
            $table->index(['conversation_id', 'id']); // Composite index for polling queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ChatMessage::getTableName());
    }
};
