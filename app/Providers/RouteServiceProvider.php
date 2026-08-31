<?php

declare(strict_types=1);

namespace App\Providers;

use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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

        // Guest page loads - raised to tolerate shared carrier-NAT IPs
        RateLimiter::for('guest', function (Request $request) {
            return Limit::perMinute(120)
                ->by($request->ip())
                ->response($this->throttledResponse('Too many requests. Please slow down.'));
        });

        RateLimiter::for('register', function (Request $request) {
            $response = $this->throttledResponse(
                'Too many registration attempts. Please wait a moment and try again.'
            );

            return [
                Limit::perMinute(5)->by('register-email:'.$this->emailKey($request))->response($response),
                Limit::perMinute(60)->by('register-ip:'.$request->ip())->response($response),
            ];
        });

        RateLimiter::for('login-attempt', function (Request $request) {
            $response = $this->throttledResponse(
                'Too many login attempts. Please wait a moment and try again.'
            );

            return [
                Limit::perMinute(6)->by('login-email:'.$this->emailKey($request))->response($response),
                Limit::perMinute(60)->by('login-ip:'.$request->ip())->response($response),
            ];
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

        RateLimiter::for('support-start', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Support bot interactions - limit to prevent spam
        RateLimiter::for('support', function (Request $request) {
            return [
                Limit::perSecond(1)->by($request->user()?->id ?: $request->ip()),
                Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        RateLimiter::for('geo', function (Request $request) {
            $user = $request->user();

            return $user
                ? Limit::perMinute(30)->by($user->id)
                : Limit::perMinute(12)->by($request->ip());
        });
    }

    protected function throttledResponse(string $message): Closure
    {
        return function (Request $request, array $headers) use ($message) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], 429, $headers);
            }

            if ($request->isMethodSafe()) {
                return response()->view('errors.429', ['message' => $message], 429, $headers);
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => $message]);
        };
    }

    /**
     * Normalised so casing and whitespace cannot mint fresh buckets.
     */
    protected function emailKey(Request $request): string
    {
        $email = $request->input('email');

        if (! is_string($email) || trim($email) === '') {
            return 'anonymous:'.$request->ip();
        }

        return Str::lower(trim($email));
    }
}
