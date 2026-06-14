<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DispatchLocationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Admin routes (guest only)
Route::middleware('admin.guest')->prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login']);
});

// Admin routes (authenticated only)
Route::middleware('admin.auth')->prefix('admin')->name('admin.')->group(function () {
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::get('dashboard', [AdminAuthController::class, 'dashboard'])->name('dashboard');
    Route::get('discount-spinner', [AdminAuthController::class, 'discountSpinner'])->name('discount-spinner');
    Route::put('discount-spinner/settings', [AdminAuthController::class, 'updateSpinSettings']);

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::get('orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::patch('orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->name('orders.update-payment-status');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/export', [PaymentController::class, 'export'])->name('payments.export');
    Route::get('payments/{order}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('payments/{order}/requery', [PaymentController::class, 'requery'])->name('payments.requery');

    // Category CRUD
    Route::resource('categories', CategoryController::class);
    Route::post('categories/{category}/toggle-active', [CategoryController::class, 'toggleActive'])->name('categories.toggle-active');

    // Product CRUD
    Route::resource('products', ProductController::class);
    Route::post('products/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('products.toggle-active');
    Route::post('products/{product}/toggle-featured', [ProductController::class, 'toggleFeatured'])->name('products.toggle-featured');

    // Dispatch Locations
    Route::resource('dispatch-locations', DispatchLocationController::class)->parameters([
        'dispatch-locations' => 'dispatchLocation',
    ]);
    Route::post('dispatch-locations/{dispatchLocation}/toggle-active', [DispatchLocationController::class, 'toggleActive'])->name('dispatch-locations.toggle-active');
});
