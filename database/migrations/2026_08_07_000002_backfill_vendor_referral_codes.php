<?php

use Illuminate\Database\Migrations\Migration;
use ModulesShoppingComplex\Identity\Services\ReferralService;

return new class extends Migration
{
    /**
     * Split from the schema migration because MySQL commits DDL implicitly: if a
     * backfill failure rolled back into the same migration, the columns would
     * survive while the migration went unrecorded, and the retry would then die
     * on a duplicate column. Alone here, it retries cleanly — it only ever mints
     * for vendors still holding a null code.
     */
    public function up(): void
    {
        app(ReferralService::class)->backfillVendorCodes();
    }

    public function down(): void {}
};
