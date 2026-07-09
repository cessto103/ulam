<?php

use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\CommunityPriceReportController as AdminCommunityPriceReportController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\GovernmentPriceReferenceController as AdminGovernmentPriceReferenceController;
use App\Http\Controllers\Api\Admin\ListingReportController as AdminListingReportController;
use App\Http\Controllers\Api\Admin\MarketController as AdminMarketController;
use App\Http\Controllers\Api\Admin\MarketPriceController as AdminMarketPriceController;
use App\Http\Controllers\Api\Admin\PostCommentController as AdminPostCommentController;
use App\Http\Controllers\Api\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\Admin\RecipeController as AdminRecipeController;
use App\Http\Controllers\Api\Admin\TindahanController as AdminTindahanController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ConnectionController;
use App\Http\Controllers\Api\ListingReportController;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\MealPlanController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PriceController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\TindahanController;
use App\Http\Controllers\Api\UpgradeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/upgrade/webhook', [UpgradeController::class, 'webhook']); // PayMongo — no auth

Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1');

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/budget/current', [BudgetController::class, 'current']);
    Route::get('/budget/today', [BudgetController::class, 'current']); // alias
    Route::get('/budget/for-date', [BudgetController::class, 'forDate']);
    Route::get('/budget/history', [BudgetController::class, 'history']);
    Route::post('/budget/setup', [BudgetController::class, 'setup']);
    Route::post('/budget/log', [BudgetController::class, 'log']);

    Route::get('/meal-plan/today', [MealPlanController::class, 'today']);
    Route::get('/meal-plans/today', [MealPlanController::class, 'today']);       // alias
    Route::post('/meal-plan/generate', [MealPlanController::class, 'generate']);
    Route::post('/meal-plans/generate', [MealPlanController::class, 'generate']); // alias
    Route::post('/meal-plan/regenerate', [MealPlanController::class, 'regenerate']);
    Route::post('/meal-plan/add-item',   [MealPlanController::class, 'addItem']);
    Route::delete('/meal-plan/items/{id}', [MealPlanController::class, 'removeItem']);
    Route::get('/meal-plans/dates',        [MealPlanController::class, 'datesWithPlans']);

    Route::get('/prices/nearby',          [PriceController::class, 'nearby']);
    Route::get('/prices/search',          [PriceController::class, 'search']);
    Route::post('/prices/report',         [PriceController::class, 'report']);
    Route::get('/prices/item/{name}',     [PriceController::class, 'item']);
    Route::post('/prices/report/{id}/vote', [PriceController::class, 'vote']);
    Route::get('/prices/history/{item}',  [PriceController::class, 'history']);

    Route::get('/community/feed',              [CommunityController::class, 'feed']);
    Route::get('/community/post/{id}',         [CommunityController::class, 'show']);
    Route::post('/community/post',             [CommunityController::class, 'store']);
    Route::post('/community/post/{id}/react',    [CommunityController::class, 'react']);
    Route::post('/community/post/{id}/dislike', [CommunityController::class, 'dislike']);
    Route::post('/community/post/{id}/save',    [CommunityController::class, 'save']);
    Route::patch('/community/post/{id}',       [CommunityController::class, 'update']);
    Route::delete('/community/post/{id}',      [CommunityController::class, 'destroy']);
    Route::get('/community/post/{id}/comments',   [CommentController::class, 'index']);
    Route::post('/community/post/{id}/comments',  [CommentController::class, 'store']);
    Route::patch('/community/comment/{id}',       [CommentController::class, 'update']);
    Route::delete('/community/comment/{id}',      [CommentController::class, 'destroy']);

    Route::get('/recipes',               [RecipeController::class, 'index']);
    Route::post('/recipes',              [RecipeController::class, 'store']);
    Route::get('/recipes/{id}',          [RecipeController::class, 'show']);
    Route::match(['post','patch'], '/recipes/{id}', [RecipeController::class, 'update']);
    Route::patch('/recipes/{id}/share',   [RecipeController::class, 'share']);
    Route::get('/recipes/{id}/sharers',  [RecipeController::class, 'sharers']);
    Route::post('/recipes/{id}/save',    [RecipeController::class, 'saveToBook']);
    Route::post('/recipes/{id}/rate',    [RecipeController::class, 'rate']);
    Route::post('/recipes/{id}/react',   [RecipeController::class, 'react']);
    Route::get('/recipe-book',           [RecipeController::class, 'book']);

    Route::get('/users/search', [UserController::class, 'search']);
    Route::get('/user', [UserController::class, 'me']);
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::patch('/user/profile', [UserController::class, 'update']);
    Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);
    Route::get('/user/achievements', [UserController::class, 'achievements']);
    Route::get('/user/stats', [UserController::class, 'stats']);

    Route::get('/leaderboard/barangay', [UserController::class, 'leaderboard']);

    Route::post('/user/push-token', [NotificationController::class, 'registerToken']);

    Route::get('/notifications',           [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read',[NotificationController::class, 'markRead']);

    Route::get('/users/{id}',             [ConnectionController::class, 'profile']);
    Route::post('/users/{id}/follow',     [ConnectionController::class, 'follow']);
    Route::delete('/users/{id}/follow',   [ConnectionController::class, 'unfollow']);
    Route::get('/connections/following',  [ConnectionController::class, 'following']);
    Route::get('/connections/followers',  [ConnectionController::class, 'followers']);

    Route::post('/upgrade/checkout',      [UpgradeController::class, 'checkout']);

    Route::get('/markets',              [MarketController::class, 'index']);
    Route::post('/markets',             [MarketController::class, 'store']);
    Route::get('/markets/{id}',         [MarketController::class, 'show']);
    Route::post('/markets/{id}/refresh',[MarketController::class, 'refreshPrices']);

    Route::get('/tindahan/mine',   [TindahanController::class, 'mine']);
    Route::get('/tindahan/{id}',   [TindahanController::class, 'show']);
    Route::post('/tindahan',       [TindahanController::class, 'store']);
    Route::patch('/tindahan/{id}', [TindahanController::class, 'update']);
    Route::delete('/tindahan/{id}',[TindahanController::class, 'destroy']);
    Route::post('/tindahan/{id}/prices',  [TindahanController::class, 'addPrice']);
    Route::patch('/tindahan/{id}/prices/{priceId}', [TindahanController::class, 'updatePrice']);

    Route::post('/listing-reports', [ListingReportController::class, 'store']);
});

// Admin — uLam-admin SPA. Bearer-token auth (same Sanctum mechanism as the mobile
// app), gated additionally by the 'admin' middleware (role=admin && !banned).
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/me',      [AdminAuthController::class, 'me']);
    Route::patch('/profile',        [AdminAuthController::class, 'updateProfile']);
    Route::post('/change-password', [AdminAuthController::class, 'changePassword']);

    Route::get('/dashboard/stats',          [AdminDashboardController::class, 'stats']);
    Route::get('/dashboard/growth',         [AdminDashboardController::class, 'growth']);
    Route::get('/dashboard/xp-leaderboard', [AdminDashboardController::class, 'xpLeaderboard']);

    Route::get('/payments',      [AdminPaymentController::class, 'index']);
    Route::get('/payments/{id}', [AdminPaymentController::class, 'show']);

    Route::get('/users',           [AdminUserController::class, 'index']);
    Route::post('/users',          [AdminUserController::class, 'store']);
    Route::get('/users/{id}',      [AdminUserController::class, 'show']);
    Route::patch('/users/{id}',    [AdminUserController::class, 'update']);
    Route::delete('/users/{id}',   [AdminUserController::class, 'destroy']);
    Route::post('/users/{id}/ban', [AdminUserController::class, 'ban']);
    Route::post('/users/{id}/unban', [AdminUserController::class, 'unban']);

    Route::get('/posts',        [AdminPostController::class, 'index']);
    Route::get('/posts/{id}',   [AdminPostController::class, 'show']);
    Route::patch('/posts/{id}', [AdminPostController::class, 'update']);
    Route::delete('/posts/{id}',[AdminPostController::class, 'destroy']);

    Route::get('/comments',        [AdminPostCommentController::class, 'index']);
    Route::get('/comments/{id}',   [AdminPostCommentController::class, 'show']);
    Route::delete('/comments/{id}',[AdminPostCommentController::class, 'destroy']);

    Route::get('/markets',                [AdminMarketController::class, 'index']);
    Route::post('/markets',               [AdminMarketController::class, 'store']);
    Route::get('/markets/{id}',           [AdminMarketController::class, 'show']);
    Route::patch('/markets/{id}',         [AdminMarketController::class, 'update']);
    Route::delete('/markets/{id}',        [AdminMarketController::class, 'destroy']);
    Route::post('/markets/{id}/refresh-ai', [AdminMarketController::class, 'refreshAi']);

    Route::get('/tindahan',        [AdminTindahanController::class, 'index']);
    Route::post('/tindahan',       [AdminTindahanController::class, 'store']);
    Route::get('/tindahan/{id}',   [AdminTindahanController::class, 'show']);
    Route::patch('/tindahan/{id}', [AdminTindahanController::class, 'update']);
    Route::delete('/tindahan/{id}',[AdminTindahanController::class, 'destroy']);

    Route::get('/market-prices',        [AdminMarketPriceController::class, 'index']);
    Route::post('/market-prices',       [AdminMarketPriceController::class, 'store']);
    Route::get('/market-prices/{id}',   [AdminMarketPriceController::class, 'show']);
    Route::patch('/market-prices/{id}', [AdminMarketPriceController::class, 'update']);
    Route::delete('/market-prices/{id}',[AdminMarketPriceController::class, 'destroy']);

    Route::get('/community-price-reports',           [AdminCommunityPriceReportController::class, 'index']);
    Route::post('/community-price-reports',          [AdminCommunityPriceReportController::class, 'store']);
    Route::get('/community-price-reports/{id}',      [AdminCommunityPriceReportController::class, 'show']);
    Route::patch('/community-price-reports/{id}',    [AdminCommunityPriceReportController::class, 'update']);
    Route::delete('/community-price-reports/{id}',   [AdminCommunityPriceReportController::class, 'destroy']);
    Route::post('/community-price-reports/{id}/verify', [AdminCommunityPriceReportController::class, 'verify']);

    Route::get('/government-price-references',      [AdminGovernmentPriceReferenceController::class, 'index']);
    Route::post('/government-price-references',     [AdminGovernmentPriceReferenceController::class, 'store']);
    Route::get('/government-price-references/{id}', [AdminGovernmentPriceReferenceController::class, 'show']);
    Route::patch('/government-price-references/{id}', [AdminGovernmentPriceReferenceController::class, 'update']);
    Route::delete('/government-price-references/{id}', [AdminGovernmentPriceReferenceController::class, 'destroy']);

    Route::get('/recipes',        [AdminRecipeController::class, 'index']);
    Route::post('/recipes',       [AdminRecipeController::class, 'store']);
    Route::get('/recipes/{id}',   [AdminRecipeController::class, 'show']);
    Route::patch('/recipes/{id}', [AdminRecipeController::class, 'update']);
    Route::delete('/recipes/{id}',[AdminRecipeController::class, 'destroy']);
    Route::get('/recipes/{id}/ingredients',    [AdminRecipeController::class, 'ingredients']);
    Route::post('/recipes/{id}/ingredients',   [AdminRecipeController::class, 'addIngredient']);
    Route::patch('/recipes/{id}/ingredients/{ingredientId}', [AdminRecipeController::class, 'updateIngredient']);
    Route::delete('/recipes/{id}/ingredients/{ingredientId}', [AdminRecipeController::class, 'destroyIngredient']);

    Route::get('/listing-reports',      [AdminListingReportController::class, 'index']);
    Route::get('/listing-reports/{id}', [AdminListingReportController::class, 'show']);
    Route::delete('/listing-reports/{id}', [AdminListingReportController::class, 'destroy']);
    Route::post('/listing-reports/{id}/ban-owner',        [AdminListingReportController::class, 'banOwner']);
    Route::post('/listing-reports/{id}/deactivate-listing', [AdminListingReportController::class, 'deactivateListing']);
    Route::post('/listing-reports/{id}/dismiss',           [AdminListingReportController::class, 'dismiss']);
});
