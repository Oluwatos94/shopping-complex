<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\WhatsAppSession;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE '.WhatsAppSession::getTableName()." MODIFY state ENUM('idle','awaiting_location','awaiting_expand_choice','showing_vendors','showing_products') NOT NULL DEFAULT 'idle'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE '.WhatsAppSession::getTableName()." MODIFY state ENUM('idle','awaiting_location','showing_vendors','showing_products') NOT NULL DEFAULT 'idle'");
    }
};
