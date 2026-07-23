<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Reviews\Models\Review;
use ModulesShoppingComplex\Reviews\Models\ReviewVote;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(ReviewVote::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained(Review::getTableName())->onDelete('cascade');
            $table->foreignId('user_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->boolean('is_helpful');
            $table->timestamps();

            $table->unique(['review_id', 'user_id'], 'unique_review_user_vote');

            $table->index(['review_id', 'is_helpful']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ReviewVote::getTableName());
    }
};
