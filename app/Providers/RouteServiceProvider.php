<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Global rate limiter - 60 requests per minute
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Guest users (unauthenticated) - stricter limits
        RateLimiter::for('guest', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->ip())
                ->response(function (array $headers) {
                    return response()->json([
                        'message' => 'Too many requests. Please slow down.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // Authenticated users - more generous limits
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(120)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (array $headers) {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // Product browsing (public) - moderate limits
        RateLimiter::for('products', function (Request $request) {
            return [
                // Burst limit: 10 requests per second
                Limit::perSecond(10)->by($request->ip()),
                // Sustained limit: 100 requests per minute
                Limit::perMinute(100)->by($request->ip()),
            ];
        });

        // Search operations - prevent abuse
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (array $headers) {
                    return response()->json([
                        'message' => 'Too many search requests. Please wait before searching again.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // Write operations (create, update, delete) - stricter limits
        RateLimiter::for('writes', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (array $headers) {
                    return response()->json([
                        'message' => 'Too many write operations. Please slow down.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // Login attempts - prevent brute force
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (array $headers) {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again later.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // API rate limiter (if you add API routes later)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (array $headers) {
                    return response()->json([
                        'message' => 'API rate limit exceeded.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // Notification actions - prevent abuse of mark-all-read and bulk operations
        RateLimiter::for('notifications', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (array $headers) {
                    return response()->json([
                        'message' => 'Too many notification actions. Please slow down.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // Chat actions - rate limit for messaging
        RateLimiter::for('chat', function (Request $request) {
            return [
                // Burst limit: 5 messages per second
                Limit::perSecond(5)->by($request->user()?->id ?: $request->ip()),
                // Sustained limit: 60 messages per minute
                Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Typing indicator - more lenient rate limit
        RateLimiter::for('typing', function (Request $request) {
            return Limit::perSecond(2)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Support bot interactions - limit to prevent spam
        RateLimiter::for('support', function (Request $request) {
            return [
                Limit::perSecond(1)->by($request->user()?->id ?: $request->ip()),
                Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()),
            ];
        });
    }
}
