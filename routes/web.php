<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use ModulesShoppingComplex\Http\Controllers\Admin\AdminAuthController;
use ModulesShoppingComplex\Http\Controllers\Admin\AdminController;
use ModulesShoppingComplex\Http\Controllers\AnalyticsController;
use ModulesShoppingComplex\Http\Controllers\Auth\AuthController;
use ModulesShoppingComplex\Http\Controllers\Auth\ForgotPasswordController;
use ModulesShoppingComplex\Http\Controllers\Auth\ResetPasswordController;
use ModulesShoppingComplex\Http\Controllers\Auth\SocialAuthController;
use ModulesShoppingComplex\Http\Controllers\Auth\VerifyEmailController;
use ModulesShoppingComplex\Http\Controllers\CategoryController;
use ModulesShoppingComplex\Http\Controllers\ChatController;
use ModulesShoppingComplex\Http\Controllers\NotificationController;
use ModulesShoppingComplex\Http\Controllers\ProductController;
use ModulesShoppingComplex\Http\Controllers\ProfileController;
use ModulesShoppingComplex\Http\Controllers\ReviewController;
use ModulesShoppingComplex\Http\Controllers\SubscriptionController;
use ModulesShoppingComplex\Http\Controllers\VendorController;
use ModulesShoppingComplex\Http\Controllers\WhatsAppController;

// WhatsApp Webhook Routes (public — Meta servers cannot authenticate)
Route::get('/webhook/whatsapp', [WhatsAppController::class, 'verify'])->name('whatsapp.webhook.verify');
Route::post('/webhook/whatsapp', [WhatsAppController::class, 'receive'])->name('whatsapp.webhook.receive');

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
        return Inertia::render('index', [
            'platformWhatsApp' => config('services.whatsapp.platform_number', ''),
        ]);
    });
});

// Public Product Routes (accessible to everyone with product-specific rate limiting)
Route::middleware(['throttle:products'])->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
});

// Public Category Routes
Route::middleware(['throttle:products'])->group(function () {
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{id}/vendors', [CategoryController::class, 'vendors'])->name('categories.vendors');
});

// Public Vendor Routes (accessible to everyone with product-specific rate limiting)
Route::middleware(['throttle:products'])->group(function () {
    Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
    Route::get('/vendors/{vendorSlug}', [VendorController::class, 'show'])->name('vendor.show');
});

// Protected Product Routes (Vendor/Admin only with auth rate limiting + write limits)
Route::middleware(['auth', 'throttle:auth'])->group(function () {
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::get('/products/{product}/images', [ProductController::class, 'getImages'])->name('products.images.index');
});

// Write operations with stricter rate limiting
Route::middleware(['auth', 'throttle:writes'])->group(function () {
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::patch('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Product Image Management
    Route::post('/products/{product}/images', [ProductController::class, 'uploadImages'])->name('products.images.upload');
    Route::delete('/products/{product}/images/{mediaId}', [ProductController::class, 'deleteImage'])->name('products.images.delete');
});

Route::middleware(['auth', 'throttle:notifications'])->prefix('api/notifications')->group(function () {
    Route::patch('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});

// Notification Inertia Pages
Route::middleware(['auth', 'throttle:auth'])->group(function () {
    Route::get('/notifications/preferences', [NotificationController::class, 'preferencesPage'])->name('notifications.preferences');
    Route::post('/notifications/preferences/{type}', [NotificationController::class, 'updatePreference'])
        ->where('type', 'message_received|vendor_contact_request|product_updated|system_alert')
        ->name('notifications.preferences.update');
});

// Chat API Routes
Route::middleware(['auth', 'throttle:chat'])->prefix('api/chat')->group(function () {
    Route::get('/conversations', [ChatController::class, 'index'])->name('chat.conversations');
    Route::post('/conversations', [ChatController::class, 'store'])->name('chat.conversations.store');
    Route::get('/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.conversations.show');
    Route::get('/conversations/{conversation}/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('chat.messages.send');
    Route::patch('/conversations/{conversation}/messages/read', [ChatController::class, 'markAsRead'])->name('chat.messages.read');
    Route::get('/conversations/{conversation}/messages/poll', [ChatController::class, 'pollMessages'])->name('chat.messages.poll');
    Route::get('/unread-count', [ChatController::class, 'unreadCount'])->name('chat.unread');
});

// Typing indicator with separate rate limit
Route::middleware(['auth', 'throttle:typing'])->prefix('api/chat')->group(function () {
    Route::post('/conversations/{conversation}/typing', [ChatController::class, 'typing'])->name('chat.typing');
});

// Chat Inertia Pages
Route::middleware(['auth', 'throttle:auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'chatPage'])->name('chat.index');
    Route::get('/chat/{conversation}', [ChatController::class, 'conversationPage'])->name('chat.conversation');
});

// Review Routes - Public (view vendor reviews)
Route::middleware(['throttle:products'])->group(function () {
    Route::get('/vendors/{vendorSlug}/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/vendors/{vendorSlug}/reviews/stats', [ReviewController::class, 'stats'])->name('reviews.stats');
});

// Review Routes - Authenticated
Route::middleware(['auth', 'throttle:auth'])->group(function () {
    Route::get('/vendors/{vendorSlug}/reviews/can-review', [ReviewController::class, 'canReview'])->name('reviews.can-review');
    Route::get('/my-reviews', [ReviewController::class, 'myReviews'])->name('reviews.my');
    Route::get('/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::get('/vendor/reviews', [ReviewController::class, 'vendorReviews'])->name('vendor.reviews');
});

// Review Write Operations
Route::middleware(['auth', 'throttle:writes'])->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/{review}/vote', [ReviewController::class, 'vote'])->name('reviews.vote');
    Route::delete('/reviews/{review}/vote', [ReviewController::class, 'removeVote'])->name('reviews.vote.remove');
    Route::post('/reviews/{review}/respond', [ReviewController::class, 'respond'])->name('reviews.respond');
});

// Admin Auth Routes (guest only)
Route::middleware(['guest', 'throttle:guest'])->prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:writes')->name('admin.login.post');
});

// Admin Dashboard Routes
Route::middleware(['auth', 'admin', 'throttle:auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'stats'])->name('admin.dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/vendors/pending', [AdminController::class, 'pendingVendors'])->name('admin.vendors.pending');
    Route::get('/bot-monitor', [AdminController::class, 'botMonitor'])->name('admin.bot.monitor');
});

Route::middleware(['auth', 'admin', 'throttle:writes'])->prefix('admin')->group(function () {
    Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::post('/vendors/{user}/approve', [AdminController::class, 'approveVendor'])->name('admin.vendors.approve');
    Route::post('/vendors/{user}/reject', [AdminController::class, 'rejectVendor'])->name('admin.vendors.reject');
});

// Review Moderation Routes - Admin only
Route::middleware(['auth', 'throttle:auth'])->prefix('admin')->group(function () {
    Route::get('/reviews/pending', [ReviewController::class, 'pending'])->name('admin.reviews.pending');
    Route::post('/reviews/{review}/moderate', [ReviewController::class, 'moderate'])->name('admin.reviews.moderate');
});

// Vendor Registration & Onboarding Routes
Route::middleware(['auth', 'throttle:auth'])->prefix('vendor')->group(function () {
    Route::get('/register', [VendorController::class, 'register'])->name('vendor.register');
    Route::get('/onboarding', [VendorController::class, 'onboarding'])->name('vendor.onboarding');
    Route::get('/onboarding/success', [VendorController::class, 'onboardingSuccess'])->name('vendor.onboarding.success');
});

Route::middleware(['auth', 'throttle:writes'])->prefix('vendor')->group(function () {
    Route::post('/register', [VendorController::class, 'storeRegistration'])->name('vendor.register.store');
    Route::post('/products/upload', [VendorController::class, 'uploadProduct'])->name('vendor.products.upload');
    Route::post('/onboarding/save', [VendorController::class, 'saveOnboarding'])->name('vendor.onboarding.save');
    Route::post('/onboarding/submit', [VendorController::class, 'submitOnboarding'])->name('vendor.onboarding.submit');
});

// User Profile Routes
Route::middleware(['auth', 'throttle:auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
});

Route::middleware(['auth', 'throttle:writes'])->group(function () {
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
});

// Vendor Analytics
Route::middleware(['auth', 'throttle:auth'])->group(function () {
    Route::get('/vendor/analytics', [AnalyticsController::class, 'index'])->name('vendor.analytics');
});

// Vendor Subscription Routes
Route::middleware(['auth', 'throttle:auth'])->prefix('vendor')->group(function () {
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('vendor.subscription.index');
    Route::get('/subscription/callback', [SubscriptionController::class, 'callback'])->name('vendor.subscription.callback');
});

Route::middleware(['auth', 'throttle:writes'])->prefix('vendor')->group(function () {
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('vendor.subscription.cancel');
    Route::post('/subscription/{plan}', [SubscriptionController::class, 'checkout'])->name('vendor.subscription.checkout');
});

// Vendor follow toggle
Route::middleware(['auth', 'throttle:writes'])->group(function () {
    Route::post('/vendors/{vendorSlug}/follow', [VendorController::class, 'toggleFollow'])->name('vendor.follow.toggle');
});
