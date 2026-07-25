<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * After the domain restructure, the `media` relationships resolve model_type
     * through the morphMap (the old alias), but the upload path saved new rows with
     * the moved class FQCN — so images uploaded after the restructure stopped
     * resolving. Rewrite those stray FQCNs back to the alias the app queries for.
     *
     * Only rows with the exact wrong class name are touched; already-correct rows
     * (and every file stored in R2) are left untouched.
     */
    private const REMAP = [
        'ModulesShoppingComplex\Catalog\Models\Product' => 'ModulesShoppingComplex\Models\Product',
        'ModulesShoppingComplex\Identity\Models\User' => 'ModulesShoppingComplex\Models\User',
    ];

    public function up(): void
    {
        foreach (self::REMAP as $fqcn => $alias) {
            DB::table('media')
                ->where('model_type', $fqcn)
                ->update(['model_type' => $alias]);
        }
    }

    /**
     * Intentionally irreversible: reverting would relabel the rows back to the
     * FQCN the app can no longer find, re-breaking every image this corrects.
     */
    public function down(): void
    {
        // No-op — one-way data correction.
    }
};
