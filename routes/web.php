<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProfileController,
    UserController,
    ProductController,
    DashboardController,
    LandingController,
    OrderController,
    PaymentInstructionController,
    TransactionVerificationController
};

/*
|--------------------------------------------------------------------------
| LANDING PAGE
|--------------------------------------------------------------------------
*/

Route::get('/', LandingController::class)->name('landing');
Route::get('/verify-transaction/{token}', TransactionVerificationController::class)
    ->name('transactions.verify');

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::controller(ProfileController::class)->name('profile.')->group(function () {
        Route::get('/profile', 'edit')->name('edit');
        Route::patch('/profile', 'update')->name('update');
        Route::delete('/profile', 'destroy')->name('destroy');
    });

    /* --- Owner Area --- */
    Route::middleware('role:pemilik')
        ->prefix('pemilik')
        ->name('pemilik.')
        ->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
            Route::resource('products', ProductController::class)->only(['index', 'show']);
            Route::get('/orders', [OrderController::class, 'staffIndex'])->name('orders.index');
            Route::get('/laporan', [OrderController::class, 'report'])->name('reports.index');
        });

    /* --- Staff Area (also accessible by owners) --- */
    Route::middleware('role:pegawai|pemilik')
        ->prefix('pegawai')
        ->name('pegawai.')
        ->group(function () {

            // Products
            Route::controller(ProductController::class)->prefix('products')->name('products.')->group(function () {
                Route::get('/', 'index')->middleware('permission:products.read')->name('index');
                Route::get('/create', 'create')->middleware('permission:products.create')->name('create');
                Route::post('/', 'store')->middleware('permission:products.create')->name('store');
                Route::get('/{product}/edit', 'edit')->middleware('permission:products.update')->name('edit');
                Route::put('/{product}', 'update')->middleware('permission:products.update')->name('update');
                Route::delete('/{product}', 'destroy')->middleware('permission:products.delete')->name('destroy');
            });

            // Orders
            Route::controller(OrderController::class)->prefix('orders')->name('orders.')->group(function () {
                Route::get('/', 'staffIndex')->middleware('permission:orders.read')->name('index');
                Route::get('/allorders', 'aOrders')->middleware('permission:orders.read')->name('all');
                Route::get('/offline/create', 'createOffline')->middleware('permission:orders.manage')->name('offline.create');
                Route::post('/offline/store', 'storeOffline')->middleware('permission:orders.manage')->name('offline.store');

                // Routes with IDs
                Route::middleware('permission:orders.manage')->group(function () {
                    Route::patch('/{id}/handover', 'handover')->name('handover');
                    Route::patch('/{id}/returned', 'returned')->name('returned');
                    Route::patch('/{id}/approve', 'approve')->name('approve');
                    Route::patch('/{id}/reject', 'reject')->name('reject');
                    Route::patch('/{id}/payment', 'updatePaymentStatus')->name('payment');
                });

                Route::get('/{id}', 'show')->middleware('permission:orders.read')->name('show');
            });
        });

    /* --- Renter Area --- */
    Route::middleware('role:penyewa')
        ->prefix('penyewa')
        ->name('penyewa.')
        ->group(function () {
            Route::get('/products', [ProductController::class, 'listForCustomer'])->name('products.index');

            Route::controller(OrderController::class)->prefix('orders')->name('orders.')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/store', 'store')->name('store');
                Route::get('/{id}/payment/instructions', [PaymentInstructionController::class, 'show'])
                    ->name('payment.instructions');
                if (app()->environment(['local', 'development', 'testing'])) {
                    Route::post('/{id}/payment/simulate-success', [PaymentInstructionController::class, 'simulateSuccess'])
                        ->name('payment.simulate-success');
                }
                Route::get('/{id}', 'show')->name('show');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });
        });
});

require __DIR__ . '/auth.php';
