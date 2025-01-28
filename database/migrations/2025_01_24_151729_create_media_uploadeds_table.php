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
        Schema::create('media_uploadeds', function (Blueprint $table) {
            $table->id();
            $table->string('media_name');
            $table->string('media_path');
            $table->string('media_size');
            $table->string('media_type_id'); // e.g., 'image', 'video'
            $table->unsignedBigInteger('uploaded_by'); // User who uploaded the media
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('media_type')->references('id')->on('media_types')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_uploadeds');
    }
};
