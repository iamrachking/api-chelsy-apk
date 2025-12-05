<?php

use App\Http\Controllers\Admin\Web\AdminDashboardController;
use App\Http\Controllers\Admin\Web\AdminAuthController;
use App\Http\Controllers\Admin\Web\AdminProfileController;
use Illuminate\Support\Facades\Route;

// Route::get('/test-firebase-file', function() {
//     return file_exists(storage_path('app/firebase-credentials.json')) ? 'Fichier OK' : 'Fichier manquant';
// });

// Route de base
Route::get('/', function () {
    return redirect('/admin/login');
});

// Route par défaut pour la redirection d'authentification de Laravel
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Routes Admin Web (Dashboard avec vues)
Route::prefix('admin')->name('admin.')->group(function () {
    // Authentification Admin
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    
    // Réinitialisation de mot de passe
    Route::get('/password/reset', [AdminAuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/password/email', [AdminAuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [AdminAuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [AdminAuthController::class, 'reset'])->name('password.update');
    
    // Routes protégées (nécessitent authentification admin)
    Route::middleware(['auth', \App\Http\Middleware\EnsureUserIsAdmin::class])->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/export-stats', [AdminDashboardController::class, 'exportStats'])->name('dashboard.export-stats');
        
            // Restaurant
            Route::get('/restaurant', [AdminDashboardController::class, 'restaurant'])->name('restaurant');
            Route::get('/restaurant/show', [AdminDashboardController::class, 'showRestaurant'])->name('restaurant.show');
            Route::put('/restaurant', [AdminDashboardController::class, 'updateRestaurant'])->name('restaurant.update');
            Route::delete('/restaurant/images/{index}', [AdminDashboardController::class, 'deleteRestaurantImage'])->name('restaurant.delete-image');
        
        // Catégories
        Route::get('/categories', [AdminDashboardController::class, 'categories'])->name('categories');
        Route::get('/categories/create', [AdminDashboardController::class, 'createCategory'])->name('categories.create');
        Route::post('/categories', [AdminDashboardController::class, 'storeCategory'])->name('categories.store');
        Route::get('/categories/import', [AdminDashboardController::class, 'showImportCategories'])->name('categories.import');
        Route::post('/categories/import', [AdminDashboardController::class, 'importCategories'])->name('categories.import.store');
        Route::get('/categories/{id}/edit', [AdminDashboardController::class, 'editCategory'])->name('categories.edit');
        Route::put('/categories/{id}', [AdminDashboardController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{id}', [AdminDashboardController::class, 'deleteCategory'])->name('categories.delete');
        
        // Plats
        Route::get('/dishes', [AdminDashboardController::class, 'dishes'])->name('dishes');
        Route::get('/dishes/create', [AdminDashboardController::class, 'createDish'])->name('dishes.create');
        Route::post('/dishes', [AdminDashboardController::class, 'storeDish'])->name('dishes.store');
        Route::get('/dishes/import', [AdminDashboardController::class, 'showImportDishes'])->name('dishes.import');
        Route::post('/dishes/import', [AdminDashboardController::class, 'importDishes'])->name('dishes.import.store');
        Route::get('/dishes/{id}', [AdminDashboardController::class, 'showDish'])->name('dishes.show');
        Route::get('/dishes/{id}/edit', [AdminDashboardController::class, 'editDish'])->name('dishes.edit');
        Route::put('/dishes/{id}', [AdminDashboardController::class, 'updateDish'])->name('dishes.update');
        Route::delete('/dishes/{id}', [AdminDashboardController::class, 'deleteDish'])->name('dishes.delete');
        
        // Commandes
        Route::get('/orders', [AdminDashboardController::class, 'orders'])->name('orders');
        Route::get('/orders/{id}', [AdminDashboardController::class, 'showOrder'])->name('orders.show');
        Route::put('/orders/{id}/status', [AdminDashboardController::class, 'updateOrderStatus'])->name('orders.status');
        Route::get('/orders/export', [AdminDashboardController::class, 'exportOrders'])->name('orders.export');
        
        // Avis
        Route::get('/reviews', [AdminDashboardController::class, 'reviews'])->name('reviews');
        Route::post('/reviews/{id}/approve', [AdminDashboardController::class, 'approveReview'])->name('reviews.approve');
        Route::post('/reviews/{id}/reject', [AdminDashboardController::class, 'rejectReview'])->name('reviews.reject');
        
        // Réclamations
        Route::get('/complaints', [AdminDashboardController::class, 'complaints'])->name('complaints');
        Route::get('/complaints/{id}', [AdminDashboardController::class, 'showComplaint'])->name('complaints.show');
        Route::put('/complaints/{id}/status', [AdminDashboardController::class, 'updateComplaintStatus'])->name('complaints.update-status');
        
        // Codes Promo
        Route::get('/promo-codes', [AdminDashboardController::class, 'promoCodes'])->name('promo-codes');
        Route::get('/promo-codes/create', [AdminDashboardController::class, 'createPromoCode'])->name('promo-codes.create');
        Route::post('/promo-codes', [AdminDashboardController::class, 'storePromoCode'])->name('promo-codes.store');
        Route::get('/promo-codes/{id}', [AdminDashboardController::class, 'showPromoCode'])->name('promo-codes.show');
        Route::get('/promo-codes/{id}/edit', [AdminDashboardController::class, 'editPromoCode'])->name('promo-codes.edit');
        Route::put('/promo-codes/{id}', [AdminDashboardController::class, 'updatePromoCode'])->name('promo-codes.update');
        Route::delete('/promo-codes/{id}', [AdminDashboardController::class, 'deletePromoCode'])->name('promo-codes.delete');
        
        // FAQ
        Route::get('/faqs', [AdminDashboardController::class, 'faqs'])->name('faqs');
        Route::get('/faqs/create', [AdminDashboardController::class, 'createFAQ'])->name('faqs.create');
        Route::post('/faqs', [AdminDashboardController::class, 'storeFAQ'])->name('faqs.store');
        Route::get('/faqs/{id}/edit', [AdminDashboardController::class, 'editFAQ'])->name('faqs.edit');
        Route::put('/faqs/{id}', [AdminDashboardController::class, 'updateFAQ'])->name('faqs.update');
        Route::delete('/faqs/{id}', [AdminDashboardController::class, 'deleteFAQ'])->name('faqs.delete');
        
        // Bannières
        Route::get('/banners', [AdminDashboardController::class, 'banners'])->name('banners');
        Route::get('/banners/create', [AdminDashboardController::class, 'createBanner'])->name('banners.create');
        Route::post('/banners', [AdminDashboardController::class, 'storeBanner'])->name('banners.store');
        Route::get('/banners/{id}/edit', [AdminDashboardController::class, 'editBanner'])->name('banners.edit');
        Route::put('/banners/{id}', [AdminDashboardController::class, 'updateBanner'])->name('banners.update');
        Route::delete('/banners/{id}', [AdminDashboardController::class, 'deleteBanner'])->name('banners.delete');
        
        // Utilisateurs
        Route::get('/users', [AdminDashboardController::class, 'users'])->name('users');
        Route::get('/users/create-admin', [AdminDashboardController::class, 'createAdmin'])->name('users.create-admin');
        Route::post('/users/create-admin', [AdminDashboardController::class, 'storeAdmin'])->name('users.store-admin');
        Route::get('/users/{id}', [AdminDashboardController::class, 'showUser'])->name('users.show');
        Route::post('/users/{id}/toggle-block', [AdminDashboardController::class, 'toggleBlockUser'])->name('users.toggle-block');
        Route::get('/users/export', [AdminDashboardController::class, 'exportUsers'])->name('users.export');
        Route::delete('/users/{id}', [AdminDashboardController::class, 'deleteUser'])->name('users.delete');
        
        // Profil
        Route::get('/profile', [AdminProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');
    });
});
