<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Discovery\Models\VendorContact;
use ModulesShoppingComplex\Identity\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(VendorContact::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained(User::getTableName())->onDelete('cascade');
            $table->string('channel', 30)->default('whatsapp');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['customer_id', 'vendor_id', 'channel']);
            $table->index('vendor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(VendorContact::getTableName());
    }
};
