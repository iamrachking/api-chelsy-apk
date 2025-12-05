<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\RestaurantController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DishController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\PromoCodeController;
use App\Http\Controllers\Api\V1\FAQController;
use App\Http\Controllers\Api\V1\ComplaintController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\FcmTokenController;
use App\Http\Controllers\Api\V1\DeliveryTrackingController;
use App\Http\Controllers\Api\V1\BannerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes publiques (sans authentification)
Route::prefix('v1')->group(function () {
    // Authentification
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Restaurant
    Route::get('/restaurant', [RestaurantController::class, 'show']);

    // Catégories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    // Plats
    Route::get('/dishes', [DishController::class, 'index']);
    Route::get('/dishes/featured', [DishController::class, 'featured']);
    Route::get('/dishes/popular', [DishController::class, 'popular']);
    Route::get('/dishes/{id}', [DishController::class, 'show']);

    // FAQ
    Route::get('/faqs', [FAQController::class, 'index']);

    // Bannières
    Route::get('/banners', [BannerController::class, 'index']);

    // Avis publics
    Route::get('/dishes/{dishId}/reviews', [ReviewController::class, 'dishReviews']);

    // Webhook Stripe (sans authentification)
    Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook']);
});

// Routes protégées (nécessitent une authentification)
Route::prefix('v1')->middleware(['auth:sanctum', \App\Http\Middleware\EnsureUserNotBlocked::class])->group(function () {
    // Authentification
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Utilisateur
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/change-password', [UserController::class, 'changePassword']);

    // Adresses
    Route::apiResource('addresses', AddressController::class);

    // Panier
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::put('/cart/items/{id}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // Commandes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::get('/orders/{id}/invoice', [OrderController::class, 'getInvoice']);
    Route::get('/orders/{id}/invoice/download', [OrderController::class, 'downloadInvoice']);
    Route::post('/orders/{id}/reorder', [OrderController::class, 'reorder']);

    // Avis
    Route::post('/reviews', [ReviewController::class, 'store']);

    // Favoris
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{id}', [FavoriteController::class, 'destroy']);

    // Codes promo
    Route::post('/promo-codes/validate', [PromoCodeController::class, 'validate']);

    // Paiements
    Route::post('/payments/confirm-stripe', [PaymentController::class, 'confirmStripePayment']);

    // Réclamations
    Route::get('/complaints', [ComplaintController::class, 'index']);
    Route::post('/complaints', [ComplaintController::class, 'store']);
    Route::get('/complaints/{id}', [ComplaintController::class, 'show']);

    // Notifications FCM
    Route::post('/fcm-token', [FcmTokenController::class, 'store']);
    Route::delete('/fcm-token', [FcmTokenController::class, 'destroy']);

    // Suivi GPS Livreur
    Route::post('/delivery/position', [DeliveryTrackingController::class, 'updatePosition']);
    Route::get('/delivery/position/current', [DeliveryTrackingController::class, 'getCurrentPosition']);
    Route::get('/delivery/position/history', [DeliveryTrackingController::class, 'getPositionHistory']);
    Route::get('/orders/{order_id}/tracking', [DeliveryTrackingController::class, 'getOrderTracking']);
    Route::get('/delivery/drivers/available', [DeliveryTrackingController::class, 'getAvailableDrivers']);
});
