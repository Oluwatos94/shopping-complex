<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Reviews\Enums\ReviewStatusEnum;
use ModulesShoppingComplex\Reviews\Models\Review;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Review::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->foreignId('conversation_id')->nullable()->constrained(Conversation::getTableName())->onDelete('set null');
            $table->unsignedTinyInteger('rating'); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('comment')->nullable();

            $table->enum('status', ReviewStatusEnum::values())->default(ReviewStatusEnum::PENDING->value);
            $table->foreignId('moderated_by')->nullable()->constrained(User::getTableName())->onDelete('set null');
            $table->timestamp('moderated_at')->nullable();
            $table->text('moderation_notes')->nullable();

            // Voting counts (denormalized for performance)
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);

            $table->text('vendor_response')->nullable();
            $table->timestamp('vendor_responded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['customer_id', 'vendor_id'], 'unique_customer_vendor_review');

            $table->index('customer_id');
            $table->index(['vendor_id', 'status', 'created_at']);
            $table->index(['vendor_id', 'rating']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Review::getTableName());
    }
};
