<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AffiliateController as AdminAffiliateController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\EmailPresetController;
use App\Http\Controllers\Admin\EmailSettingsController;
use App\Http\Controllers\Admin\ExtensionSettingsController;
use App\Http\Controllers\Admin\LegalPageController;
use App\Http\Controllers\Admin\PaymentIntegrationController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SessionController as AdminSessionController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\ToolCatalogController;
use App\Http\Controllers\Admin\ToolGroupController;
use App\Http\Controllers\Admin\ToolServerController;
use App\Http\Controllers\Admin\WalletController as AdminWalletController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\OtpAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AuthPageController;
use App\Http\Controllers\Dashboard\CheckoutController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\OrderController as DashboardOrderController;
use App\Http\Controllers\Dashboard\PayPalController;
use App\Http\Controllers\Dashboard\SupportTicketController as DashboardSupportTicketController;
use App\Http\Controllers\Dashboard\ToolController;
use App\Http\Controllers\Dashboard\UserSettingsController;
use App\Http\Controllers\Dashboard\WalletController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\BuyahrefWebhookController;
use App\Http\Controllers\PayPalWebhookController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/paypal', PayPalWebhookController::class)->name('webhooks.paypal');
Route::post('/webhooks/buyahref', BuyahrefWebhookController::class)->name('webhooks.buyahref');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/subscribe', SubscribeController::class)->name('subscribe');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/refund', [LegalController::class, 'refund'])->name('legal.refund');
Route::get('/legal/{slug}', [LegalController::class, 'show'])->name('legal.show');

Route::redirect('/semrush-group-buy', '/tools/semrush-group-buy', 301);
Route::redirect('/ahrefs-group-buy', '/tools/ahrefs-group-buy', 301);

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Article pages are registered dynamically from DB in AppServiceProvider

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthPageController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/login/otp', [OtpAuthController::class, 'loginForm'])->name('login.otp.form');
    Route::post('/login/otp/send', [OtpAuthController::class, 'sendLoginOtp'])->name('login.otp.send');
    Route::post('/login/otp/verify', [OtpAuthController::class, 'verifyLoginOtp'])->name('login.otp.verify');
    Route::post('/login/otp/resend', [OtpAuthController::class, 'resendLoginOtp'])->name('login.otp.resend');

    Route::get('/login/verify-otp', [OtpAuthController::class, 'challengeForm'])->name('login.otp.challenge');
    Route::post('/login/verify-otp', [OtpAuthController::class, 'verifyChallenge'])->name('login.otp.challenge.verify');
    Route::post('/login/verify-otp/resend', [OtpAuthController::class, 'resendChallenge'])->name('login.otp.challenge.resend');

    Route::get('/register', [AuthPageController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.submit');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.store');
});

Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
    ->middleware('throttle:6,1')
    ->name('verification.send');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::prefix('dashboard')->name('dashboard.')->middleware(['auth', 'verified', 'user.blocked', 'device.bind', 'dashboard.track'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/activity', [DashboardController::class, 'activity'])->name('activity');
    Route::get('/shop', [DashboardController::class, 'shop'])->name('shop');
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/tools', [DashboardController::class, 'tools'])->name('tools');
    Route::get('/tools/access/{group}', [ToolController::class, 'hub'])->name('tools.hub');
    Route::get('/tools/route/{tool}', [ToolController::class, 'route'])->name('tools.route');
    Route::post('/tools/ext/{tool}', [ToolController::class, 'extAccess'])->name('tools.ext');
    Route::post('/tools/{tool}/end', [ToolController::class, 'endSession'])->name('tools.end');
    Route::get('/orders', [DashboardController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [DashboardOrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/invoice', [DashboardOrderController::class, 'invoice'])->name('orders.invoice');
    Route::get('/orders/{order}/pay/upi', [DashboardOrderController::class, 'payUpi'])->name('orders.pay.upi');
    Route::get('/orders/{order}/payment/return', [DashboardOrderController::class, 'paymentReturn'])->name('orders.payment.return');
    Route::get('/orders/{order}/pay/offline', [DashboardOrderController::class, 'payOffline'])->name('orders.pay.offline');
    Route::get('/orders/{order}/pay/paypal', [DashboardOrderController::class, 'payPaypal'])->name('orders.pay.paypal');
    Route::post('/orders/{order}/paypal/approve', [PayPalController::class, 'approve'])->name('orders.paypal.approve');
    Route::post('/orders/{order}/proof', [DashboardOrderController::class, 'uploadProof'])->name('orders.proof');
    Route::get('/orders/{order}/status', [DashboardOrderController::class, 'status'])->name('orders.status');
    Route::get('/extensions', [DashboardController::class, 'extensions'])->name('extensions');
    Route::get('/affiliates', [DashboardController::class, 'affiliates'])->name('affiliates');
    Route::get('/affiliates/export/{format}', [DashboardController::class, 'exportAffiliates'])->name('affiliates.export');
    Route::post('/affiliates/payout', [DashboardController::class, 'requestPayout'])->name('affiliates.payout');
    Route::post('/affiliates/transfer-wallet', [DashboardController::class, 'transferToWallet'])->name('affiliates.transfer-wallet');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    Route::get('/wallet/topup', [WalletController::class, 'topup'])->name('wallet.topup');
    Route::post('/wallet/topup', [WalletController::class, 'storeTopup'])->name('wallet.topup.store');
    Route::get('/support', [DashboardController::class, 'support'])->name('support');
    Route::get('/support/create', [DashboardSupportTicketController::class, 'create'])->name('support.create');
    Route::post('/support', [DashboardSupportTicketController::class, 'store'])->name('support.store');
    Route::get('/support/{ticket}', [DashboardSupportTicketController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [DashboardSupportTicketController::class, 'reply'])->name('support.reply');
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::post('/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/avatar', [DashboardController::class, 'updateAvatar'])->name('profile.avatar');
    Route::delete('/profile/avatar', [DashboardController::class, 'removeAvatar'])->name('profile.avatar.remove');
    Route::post('/profile/password', [DashboardController::class, 'updatePassword'])->name('profile.password');
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
    Route::put('/settings', [UserSettingsController::class, 'update'])->name('settings.update');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthPageController::class, 'adminLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'adminLogin'])->name('login.submit');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/export/csv', [AdminController::class, 'exportUsers'])->name('users.export');
        Route::get('/users/unverified', [AdminController::class, 'unverifiedUsers'])->name('users.unverified');
        Route::post('/users/unverified/purge-eligible', [AdminController::class, 'purgeEligibleUnverified'])->name('users.unverified.purge-eligible');
        Route::delete('/users/unverified/{user}', [AdminUserController::class, 'deleteUnverified'])->name('users.unverified.delete');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/password', [AdminUserController::class, 'updatePassword'])->name('users.password');
        Route::post('/users/{user}/subscriptions', [AdminUserController::class, 'grantSubscription'])->name('users.subscriptions.grant');
        Route::post('/users/{user}/subscriptions/{subscription}/extend', [AdminUserController::class, 'extendSubscription'])->name('users.subscriptions.extend');
        Route::post('/users/{user}/subscriptions/{subscription}/cancel', [AdminUserController::class, 'cancelSubscription'])->name('users.subscriptions.cancel');
        Route::post('/users/{user}/kill-sessions', [AdminUserController::class, 'killSessions'])->name('users.kill-sessions');
        Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::post('/plans/{plan}/toggle', [PlanController::class, 'toggle'])->name('plans.toggle');

        Route::get('/tools', [ToolCatalogController::class, 'index'])->name('tools.index');
        Route::get('/tools/create', [ToolCatalogController::class, 'create'])->name('tools.create');
        Route::post('/tools', [ToolCatalogController::class, 'store'])->name('tools.store');
        Route::get('/tools/seo/suggestions', [ToolCatalogController::class, 'seoSuggestions'])->name('tools.seo-suggestions');
        Route::get('/tools/{tool}/edit', [ToolCatalogController::class, 'edit'])->name('tools.edit');
        Route::get('/tools/{tool}/preview', [ToolCatalogController::class, 'preview'])->name('tools.preview');
        Route::put('/tools/{tool}', [ToolCatalogController::class, 'update'])->name('tools.update');
        Route::post('/tools/{tool}/toggle', [ToolCatalogController::class, 'toggle'])->name('tools.toggle');

        Route::get('/articles', [AdminArticleController::class, 'index'])->name('articles.index');
        Route::get('/articles/create', [AdminArticleController::class, 'create'])->name('articles.create');
        Route::get('/articles/seo/suggestions', [AdminArticleController::class, 'seoSuggestions'])->name('articles.seo-suggestions');
        Route::get('/articles/{article}/preview', [AdminArticleController::class, 'preview'])->name('articles.preview');
        Route::post('/articles', [AdminArticleController::class, 'store'])->name('articles.store');
        Route::get('/articles/{article}/edit', [AdminArticleController::class, 'edit'])->name('articles.edit');
        Route::put('/articles/{article}', [AdminArticleController::class, 'update'])->name('articles.update');
        Route::delete('/articles/{article}', [AdminArticleController::class, 'destroy'])->name('articles.destroy');
        Route::post('/articles/{article}/toggle', [AdminArticleController::class, 'toggle'])->name('articles.toggle');

        Route::get('/tool-groups', [ToolGroupController::class, 'index'])->name('tool-groups.index');
        Route::get('/tool-groups/create', [ToolGroupController::class, 'create'])->name('tool-groups.create');
        Route::post('/tool-groups', [ToolGroupController::class, 'store'])->name('tool-groups.store');
        Route::get('/tool-groups/{toolGroup}/edit', [ToolGroupController::class, 'edit'])->name('tool-groups.edit');
        Route::put('/tool-groups/{toolGroup}', [ToolGroupController::class, 'update'])->name('tool-groups.update');

        Route::get('/legal-pages', [LegalPageController::class, 'index'])->name('legal-pages.index');
        Route::get('/legal-pages/create', [LegalPageController::class, 'create'])->name('legal-pages.create');
        Route::post('/legal-pages', [LegalPageController::class, 'store'])->name('legal-pages.store');
        Route::get('/legal-pages/{legalPage}/edit', [LegalPageController::class, 'edit'])->name('legal-pages.edit');
        Route::get('/legal-pages/{legalPage}/preview', [LegalPageController::class, 'preview'])->name('legal-pages.preview');
        Route::put('/legal-pages/{legalPage}', [LegalPageController::class, 'update'])->name('legal-pages.update');
        Route::delete('/legal-pages/{legalPage}', [LegalPageController::class, 'destroy'])->name('legal-pages.destroy');

        Route::get('/tool-servers', [ToolServerController::class, 'index'])->name('tool-servers.index');
        Route::get('/tool-servers/create', [ToolServerController::class, 'create'])->name('tool-servers.create');
        Route::post('/tool-servers', [ToolServerController::class, 'store'])->name('tool-servers.store');
        Route::get('/tool-servers/{toolServer}/edit', [ToolServerController::class, 'edit'])->name('tool-servers.edit');
        Route::put('/tool-servers/{toolServer}', [ToolServerController::class, 'update'])->name('tool-servers.update');
        Route::delete('/tool-servers/{toolServer}', [ToolServerController::class, 'destroy'])->name('tool-servers.destroy');
        Route::post('/tool-servers/{toolServer}/toggle', [ToolServerController::class, 'toggle'])->name('tool-servers.toggle');

        Route::get('/extension-settings', [ExtensionSettingsController::class, 'edit'])->name('extension-settings.edit');
        Route::put('/extension-settings', [ExtensionSettingsController::class, 'update'])->name('extension-settings.update');

        Route::get('/email-settings', [EmailSettingsController::class, 'edit'])->name('email-settings.edit');
        Route::put('/email-settings', [EmailSettingsController::class, 'update'])->name('email-settings.update');
        Route::post('/email-settings/test-connection', [EmailSettingsController::class, 'testConnection'])->name('email-settings.test-connection');
        Route::put('/email-settings/daily-limit', [EmailSettingsController::class, 'updateDailyLimit'])->name('email-settings.daily-limit');
        Route::post('/email-settings/sync-all', [EmailSettingsController::class, 'syncAll'])->name('email-settings.sync-all');
        Route::post('/email-settings/test-send', [EmailSettingsController::class, 'testSend'])->name('email-settings.test-send');

        Route::post('/email-presets/preview', [EmailPresetController::class, 'preview'])->name('email-presets.preview');
        Route::get('/email-presets', [EmailPresetController::class, 'index'])->name('email-presets.index');
        Route::get('/email-presets/create', [EmailPresetController::class, 'create'])->name('email-presets.create');
        Route::post('/email-presets', [EmailPresetController::class, 'store'])->name('email-presets.store');
        Route::get('/email-presets/{emailPreset}/edit', [EmailPresetController::class, 'edit'])->name('email-presets.edit');
        Route::put('/email-presets/{emailPreset}', [EmailPresetController::class, 'update'])->name('email-presets.update');
        Route::delete('/email-presets/{emailPreset}', [EmailPresetController::class, 'destroy'])->name('email-presets.destroy');
        Route::post('/email-presets/{emailPreset}/sync', [EmailPresetController::class, 'sync'])->name('email-presets.sync');

        Route::get('/payment-integration', [PaymentIntegrationController::class, 'edit'])->name('payment-integration.edit');
        Route::put('/payment-integration', [PaymentIntegrationController::class, 'update'])->name('payment-integration.update');
        Route::post('/payment-integration/buyahref/test', [PaymentIntegrationController::class, 'testBuyahref'])->name('payment-integration.buyahref.test');
        Route::put('/payment-integration/paypal', [PaymentIntegrationController::class, 'updatePayPal'])->name('payment-integration.paypal');
        Route::post('/payment-integration/paypal/test', [PaymentIntegrationController::class, 'testPayPal'])->name('payment-integration.paypal.test');

        Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
        Route::get('/orders/export/csv', [AdminController::class, 'exportOrders'])->name('orders.export');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/invoice', [AdminOrderController::class, 'invoice'])->name('orders.invoice');
        Route::post('/orders/{order}/approve', [AdminOrderController::class, 'approve'])->name('orders.approve');
        Route::post('/orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('orders.refund');
        Route::post('/orders/{order}/revoke-access', [AdminOrderController::class, 'revokeAccess'])->name('orders.revoke-access');
        Route::post('/orders/{order}/reject', [AdminOrderController::class, 'reject'])->name('orders.reject');
        Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
        Route::get('/wallet', [AdminWalletController::class, 'index'])->name('wallet');
        Route::get('/wallet/export/csv', [AdminWalletController::class, 'export'])->name('wallet.export');
        Route::post('/wallet/adjust', [AdminWalletController::class, 'adjust'])->name('wallet.adjust');
        Route::get('/sessions', [AdminSessionController::class, 'index'])->name('sessions');
        Route::delete('/sessions/{session}', [AdminSessionController::class, 'destroy'])->name('sessions.destroy');
        Route::get('/tickets', [AdminSupportTicketController::class, 'index'])->name('tickets');
        Route::get('/tickets/{ticket}', [AdminSupportTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/reply', [AdminSupportTicketController::class, 'reply'])->name('tickets.reply');
        Route::post('/tickets/{ticket}/close', [AdminSupportTicketController::class, 'close'])->name('tickets.close');
        Route::get('/affiliates', [AdminAffiliateController::class, 'index'])->name('affiliates');
        Route::get('/affiliates/export/{format}', [AdminAffiliateController::class, 'export'])->name('affiliates.export');
        Route::post('/affiliates/broadcast', [AdminAffiliateController::class, 'startBroadcast'])->name('affiliates.broadcast');
        Route::post('/affiliates/users/{user}/send-report', [AdminAffiliateController::class, 'sendMonthlyReport'])->name('affiliates.send-report');
        Route::post('/affiliates/payouts/{payout}/process', [AdminAffiliateController::class, 'process'])->name('affiliates.payout.process');
        Route::post('/affiliates/payouts/{payout}/reject', [AdminAffiliateController::class, 'reject'])->name('affiliates.payout.reject');
        Route::post('/affiliates/commissions/{commission}/approve', [AdminAffiliateController::class, 'approveCommission'])->name('affiliates.commission.approve');
        Route::post('/affiliates/commissions/{commission}/reject', [AdminAffiliateController::class, 'rejectCommission'])->name('affiliates.commission.reject');
        Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
        Route::get('/coupons/create', [CouponController::class, 'create'])->name('coupons.create');
        Route::post('/coupons', [CouponController::class, 'store'])->name('coupons.store');
        Route::get('/coupons/{coupon}/edit', [CouponController::class, 'edit'])->name('coupons.edit');
        Route::put('/coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
        Route::post('/coupons/{coupon}/toggle', [CouponController::class, 'toggle'])->name('coupons.toggle');
        Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings');
        Route::put('/settings/general', [AdminSettingsController::class, 'updateGeneral'])->name('settings.general');
        Route::put('/settings/affiliate', [AdminSettingsController::class, 'updateAffiliate'])->name('settings.affiliate');
        Route::put('/settings/wallet', [AdminSettingsController::class, 'updateWallet'])->name('settings.wallet');
        Route::put('/settings/support', [AdminSettingsController::class, 'updateSupport'])->name('settings.support');

        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/avatar', [AdminProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::delete('/profile/avatar', [AdminProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
        Route::post('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');

        Route::get('/security', [SecurityController::class, 'index'])->name('security');
        Route::get('/security/users/{user}', [SecurityController::class, 'showUser'])->name('security.user');
        Route::post('/security/users/{user}/block', [SecurityController::class, 'blockUser'])->name('security.block');
        Route::post('/security/users/{user}/unblock', [SecurityController::class, 'unblockUser'])->name('security.unblock');
        Route::post('/security/users/{user}/kill-sessions', [SecurityController::class, 'killAllSessions'])->name('security.kill-sessions');
        Route::delete('/security/sessions/{sessionId}', [SecurityController::class, 'killSession'])->name('security.sessions.destroy');
        Route::post('/security/alerts/{alert}/resolve', [SecurityController::class, 'resolveAlert'])->name('security.resolve');
    });
});
