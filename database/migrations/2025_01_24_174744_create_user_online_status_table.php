<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\UserOnlineStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(UserOnlineStatus::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->timestamp('last_seen_at')->useCurrent();
            $table->boolean('is_online')->default(false);
            $table->string('socket_id')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('is_online');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(UserOnlineStatus::getTableName());
    }
};
