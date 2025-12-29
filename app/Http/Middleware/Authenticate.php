<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Redirect to ecommerce login if accessing ecommerce routes
        if ($request->is('ecommerce/*') || $request->is('ecommerce')) {
            return route('ecommerce.login');
        }

        // Default to admin login for admin panel routes
        return route('admin.login');
    }
}
