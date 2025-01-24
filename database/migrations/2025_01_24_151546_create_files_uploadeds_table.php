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
        Schema::create('files_uploadeds', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type'); // e.g., 'pdf', 'docx'
            $table->unsignedBigInteger('uploaded_by'); // User who uploaded the file
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files_uploadeds');
    }
};
