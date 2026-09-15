<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Identity\Models\User;

return new class extends Migration
{
    /**
     * Non-default lead tiers by category slug. Every other category keeps the
     * column default of 5. Kept in sync with CategorySeeder.
     */
    private const TIERS = [
        'services' => 20,
        'automotive-tools' => 20,
        'electronics-repairs' => 10,
        'furniture-appliances' => 10,
        'outdoors-entertainment' => 10,
        'art-gallery' => 10,
    ];

    public function up(): void
    {
        Schema::table(Category::getTableName(), function (Blueprint $table) {
            $table->unsignedInteger('lead_coin_cost')->default(5)->after('slug');
        });

        Schema::table(User::getTableName(), function (Blueprint $table) {
            // A negotiated per-vendor rate; null means "use the category tier".
            $table->unsignedInteger('lead_coin_cost_override')->nullable()->after('category_id');
        });

        foreach (self::TIERS as $slug => $cost) {
            DB::table(Category::getTableName())->where('slug', $slug)->update(['lead_coin_cost' => $cost]);
        }

        DB::table(Category::getTableName())->updateOrInsert(
            ['slug' => 'real-estate-property'],
            [
                'name' => 'Real Estate & Property',
                'description' => 'Property sales, rentals, and real estate services',
                'lead_coin_cost' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table(Category::getTableName())->where('slug', 'real-estate-property')->delete();

        Schema::table(Category::getTableName(), function (Blueprint $table) {
            $table->dropColumn('lead_coin_cost');
        });

        Schema::table(User::getTableName(), function (Blueprint $table) {
            $table->dropColumn('lead_coin_cost_override');
        });
    }
};
