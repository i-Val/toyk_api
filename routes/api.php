<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ReportedAdController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\HomeSettingController;
use App\Http\Controllers\AdminSlideController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\SplashMessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::get('/pages/{slug}', [PageController::class, 'show']);
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/plans', [MembershipPlanController::class, 'index']);
Route::get('/blog', [BlogPostController::class, 'index']);
Route::get('/blog/{slug}', [BlogPostController::class, 'show']);
Route::get('/slides', [AdminSlideController::class, 'active']);

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/stats', [AdminController::class, 'stats']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    Route::post('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
    Route::get('/products', [AdminController::class, 'products']);
    Route::delete('/products/{id}', [AdminController::class, 'deleteProduct']);
    Route::post('/products/{id}/toggle-status', [AdminController::class, 'toggleProductStatus']);
    Route::post('/products/{id}/toggle-featured', [AdminController::class, 'toggleProductFeatured']);
    
    // Plans Management
    Route::post('/plans', [MembershipPlanController::class, 'store']);
    Route::put('/plans/{id}', [MembershipPlanController::class, 'update']);
    Route::delete('/plans/{id}', [MembershipPlanController::class, 'destroy']);

    // Blog Management
    Route::post('/blog', [BlogPostController::class, 'store']);
    Route::put('/blog/{id}', [BlogPostController::class, 'update']);
    Route::delete('/blog/{id}', [BlogPostController::class, 'destroy']);

    // Categories Management
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Pages Management
    Route::get('/pages', [PageController::class, 'index']);
    Route::post('/pages', [PageController::class, 'store']);
    Route::put('/pages/{id}', [PageController::class, 'update']);
    Route::delete('/pages/{id}', [PageController::class, 'destroy']);

    // Contacts Management
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::get('/contacts/{id}', [ContactController::class, 'show']);
    Route::delete('/contacts/{id}', [ContactController::class, 'destroy']);

    // Reports Management
    Route::get('/reports', [ReportedAdController::class, 'index']);
    Route::delete('/reports/{id}', [ReportedAdController::class, 'destroy']);

    // Home settings
    Route::get('/home_settings', [HomeSettingController::class, 'show']);
    Route::post('/home_settings', [HomeSettingController::class, 'store']);

    // Splash messages
    Route::get('/splash_messages', [SplashMessageController::class, 'show']);
    Route::post('/splash_messages', [SplashMessageController::class, 'store']);

    // Categories helper for admin
    Route::get('/get_all_categories', [CategoryController::class, 'allForAdmin']);

    // Slides Management
    Route::get('/slides', [AdminSlideController::class, 'index']);
    Route::post('/slides', [AdminSlideController::class, 'store']);
    Route::post('/slides/{id}/toggle', [AdminSlideController::class, 'toggle']);
    Route::post('/slides/{id}/delete', [AdminSlideController::class, 'destroy']);

    // Transactions
    Route::get('/transactions', [AdminController::class, 'transactions']);

    // Reviews
    Route::get('/reviews', [ReviewController::class, 'adminIndex']);
    Route::get('/reviews/{id}', [ReviewController::class, 'adminShow']);
    Route::post('/reviews/{id}/toggle-status', [ReviewController::class, 'toggleStatus']);

    // Subscribers
    Route::get('/subscribers', [NewsletterController::class, 'adminIndex']);
    Route::post('/subscribers/{id}/toggle-status', [NewsletterController::class, 'toggleStatus']);
    Route::delete('/subscribers/{id}', [NewsletterController::class, 'destroy']);

    // Notifications
    Route::post('/notify', [AdminController::class, 'notify']);

    // Currency Management
    Route::get('/currencies', [CurrencyController::class, 'index']);
    Route::post('/currencies', [CurrencyController::class, 'store']);
    Route::put('/currencies/{id}', [CurrencyController::class, 'update']);
    Route::delete('/currencies/{id}', [CurrencyController::class, 'destroy']);

    // Location Management
    Route::get('/countries', [CountryController::class, 'index']);
    Route::post('/countries', [CountryController::class, 'store']);
    Route::put('/countries/{id}', [CountryController::class, 'update']);
    Route::delete('/countries/{id}', [CountryController::class, 'destroy']);

    Route::get('/states', [StateController::class, 'index']);
    Route::post('/states', [StateController::class, 'store']);
    Route::put('/states/{id}', [StateController::class, 'update']);
    Route::delete('/states/{id}', [StateController::class, 'destroy']);

    Route::get('/cities', [CityController::class, 'index']);
    Route::post('/cities', [CityController::class, 'store']);
    Route::put('/cities/{id}', [CityController::class, 'update']);
    Route::delete('/cities/{id}', [CityController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/my-products', [ProductController::class, 'myProducts']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::delete('/products/{id}/images/{imageId}', [ProductController::class, 'deleteImage']);
    Route::post('/products/{id}/upgrade', [ProductController::class, 'upgrade']);
    Route::post('/products/{id}/reviews', [ReviewController::class, 'store']);
    Route::post('/wishlists/toggle/{productId}', [WishlistController::class, 'toggle']);
    Route::get('/wishlists', [WishlistController::class, 'index']);
    Route::post('/products/{id}/report', [ReportedAdController::class, 'store']);
    
    // User Profile
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::delete('/profile', [AuthController::class, 'deleteAccount']);
    Route::put('/profile/password', [AuthController::class, 'changePassword']);

    // Follow System
    Route::get('/user/followers', [FollowController::class, 'followers']);
    Route::get('/user/following', [FollowController::class, 'following']);
    Route::post('/user/follow/{id}', [FollowController::class, 'follow']);
    Route::post('/user/unfollow/{id}', [FollowController::class, 'unfollow']);

    // Payments
    Route::get('/payments', [PaymentController::class, 'index']);

    // Membership
    Route::post('/subscribe/init', [MembershipPlanController::class, 'initiatePaystack']);
    Route::post('/subscribe/verify', [MembershipPlanController::class, 'verifyPaystack']);
});

Route::get('/form-data', [ProductController::class, 'create']);
