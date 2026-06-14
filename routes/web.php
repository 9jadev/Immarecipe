<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SpinController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Storefront Routes (Customer-facing)
Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/category/{category:slug}', [StorefrontController::class, 'category'])->name('category');
Route::get('/product/{product:slug}', [StorefrontController::class, 'product'])->name('product');

// Cart Routes
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::get('/cart/count', [CartController::class, 'count'])->name('cart.count');

// Order Routes
Route::post('/order', [OrderController::class, 'store'])->name('order.store');
Route::get('/order/success', [OrderController::class, 'success'])->name('order.success');
Route::get('/order/{order}', [OrderController::class, 'show'])->name('order.show');

// Payment Routes
Route::post('/payment/initialize', [PaymentController::class, 'initialize'])->name('payment.initialize');
Route::get('/payment/callback/{gateway}', [PaymentController::class, 'callback'])->name('payment.callback');

// Payment Webhooks (no CSRF protection needed)
Route::post('/webhook/flutterwave', [PaymentController::class, 'flutterwaveWebhook'])->name('webhook.flutterwave');
Route::post('/webhook/safe-haven', [PaymentController::class, 'safeHavenWebhook'])->name('webhook.safe_haven');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

// Customer Spin Routes
Route::get('/spin', [SpinController::class, 'index'])->name('spin');
Route::post('/spin', [SpinController::class, 'spin']);
Route::get('/spin/verify/{code}', [SpinController::class, 'verifyCode']);

require __DIR__ . '/settings.php';
require __DIR__ . '/admin.php';
