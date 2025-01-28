<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // e.g., 'Product', 'User'
            $table->unsignedBigInteger('entity_id'); // ID of the entity
            $table->string('event_type'); // e.g., 'view', 'purchase'
            $table->ipAddress('ip_address')->nullable(); // IP of the user
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Optional: linked user
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics');
    }
};
