<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Media\Models\Media;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Media::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->morphs('model'); // Links to products, users, etc.
            $table->string('url');
            $table->string('type')->nullable(); // e.g., 'image', 'logo'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Media::getTableName());
    }
};
