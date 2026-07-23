<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Notifications\Models\NotificationPreference;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(NotificationPreference::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->string('notification_type'); // e.g., 'message_received', 'vendor_contact_request'
            $table->boolean('email_enabled')->default(true);
            $table->boolean('push_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'notification_type']);
            $table->index('notification_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(NotificationPreference::getTableName());
    }
};
