<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use ModulesShoppingComplex\Http\Controllers\Auth\AuthController;
use ModulesShoppingComplex\Http\Controllers\Auth\ForgotPasswordController;
use ModulesShoppingComplex\Http\Controllers\Auth\ResetPasswordController;
use ModulesShoppingComplex\Http\Controllers\Auth\SocialAuthController;
use ModulesShoppingComplex\Http\Controllers\Auth\VerifyEmailController;
use ModulesShoppingComplex\Http\Controllers\ProductController;

// Authentication Routes (guest only with rate limiting)
Route::middleware(['guest', 'throttle:guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:writes');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:writes');

    // Password Reset Routes
    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

    // Social Authentication - Redirect
    Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
});

// Social Authentication - Callback (no guest middleware to allow login)
Route::middleware(['throttle:guest'])->group(function () {
    Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// Authenticated User Routes
Route::middleware(['auth', 'throttle:auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [AuthController::class, 'user'])->name('user');

    // Email Verification Routes
    Route::get('/email/verify', [VerifyEmailController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [VerifyEmailController::class, 'resend'])->name('verification.send');
});

// Email Verification (signed URL)
Route::middleware(['auth', 'signed'])->group(function () {
    Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])->name('verification.verify');
});

// Landing page - moderate rate limiting
Route::middleware(['throttle:guest'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('index', []);
    });
});

// Public Product Routes (accessible to everyone with product-specific rate limiting)
Route::middleware(['throttle:products'])->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
});

// Protected Product Routes (Vendor/Admin only with auth rate limiting + write limits)
Route::middleware(['auth', 'throttle:auth'])->group(function () {
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
});

// Write operations with stricter rate limiting
Route::middleware(['auth', 'throttle:writes'])->group(function () {
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::patch('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});
