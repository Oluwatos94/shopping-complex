<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Notifications\Models\Notification;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Notification::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->string('type'); // e.g., 'message_received', 'vendor_contact_request'
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->json('data')->nullable();
            $table->string('group_key')->nullable(); // For grouping similar notifications
            $table->boolean('is_grouped')->default(false);
            $table->unsignedInteger('group_count')->default(1);
            $table->timestamps();

            // Composite indexes for performance optimization
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'group_key', 'read_at', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Notification::getTableName());
    }
};
