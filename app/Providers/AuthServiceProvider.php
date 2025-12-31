<?php

declare(strict_types=1);

namespace App\Providers;

use App\Policies\ProductPolicy;
use Illuminate\Support\ServiceProvider;
use ModulesShoppingComplex\Models\Product;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
