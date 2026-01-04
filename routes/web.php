<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use ModulesShoppingComplex\Http\Controllers\AuthController;
use ModulesShoppingComplex\Http\Controllers\ProductController;
use ModulesShoppingComplex\Http\Controllers\SocialAuthController;

// Authentication Routes (guest only with rate limiting)
Route::middleware(['guest', 'throttle:guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:writes');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:writes');

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
