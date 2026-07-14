<?php

use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\BoostController as AdminBoostController;
use App\Http\Controllers\Api\Admin\TindahanCommentController as AdminTindahanCommentController;
use App\Http\Controllers\Api\Admin\TindahanRatingController as AdminTindahanRatingController;
use App\Http\Controllers\Api\Admin\TwoFactorController as AdminTwoFactorController;
use App\Http\Controllers\Api\Admin\LegalDocumentController as AdminLegalDocumentController;
use App\Http\Controllers\Api\LegalController;
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
use App\Http\Controllers\Api\Admin\AppSettingController as AdminAppSettingController;
use App\Http\Controllers\Api\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Api\Admin\SellerPlanController as AdminSellerPlanController;
use App\Http\Controllers\Api\Admin\SellerSubscriptionController as AdminSellerSubscriptionController;
use App\Http\Controllers\Api\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Api\Admin\TindahanController as AdminTindahanController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\BillingController as AdminBillingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\BoostController;
use App\Http\Controllers\Api\PayMongoWebhookController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ConnectionController;
use App\Http\Controllers\Api\ListingReportController;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\MealPlanController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PriceController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\InsightsController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\SellerSubscriptionController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\TindahanController;
use App\Http\Controllers\Api\TindahanCommentController;
use App\Http\Controllers\Api\UpgradeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:4,1');
Route::post('/auth/login',    [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:4,1');
Route::post('/auth/reset-password',  [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');
Route::post('/upgrade/webhook', [UpgradeController::class, 'webhook']); // PayMongo — no auth

Route::post('/billing/webhooks/paymongo', PayMongoWebhookController::class);

Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1');

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::delete('/auth/account', [AuthController::class, 'deleteAccount'])->middleware('throttle:3,1');

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
    Route::get('/prices/my-reports',      [PriceController::class, 'myReports']);
    Route::get('/prices/search',          [PriceController::class, 'search']);
    Route::post('/prices/report',         [PriceController::class, 'report']);
    Route::post('/content-reports',       [\App\Http\Controllers\Api\ContentReportController::class, 'store']);
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

    Route::post('/user/secondary-email/request', [UserController::class, 'requestSecondaryEmail']);
    Route::post('/user/secondary-email/verify',  [UserController::class, 'verifySecondaryEmail']);
    Route::delete('/user/secondary-email',       [UserController::class, 'removeSecondaryEmail']);

    Route::get('/insights/summary', [InsightsController::class, 'summary']);
    Route::get('/insights/graph',   [InsightsController::class, 'graph']);

    Route::get('/legal/status',        [LegalController::class, 'status']);
    Route::get('/legal/{slug}',        [LegalController::class, 'show']);
    Route::post('/legal/{slug}/accept', [LegalController::class, 'accept']);

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

    Route::get('/billing/plans', [BillingController::class, 'catalog']);
    Route::get('/billing/status', [BillingController::class, 'status']);
    Route::get('/billing/history', [BillingController::class, 'history']);
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->middleware('throttle:10,1');
    Route::get('/billing/checkouts/{publicId}', [BillingController::class, 'checkoutStatus']);
    Route::post('/billing/subscriptions/{id}/cancel', [BillingController::class, 'cancel'])->middleware('throttle:6,1');

    Route::get('/markets',              [MarketController::class, 'index']);
    Route::post('/markets',             [MarketController::class, 'store']);
    Route::get('/markets/{id}',         [MarketController::class, 'show']);
    Route::post('/markets/{id}/refresh',[MarketController::class, 'refreshPrices']);

    Route::get('/tindahan/mine',   [TindahanController::class, 'mine']);
    Route::get('/tindahan-reports',                [TindahanController::class, 'pendingReports']);
    Route::post('/tindahan-reports/{id}/accept',   [TindahanController::class, 'acceptReport']);
    Route::post('/tindahan-reports/{id}/decline',  [TindahanController::class, 'declineReport']);
    Route::get('/tindahan/{id}',   [TindahanController::class, 'show']);
    Route::post('/tindahan',       [TindahanController::class, 'store']);
    Route::patch('/tindahan/{id}', [TindahanController::class, 'update']);
    Route::delete('/tindahan/{id}',[TindahanController::class, 'destroy']);
    Route::post('/tindahan/{id}/rate', [TindahanController::class, 'rate']);

    Route::get('/tindahan/{id}/comments',  [TindahanCommentController::class, 'index']);
    Route::post('/tindahan/{id}/comments', [TindahanCommentController::class, 'store']);
    Route::delete('/tindahan/comments/{id}', [TindahanCommentController::class, 'destroy']);
    Route::post('/tindahan/{id}/photos',  [TindahanController::class, 'uploadPhotos']);
    Route::post('/tindahan/{id}/prices',  [TindahanController::class, 'addPrice']);
    Route::patch('/tindahan/{id}/prices/{priceId}', [TindahanController::class, 'updatePrice']);

    Route::post('/listing-reports', [ListingReportController::class, 'store']);

    // Seller subscriptions (manual GCash flow)
    Route::get('/seller/plans',                 [SellerSubscriptionController::class, 'catalog']);
    Route::get('/seller/subscriptions',         [SellerSubscriptionController::class, 'index']);
    Route::post('/seller/subscriptions',        [SellerSubscriptionController::class, 'store']);
    Route::delete('/seller/subscriptions/{id}', [SellerSubscriptionController::class, 'destroy']);

    Route::get('/boosts',        [BoostController::class, 'index']);
    Route::post('/boosts',       [BoostController::class, 'store']);
    Route::delete('/boosts/{id}', [BoostController::class, 'destroy']);

    // Help & Support
    Route::get('/faqs',                        [SupportController::class, 'faqs']);
    Route::get('/support-tickets',             [SupportController::class, 'index']);
    Route::post('/support-tickets',            [SupportController::class, 'store']);
    Route::get('/support-tickets/{id}',        [SupportController::class, 'show']);
    Route::post('/support-tickets/{id}/reply', [SupportController::class, 'reply']);
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
    Route::get('/billing/summary', [AdminBillingController::class, 'summary']);
    Route::get('/billing/subscriptions', [AdminBillingController::class, 'subscriptions']);
    Route::get('/billing/webhooks', [AdminBillingController::class, 'webhooks']);
    Route::get('/billing/logs', [AdminBillingController::class, 'logs']);
    Route::post('/billing/payments/{paymentId}/refund', [AdminBillingController::class, 'refund'])->middleware('throttle:6,1');

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

    Route::get('/tindahan-comments',        [AdminTindahanCommentController::class, 'index']);
    Route::get('/tindahan-comments/{id}',   [AdminTindahanCommentController::class, 'show']);
    Route::delete('/tindahan-comments/{id}',[AdminTindahanCommentController::class, 'destroy']);

    Route::get('/tindahan-ratings',        [AdminTindahanRatingController::class, 'index']);
    Route::delete('/tindahan-ratings/{id}',[AdminTindahanRatingController::class, 'destroy']);

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

    Route::get('/seller-subscriptions',               [AdminSellerSubscriptionController::class, 'index']);
    Route::get('/seller-subscriptions/{id}',          [AdminSellerSubscriptionController::class, 'show']);
    Route::post('/seller-subscriptions/{id}/approve', [AdminSellerSubscriptionController::class, 'approve']);
    Route::post('/seller-subscriptions/{id}/reject',  [AdminSellerSubscriptionController::class, 'reject']);
    Route::post('/seller-subscriptions/{id}/refund',  [AdminSellerSubscriptionController::class, 'refund']);

    Route::get('/boosts',               [AdminBoostController::class, 'index']);
    Route::post('/boosts/{id}/approve', [AdminBoostController::class, 'approve']);
    Route::post('/boosts/{id}/reject',  [AdminBoostController::class, 'reject']);

    Route::get('/2fa/status',   [AdminTwoFactorController::class, 'status']);
    Route::post('/2fa/setup',   [AdminTwoFactorController::class, 'setup']);
    Route::post('/2fa/confirm', [AdminTwoFactorController::class, 'confirm']);
    Route::post('/2fa/disable', [AdminTwoFactorController::class, 'disable']);

    // Read-only render of the repo's TECHNICAL.md — single source of truth.
    Route::get('/technical-guide', function () {
        $path = base_path('TECHNICAL.md');
        abort_unless(file_exists($path), 404);
        return response()->json([
            'content_md' => file_get_contents($path),
            'updated_at' => date('c', filemtime($path)),
        ]);
    });

    Route::get('/legal-documents',                  [AdminLegalDocumentController::class, 'index']);
    Route::get('/legal-documents/{slug}/versions',  [AdminLegalDocumentController::class, 'versions']);
    Route::post('/legal-documents/{slug}/versions', [AdminLegalDocumentController::class, 'storeVersion']);
    Route::get('/legal-versions/{id}',              [AdminLegalDocumentController::class, 'showVersion']);
    Route::patch('/legal-versions/{id}',            [AdminLegalDocumentController::class, 'updateVersion']);
    Route::post('/legal-versions/{id}/publish',     [AdminLegalDocumentController::class, 'publish']);
    Route::post('/legal-versions/{id}/archive',     [AdminLegalDocumentController::class, 'archive']);
    Route::delete('/legal-versions/{id}',           [AdminLegalDocumentController::class, 'destroyVersion']);

    Route::get('/seller-plans',             [AdminSellerPlanController::class, 'index']);
    Route::patch('/seller-plans/{id}',      [AdminSellerPlanController::class, 'update']);
    Route::put('/seller-plans/{id}/prices', [AdminSellerPlanController::class, 'updatePrices']);
    Route::put('/seller-plans/{id}/features', [AdminSellerPlanController::class, 'updateFeatures']);
    Route::patch('/boost-options/{id}',     [AdminSellerPlanController::class, 'updateBoostOption']);

    Route::get('/app-settings', [AdminAppSettingController::class, 'index']);
    Route::put('/app-settings', [AdminAppSettingController::class, 'update']);

    Route::get('/support-tickets',             [AdminSupportTicketController::class, 'index']);
    Route::get('/support-tickets/{id}',        [AdminSupportTicketController::class, 'show']);
    Route::post('/support-tickets/{id}/reply', [AdminSupportTicketController::class, 'reply']);
    Route::post('/support-tickets/{id}/close', [AdminSupportTicketController::class, 'close']);

    Route::get('/faqs',         [AdminFaqController::class, 'index']);
    Route::post('/faqs',        [AdminFaqController::class, 'store']);
    Route::patch('/faqs/{id}',  [AdminFaqController::class, 'update']);
    Route::delete('/faqs/{id}', [AdminFaqController::class, 'destroy']);

    Route::get('/listing-reports',      [AdminListingReportController::class, 'index']);
    Route::get('/listing-reports/{id}', [AdminListingReportController::class, 'show']);
    Route::delete('/listing-reports/{id}', [AdminListingReportController::class, 'destroy']);
    Route::post('/listing-reports/{id}/ban-owner',        [AdminListingReportController::class, 'banOwner']);
    Route::post('/listing-reports/{id}/deactivate-listing', [AdminListingReportController::class, 'deactivateListing']);
    Route::post('/listing-reports/{id}/dismiss',           [AdminListingReportController::class, 'dismiss']);
});
