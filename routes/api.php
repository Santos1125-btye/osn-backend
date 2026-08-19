<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ProviderServiceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProviderBookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\ProviderBankAccountController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Api\MessageReadController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\TypingController;
use App\Http\Controllers\Api\ProviderDashboardController;
use App\Http\Controllers\Api\ProviderProfileController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ProviderPortfolioController;
use App\Http\Controllers\ProviderAvailabilityController;
use App\Http\Controllers\Api\SupportReportController;
use App\Http\Controllers\Api\CustomerDashboardController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\CustomerServiceController;
use App\Http\Controllers\CustomerProviderController;
use App\Http\Controllers\CustomerProviderDetailsController;
use App\Http\Controllers\Api\DisputeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Api\DeviceTokenController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/change-email', [AuthController::class, 'changeEmail']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);

    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
    Route::put('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/dispute', [DisputeController::class, 'store']);

    Route::get('/provider/bookings', [ProviderBookingController::class, 'index']);
    Route::get('/provider/bookings/{booking}', [ProviderBookingController::class, 'show']);
    Route::put('/provider/bookings/{booking}/price', [ProviderBookingController::class, 'setPrice']);

    Route::put('/provider/bookings/{booking}/accept', [ProviderBookingController::class, 'accept']);
    Route::put('/provider/bookings/{booking}/reject', [ProviderBookingController::class, 'reject']);
    Route::put('/provider/bookings/{booking}/start', [ProviderBookingController::class, 'start']);
    Route::put('/provider/bookings/{booking}/complete', [ProviderBookingController::class, 'complete']);


    Route::post('/bookings/{booking}/payment/initialize',[PaymentController::class, 'initialize']);

    Route::post('/payments/verify',[PaymentController::class, 'verify']);
    Route::get('/transactions', [TransactionController::class, 'index']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{provider}', [FavoriteController::class, 'destroy']);
    Route::get('/favorites/{provider}/check', [FavoriteController::class, 'isFavorite']);

    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
    Route::patch('/addresses/{address}/default', [AddressController::class, 'setDefault']);

    Route::get('/provider/banks',[ProviderBankAccountController::class, 'banks']);
    Route::get('/provider/bank-accounts',[ProviderBankAccountController::class, 'index']);

    Route::post('/provider/bank-accounts',[ProviderBankAccountController::class, 'store']);

    Route::post('/provider/resolve-bank-account',[ProviderBankAccountController::class, 'resolve']);

    Route::delete('/provider/bank-accounts/{bankAccount}',[ProviderBankAccountController::class, 'destroy']);

    Route::patch('/provider/bank-accounts/{bankAccount}/default',[ProviderBankAccountController::class, 'setDefault']);

    Route::get('/provider/wallet',[WithdrawalController::class, 'wallet']);
    Route::get('/provider/withdrawals',[WithdrawalController::class, 'index']);
    Route::post('/provider/withdrawals',[WithdrawalController::class, 'store']);

    Route::get('/conversations/{conversation}/messages',[MessageController::class, 'index']);
    Route::post('/messages',[MessageController::class, 'store']);

    Route::patch('/messages/{message}/delivered',[MessageReadController::class, 'delivered']);
    Route::patch('/messages/{message}/read',[MessageReadController::class, 'read']);

    Route::get('/conversations',[ConversationController::class, 'index']);

    Route::get('/conversations/customer-support', [ConversationController::class, 'customerSupportConversation']);

    Route::get('/conversations/{conversation}',[ConversationController::class, 'show']);

    Route::get('/support/conversation',[ConversationController::class, 'supportConversation']);

    Route::post('/presence/heartbeat',[PresenceController::class, 'heartbeat']);

    Route::get('/presence/{userId}',[PresenceController::class, 'status']);

    Route::get('/provider/services',[ProviderServiceController::class, 'index']);

    Route::post('/provider/services',[ProviderServiceController::class, 'store']);

    Route::put('/provider/services/{providerService}',[ProviderServiceController::class, 'update']);

    Route::delete('/provider/services/{providerService}',[ProviderServiceController::class, 'destroy']);

    Route::put('/provider/services/{providerService}/status',[ProviderServiceController::class, 'toggleStatus']);

    // customer API
    Route::get('/customer/dashboard',[CustomerDashboardController::class, 'index']);

});

Route::post('/payments/webhook',[PaystackWebhookController::class, 'handle']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
});
Route::get('/subcategories',[SubCategoryController::class, 'index']);
Route::get('/services',[CustomerServiceController::class, 'index']);
Route::get('/services/{service}/providers', [CustomerProviderController::class, 'index']);
Route::get('/customer/providers/{provider}', [CustomerProviderDetailsController::class, 'show']
);

Route::get('/providers', [ProviderController::class, 'index']);
Route::get('/providers/{provider}', [ProviderController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/provider/profile', [ProviderController::class, 'store']);
    Route::put('/provider/profile', [ProviderController::class, 'update']);
    Route::get('/provider/profile', [ProviderController::class, 'profile']);
});

Route::get('/providers/{provider}/reviews', [ReviewController::class, 'providerReviews']);
Route::get('/providers/{provider}/review-summary', [ReviewController::class, 'summary']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store']);
});


Route::get('/search', [SearchController::class, 'search']);
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/provider/profile',[ProviderProfileController::class, 'show']);

    Route::post('/provider/profile/business',[ProviderProfileController::class, 'storeBusiness']);

    Route::post('/provider/profile/branding',[ProviderProfileController::class, 'updateBranding']);

    Route::post('/provider/profile/submit',[ProviderProfileController::class, 'submitForVerification']);

    Route::put('/provider/profile/location',[ProviderProfileController::class, 'updateLocation']);

    Route::post('/provider/profile/verification',[ProviderProfileController::class, 'updateVerification']);

    Route::put('/provider/profile/social-links',[ProviderProfileController::class, 'updateSocialLinks']);

    Route::post('/provider/onboarding/submit',[ProviderProfileController::class, 'submitOnboarding']);

    Route::post('/provider/profile/submit',[ProviderProfileController::class, 'submitForVerification']);

    Route::get('/provider/portfolios',[ProviderPortfolioController::class, 'index']);

    Route::post('/provider/portfolios',[ProviderPortfolioController::class, 'store']);

    Route::get('/provider/portfolios/{portfolio}',[ProviderPortfolioController::class, 'show']);

    Route::put('/provider/portfolios/{portfolio}',[ProviderPortfolioController::class, 'update']);

    Route::delete('/provider/portfolios/{portfolio}',[ProviderPortfolioController::class, 'destroy']);

    Route::post('/support/reports',[SupportReportController::class, 'store']);

    Route::get('/support/reports',[SupportReportController::class, 'index']);

});

Route::get('/countries', [LocationController::class, 'countries']);
Route::get('/countries/{country}/states', [LocationController::class, 'states']);
Route::get('/states/{state}/cities', [LocationController::class, 'cities']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/messages/upload',[MessageController::class, 'upload']);

    Route::patch('/messages/{message}',[MessageController::class, 'update']);

    Route::get('/bookings/{booking}/conversation',[ConversationController::class, 'bookingConversation']);

    Route::post('/conversations/{conversation}/typing/start',[TypingController::class, 'start']);

    Route::post('/conversations/{conversation}/typing/stop',[TypingController::class, 'stop']);

    Route::get('/conversations/{conversation}/typing/{userId}',[TypingController::class, 'status']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/provider/dashboard',[ProviderDashboardController::class, 'index']);

    Route::put('/provider/availability',[ProviderDashboardController::class, 'updateAvailability']);

    Route::get('/provider/availability',[ProviderAvailabilityController::class, 'show']);

    Route::put('/provider/availability/settings',[ProviderAvailabilityController::class, 'update']);
    Route::get('/providers/{provider}/availability', [ProviderAvailabilityController::class, 'customerAvailability']);

});

Route::put('/admin/providers/{provider}/verify',[ProviderDashboardController::class, 'verifyProvider']);