<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorOnboarding;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(VendorOnboarding::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(User::getTableName())->onDelete('cascade');

            // Business Information (nullable for drafts, validated on submission)
            $table->string('legal_entity_name')->nullable();
            $table->string('business_category')->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->text('physical_address')->nullable();

            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('account_number')->nullable();
            $table->string('swift_bic_code')->nullable();

            // Verification Documents (nullable for drafts, validated on submission)
            $table->string('certificate_of_incorporation')->nullable();
            $table->string('government_issued_id')->nullable();
            $table->string('proof_of_address')->nullable();

            // Status and Progress
            $table->enum('status', VendorOnboardingStatusEnum::values())->default('draft');
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->boolean('agreed_to_terms')->default(false);

            // Admin review
            $table->foreignId('reviewed_by')->nullable()->constrained(User::getTableName())->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->unique('user_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(VendorOnboarding::getTableName());
    }
};
