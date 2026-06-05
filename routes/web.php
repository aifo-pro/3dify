<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Admin\BlogTaxonomyController;
use App\Http\Controllers\Admin\BulkActionController;
use App\Http\Controllers\Admin\BundleAdminController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\CustomOrderAdminController;
use App\Http\Controllers\Admin\PrintChallengeAdminController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\FeaturedProductsController;
use App\Http\Controllers\Admin\KycAdminController;
use App\Http\Controllers\Admin\LegalPagesController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\PayoutAdminController;
use App\Http\Controllers\Admin\PromoCodeAdminController;
use App\Http\Controllers\Admin\RefundAdminController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\TipsAdminController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Marketplace\AuthorAnalyticsController;
use App\Http\Controllers\Marketplace\AuthorPromoCodeController;
use App\Http\Controllers\Marketplace\AuthorContactController;
use App\Http\Controllers\Marketplace\AuthorController;
use App\Http\Controllers\Marketplace\AuthorFollowController;
use App\Http\Controllers\Marketplace\BalanceController;
use App\Http\Controllers\Marketplace\BlogController;
use App\Http\Controllers\Marketplace\BlogFeedController;
use App\Http\Controllers\Marketplace\BlogSitemapController;
use App\Http\Controllers\Marketplace\BlogSubscriptionController;
use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Marketplace\AdClickController;
use App\Http\Controllers\Marketplace\BulkDownloadController;
use App\Http\Controllers\Marketplace\BundleController;
use App\Http\Controllers\Marketplace\CheckoutController;
use App\Http\Controllers\Marketplace\CompareController;
use App\Http\Controllers\Marketplace\CustomOrderController;
use App\Http\Controllers\Marketplace\DeliveryLookupController;
use App\Http\Controllers\Marketplace\DiditWebhookController;
use App\Http\Controllers\Marketplace\KycController;
use App\Http\Controllers\Marketplace\LeaderboardController;
use App\Http\Controllers\Marketplace\MakesGalleryController;
use App\Http\Controllers\Marketplace\PrintChallengeController;
use App\Http\Controllers\Marketplace\ReferralController;
use App\Http\Controllers\Marketplace\SearchController;
use App\Http\Controllers\Marketplace\CommentLikeController;
use App\Http\Controllers\Marketplace\CommentController;
use App\Http\Controllers\Marketplace\DashboardController;
use App\Http\Controllers\Marketplace\DownloadController;
use App\Http\Controllers\Marketplace\LibraryController;
use App\Http\Controllers\Marketplace\DownloadOptionsController;
use App\Http\Controllers\Marketplace\HomeController;
use App\Http\Controllers\Marketplace\MakeController;
use App\Http\Controllers\Marketplace\NotificationController;
use App\Http\Controllers\Marketplace\PaymentWebhookController;
use App\Http\Controllers\Marketplace\PayoutController;
use App\Http\Controllers\Marketplace\PrinterProfileController;
use App\Http\Controllers\Marketplace\PrintProfileDownloadController;
use App\Http\Controllers\Marketplace\ProductController;
use App\Http\Controllers\Marketplace\PromoCodeController;
use App\Http\Controllers\Marketplace\RefundRequestController;
use App\Http\Controllers\Marketplace\ReportController;
use App\Http\Controllers\Marketplace\ReviewController;
use App\Http\Controllers\Marketplace\SavedSearchController;
use App\Http\Controllers\Marketplace\SitemapController;
use App\Http\Controllers\Marketplace\TipController;
use App\Http\Controllers\Marketplace\TipPaymentWebhookController;
use App\Http\Controllers\Marketplace\TipSuccessController;
use App\Http\Controllers\Marketplace\WishlistController;
use App\Http\Controllers\NewsletterController as PublicNewsletterController;
use App\Http\Controllers\OgImageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/og-image.png', OgImageController::class)->name('og.image');
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/sitemap-models.xml', [SitemapController::class, 'models'])->name('sitemap.models');
Route::get('/sitemap-categories.xml', [SitemapController::class, 'categories'])->name('sitemap.categories');
Route::get('/sitemap-authors.xml', [SitemapController::class, 'authors'])->name('sitemap.authors');
Route::get('/sitemap-blog.xml', BlogSitemapController::class)->name('sitemap.blog');
Route::get('/feed.xml', BlogFeedController::class)->name('feed');
Route::redirect('/catalog', '/models')->name('catalog');
Route::get('/models', [ProductController::class, 'index'])->name('products.index');
Route::get('/bundles/{bundle:slug}', [BundleController::class, 'show'])->name('bundles.show');
Route::get('/makes', MakesGalleryController::class)->name('makes.gallery');
Route::get('/ads/{ad}/click', [AdClickController::class, 'click'])->name('ads.click');
Route::post('/ads/{ad}/impression', [AdClickController::class, 'impression'])->name('ads.impression');
Route::get('/search', SearchController::class)->name('search');
Route::get('/compare', [CompareController::class, 'show'])->name('compare');
Route::get('/leaderboard', LeaderboardController::class)->name('leaderboard');
Route::get('/challenges', [PrintChallengeController::class, 'index'])->name('challenges.index');
Route::get('/challenges/{challenge:slug}', [PrintChallengeController::class, 'show'])->name('challenges.show');
Route::get('/categories/{category:slug}', [ProductController::class, 'index'])->name('categories.show');
Route::get('/models/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/models/{product:slug}/embed', [ProductController::class, 'embed'])->name('products.embed');
// Tip shortcut URL (bookmark/share): works for guests — redirects to product page where the tip form lives.
Route::get('/models/{product:slug}/tip', [TipController::class, 'redirect'])->name('products.tip.redirect');

// Public report submit (anonymous allowed) — auth optional, captured if present.
Route::post('/models/{product:slug}/report', [ReportController::class, 'store'])->middleware('throttle:5,10')->name('products.report');

// Public authors directory and profiles.
Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
Route::get('/authors/{user}', [AuthorController::class, 'show'])->name('authors.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::post('/blog/subscribe', [BlogSubscriptionController::class, 'store'])->middleware('throttle:6,1')->name('blog.subscribe');
Route::get('/blog/unsubscribe/{token}', [BlogSubscriptionController::class, 'unsubscribe'])->name('blog.unsubscribe');
Route::get('/blog/category/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{tag:slug}', [BlogController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

// CMS legal / informational pages (footer links).
Route::get('/page/{slug}', [PageController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('pages.show');

Route::match(['get', 'post'], '/payments/aifo/webhook', PaymentWebhookController::class)->name('payments.aifo.webhook');
Route::match(['get', 'post'], '/payments/aifo/tips/webhook', TipPaymentWebhookController::class)->name('payments.aifo.tips.webhook');
Route::match(['get', 'post'], '/webhooks/didit', DiditWebhookController::class)->name('webhooks.didit');

// Signed download for slicer custom-protocol opens. Auth is via short-lived URL signature
// (5 min) that is generated server-side only after MarketplaceAccess passes.
Route::get('/models/{product:slug}/d/{file}', [DownloadController::class, 'signed'])
    ->name('products.download.signed')
    ->middleware('signed');
Route::get('/auth/github/redirect', [SocialAuthController::class, 'githubRedirect'])->name('auth.github.redirect');
Route::get('/auth/github/callback', [SocialAuthController::class, 'githubCallback'])->name('auth.github.callback');
Route::match(['get', 'post'], '/auth/telegram', [SocialAuthController::class, 'telegram'])->name('auth.telegram');

Route::get('/dashboard', DashboardController::class)->middleware(['auth'])->name('dashboard');

// Two-factor challenge (post-password) — must be reachable while NOT yet authenticated.
Route::get('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->middleware('guest')->name('two-factor.challenge');
Route::post('/two-factor/challenge', [TwoFactorController::class, 'challengeSubmit'])->middleware('guest')->name('two-factor.challenge.submit');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/checkout/{product:slug}', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    Route::get('/checkout/{order}/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/{order}/failed', [CheckoutController::class, 'failed'])->name('checkout.failed');
    Route::post('/checkout/{order}/demo-confirm', [CheckoutController::class, 'demoConfirm'])->name('checkout.demo-confirm');
    Route::get('/models/{product:slug}/download/{file}', DownloadController::class)->name('products.download');
    Route::get('/models/{product:slug}/download-options', [DownloadOptionsController::class, 'show'])->name('products.download-options');
    Route::post('/models/{product:slug}/download-options/slicer-log', [DownloadOptionsController::class, 'logSlicer'])->name('products.download-options.slicer-log');

    // Engagement: comments + makes (photos of prints)
    Route::post('/models/{product:slug}/comments', [CommentController::class, 'store'])->name('products.comments.store');
    Route::delete('/models/{product:slug}/comments/{comment}', [CommentController::class, 'destroy'])->name('products.comments.destroy');

    Route::post('/models/{product:slug}/makes', [MakeController::class, 'store'])->name('products.makes.store');
    Route::delete('/models/{product:slug}/makes/{make}', [MakeController::class, 'destroy'])->name('products.makes.destroy');
    Route::patch('/models/{product:slug}/makes/{make}/moderate', [MakeController::class, 'moderate'])->name('products.makes.moderate');

    // Reviews (5-star)
    Route::post('/models/{product:slug}/reviews', [ReviewController::class, 'store'])->name('products.reviews.store');
    Route::delete('/models/{product:slug}/reviews/{review}', [ReviewController::class, 'destroy'])->name('products.reviews.destroy');

    // Wishlist
    Route::post('/wishlist/{product:slug}/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');

    // Notifications inbox
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}', [NotificationController::class, 'read'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Author payouts
    Route::get('/author/payouts', [PayoutController::class, 'index'])->name('author.payouts');
    Route::post('/author/payouts', [PayoutController::class, 'store'])->name('author.payouts.store');
    Route::get('/kyc', [KycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc/start', [KycController::class, 'start'])->middleware('throttle:3,10')->name('kyc.start');
    Route::get('/kyc/return', [KycController::class, 'returned'])->name('kyc.return');
    Route::post('/kyc/refresh', [KycController::class, 'refresh'])->middleware('throttle:10,1')->name('kyc.refresh');

    // Author analytics dashboard
    Route::get('/author/analytics', [AuthorAnalyticsController::class, 'index'])->name('author.analytics');

    // Author-owned promo codes (apply to the author's own models; author funds the discount)
    Route::get('/author/promo-codes', [AuthorPromoCodeController::class, 'index'])->name('author.promo-codes');
    Route::post('/author/promo-codes', [AuthorPromoCodeController::class, 'store'])->middleware('throttle:20,1')->name('author.promo-codes.store');
    Route::patch('/author/promo-codes/{promoCode}/toggle', [AuthorPromoCodeController::class, 'toggle'])->name('author.promo-codes.toggle');
    Route::delete('/author/promo-codes/{promoCode}', [AuthorPromoCodeController::class, 'destroy'])->name('author.promo-codes.destroy');

    // Printer profiles (user's own)
    Route::get('/profile/printers', [PrinterProfileController::class, 'index'])->name('printers.index');
    Route::post('/profile/printers', [PrinterProfileController::class, 'store'])->name('printers.store');
    Route::patch('/profile/printers/{printer}', [PrinterProfileController::class, 'update'])->name('printers.update');
    Route::delete('/profile/printers/{printer}', [PrinterProfileController::class, 'destroy'])->name('printers.destroy');
    Route::post('/profile/printers/{printer}/default', [PrinterProfileController::class, 'makeDefault'])->name('printers.default');

    // Print profile download (signed when needed; here we authorize via access guard)
    Route::get('/models/{product:slug}/print-profile', PrintProfileDownloadController::class)->name('products.print-profile.download');

    // Saved searches
    Route::get('/saved-searches', [SavedSearchController::class, 'index'])->name('saved-searches.index');
    Route::post('/saved-searches', [SavedSearchController::class, 'store'])->name('saved-searches.store');
    Route::delete('/saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');

    // Two-factor settings
    Route::get('/profile/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/profile/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::delete('/profile/two-factor', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
    Route::post('/profile/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecovery'])->name('two-factor.recovery');

    // Promo codes (apply at product page before checkout)
    Route::post('/models/{product:slug}/promo', [PromoCodeController::class, 'apply'])->name('products.promo.apply');

    // Tips (donate to author for any model, free or paid); POST requires login.
    Route::post('/models/{product:slug}/tip', [TipController::class, 'store'])->name('products.tip');
    Route::get('/tips/{tip}/success', TipSuccessController::class)->name('tips.success');

    // Download library — all purchased models in one place
    Route::get('/my/library', LibraryController::class)->name('library');
    Route::get('/profile/referrals', ReferralController::class)->name('referral');

    // Challenges
    Route::post('/challenges/{challenge:slug}/enter', [PrintChallengeController::class, 'enter'])->name('challenges.enter');
    Route::post('/challenges/entries/{entry}/vote', [PrintChallengeController::class, 'vote'])->name('challenges.vote');

    // Comment likes
    Route::post('/comments/{comment}/like', [CommentLikeController::class, 'toggle'])->name('comments.like');
    Route::get('/my/library/download-all', [BulkDownloadController::class, 'library'])->name('library.download-all');
    Route::get('/models/{product:slug}/download-all', [BulkDownloadController::class, 'product'])->name('products.download-all');

    // Bundle checkout
    Route::post('/bundles/{bundle:slug}/checkout', [BundleController::class, 'checkout'])->middleware('throttle:10,1')->name('bundles.checkout');

    // Refund requests
    Route::get('/refunds', [RefundRequestController::class, 'index'])->name('refunds.index');
    Route::post('/orders/{order}/refund', [RefundRequestController::class, 'store'])->name('refunds.store');

    // Account balance from refunds and checkout credits.
    Route::get('/balance', BalanceController::class)->name('balance.index');

    // Custom author services: model creation, print jobs, escrow workflow and protected chat.
    Route::get('/custom-orders', [CustomOrderController::class, 'index'])->name('custom-orders.index');
    Route::get('/custom-orders/create', [CustomOrderController::class, 'create'])->name('custom-orders.create');
    Route::post('/custom-orders', [CustomOrderController::class, 'store'])->name('custom-orders.store');
    Route::get('/custom-orders/{customOrder}', [CustomOrderController::class, 'show'])->name('custom-orders.show');
    Route::post('/custom-orders/{customOrder}/messages', [CustomOrderController::class, 'message'])->name('custom-orders.messages.store');
    Route::post('/custom-orders/{customOrder}/offer', [CustomOrderController::class, 'offer'])->name('custom-orders.offer');
    Route::post('/custom-orders/{customOrder}/delivery', [CustomOrderController::class, 'delivery'])->name('custom-orders.delivery');
    Route::post('/custom-orders/{customOrder}/accept', [CustomOrderController::class, 'accept'])->name('custom-orders.accept');
    Route::get('/custom-orders/{customOrder}/pay', [CustomOrderController::class, 'payRedirect'])->name('custom-orders.pay.redirect');
    Route::post('/custom-orders/{customOrder}/pay', [CustomOrderController::class, 'pay'])->name('custom-orders.pay');
    Route::post('/custom-orders/{customOrder}/demo-pay', [CustomOrderController::class, 'demoPay'])->name('custom-orders.demo-pay');
    Route::post('/custom-orders/{customOrder}/cancel', [CustomOrderController::class, 'cancel'])->name('custom-orders.cancel');
    Route::get('/custom-orders/{customOrder}/messages', [CustomOrderController::class, 'messages'])->name('custom-orders.messages.index');
    Route::post('/custom-orders/{customOrder}/result', [CustomOrderController::class, 'result'])->name('custom-orders.result');
    Route::get('/custom-orders/{customOrder}/files/{file}/download', [CustomOrderController::class, 'downloadFile'])->name('custom-orders.files.download');
    Route::post('/custom-orders/{customOrder}/ship', [CustomOrderController::class, 'ship'])->name('custom-orders.ship');
    Route::post('/custom-orders/{customOrder}/complete', [CustomOrderController::class, 'complete'])->name('custom-orders.complete');
    Route::post('/custom-orders/{customOrder}/dispute', [CustomOrderController::class, 'dispute'])->name('custom-orders.dispute');
    Route::get('/delivery/cities', [DeliveryLookupController::class, 'cities'])->name('delivery.cities');
    Route::get('/delivery/warehouses', [DeliveryLookupController::class, 'warehouses'])->name('delivery.warehouses');

    // Author follow / unfollow / contact.
    Route::post('/authors/{user}/follow', [AuthorFollowController::class, 'store'])->name('authors.follow');
    Route::delete('/authors/{user}/follow', [AuthorFollowController::class, 'destroy'])->name('authors.unfollow');
    Route::post('/authors/{user}/contact', [AuthorContactController::class, 'store'])->name('authors.contact');

    Route::get('/author/products', [ProductController::class, 'myProducts'])->name('author.products.index');
    Route::get('/author/products/create', [ProductController::class, 'create'])->name('author.products.create');
    Route::post('/author/products', [ProductController::class, 'store'])->name('author.products.store');
    Route::get('/author/products/{product}/edit', [ProductController::class, 'edit'])->name('author.products.edit');
    Route::patch('/author/products/{product}', [ProductController::class, 'update'])->name('author.products.update');
    Route::delete('/author/products/{product}/files/{file}', [ProductController::class, 'destroyFile'])->name('author.products.files.destroy');
});

// Newsletter (public)
Route::post('/newsletter/subscribe', [PublicNewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [PublicNewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::middleware(['auth', 'role:admin,moderator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{user}/reset-password', [AdminController::class, 'resetUserPassword'])->name('users.reset-password');
    Route::post('/users/{user}/toggle-verification', [AdminController::class, 'toggleUserVerification'])->name('users.toggle-verification');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::patch('/products/{product}/moderate', [AdminController::class, 'moderate'])->name('products.moderate');
    Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
    Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
    Route::get('/custom-orders', [CustomOrderAdminController::class, 'index'])->name('custom-orders.index');
    Route::get('/custom-orders/{customOrder}', [CustomOrderAdminController::class, 'show'])->name('custom-orders.show');
    Route::patch('/custom-orders/{customOrder}', [CustomOrderAdminController::class, 'update'])->name('custom-orders.update');
    Route::post('/custom-orders/{customOrder}/track', [CustomOrderAdminController::class, 'track'])->name('custom-orders.track');
    Route::post('/custom-orders/{customOrder}/resolve-dispute', [CustomOrderAdminController::class, 'resolveDispute'])->name('custom-orders.resolve-dispute');

    Route::middleware('role:admin')->group(function () {
        Route::get('/blog', [AdminBlogPostController::class, 'index'])->name('blog.index');
        Route::get('/blog/create', [AdminBlogPostController::class, 'create'])->name('blog.create');
        Route::post('/blog', [AdminBlogPostController::class, 'store'])->name('blog.store');
        Route::get('/blog/{post}/edit', [AdminBlogPostController::class, 'edit'])->name('blog.edit');
        Route::put('/blog/{post}', [AdminBlogPostController::class, 'update'])->name('blog.update');
        Route::delete('/blog/{post}', [AdminBlogPostController::class, 'destroy'])->name('blog.destroy');
        Route::post('/blog/upload', [AdminBlogPostController::class, 'upload'])->name('blog.upload');
        Route::get('/blog/categories', [BlogTaxonomyController::class, 'categories'])->name('blog.categories');
        Route::post('/blog/categories', [BlogTaxonomyController::class, 'storeCategory'])->name('blog.categories.store');
        Route::patch('/blog/categories/{category}', [BlogTaxonomyController::class, 'updateCategory'])->name('blog.categories.update');
        Route::delete('/blog/categories/{category}', [BlogTaxonomyController::class, 'destroyCategory'])->name('blog.categories.destroy');
        Route::get('/blog/tags', [BlogTaxonomyController::class, 'tags'])->name('blog.tags');
        Route::post('/blog/tags', [BlogTaxonomyController::class, 'storeTag'])->name('blog.tags.store');
        Route::patch('/blog/tags/{tag}', [BlogTaxonomyController::class, 'updateTag'])->name('blog.tags.update');
        Route::delete('/blog/tags/{tag}', [BlogTaxonomyController::class, 'destroyTag'])->name('blog.tags.destroy');
    });

    Route::get('/payouts', [PayoutAdminController::class, 'index'])->name('payouts');
    Route::patch('/payouts/{payout}', [PayoutAdminController::class, 'update'])->name('payouts.update');
    Route::get('/kyc', [KycAdminController::class, 'index'])->name('kyc.index');
    Route::post('/kyc/{verification}/sync', [KycAdminController::class, 'sync'])->name('kyc.sync');
    Route::patch('/kyc/{verification}/status', [KycAdminController::class, 'updateStatus'])->name('kyc.status');

    Route::get('/promo-codes', [PromoCodeAdminController::class, 'index'])->name('promo-codes');
    Route::post('/promo-codes', [PromoCodeAdminController::class, 'store'])->name('promo-codes.store');
    Route::patch('/promo-codes/{promoCode}', [PromoCodeAdminController::class, 'update'])->name('promo-codes.update');
    Route::delete('/promo-codes/{promoCode}', [PromoCodeAdminController::class, 'destroy'])->name('promo-codes.destroy');

    Route::get('/refunds', [RefundAdminController::class, 'index'])->name('refunds');
    Route::patch('/refunds/{refundRequest}', [RefundAdminController::class, 'update'])->name('refunds.update');

    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit');

    Route::redirect('/taxonomy', '/admin/categories')->name('taxonomy');

    Route::get('/categories', [AdminController::class, 'categories'])->name('categories');
    Route::post('/categories', [AdminController::class, 'storeCategory'])->name('categories.store');
    Route::patch('/categories/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminController::class, 'destroyCategory'])->name('categories.destroy');

    Route::get('/tags', [AdminController::class, 'tags'])->name('tags');
    Route::post('/tags', [AdminController::class, 'storeTag'])->name('tags.store');
    Route::patch('/tags/{tag}', [AdminController::class, 'updateTag'])->name('tags.update');
    Route::delete('/tags/{tag}', [AdminController::class, 'destroyTag'])->name('tags.destroy');

    Route::get('/licenses', [AdminController::class, 'licenses'])->name('licenses');
    Route::post('/licenses', [AdminController::class, 'storeLicense'])->name('licenses.store');
    Route::patch('/licenses/{license}', [AdminController::class, 'updateLicense'])->name('licenses.update');
    Route::delete('/licenses/{license}', [AdminController::class, 'destroyLicense'])->name('licenses.destroy');
    Route::get('/content', [ContentController::class, 'edit'])->name('content');
    Route::post('/settings', [ContentController::class, 'setting'])->name('settings.store');
    Route::post('/settings/bulk', [ContentController::class, 'bulkSettings'])->name('settings.bulk');
    Route::post('/settings/asset/delete', [ContentController::class, 'deleteAsset'])->name('settings.asset.delete');
    Route::post('/seo', [ContentController::class, 'seo'])->name('seo.store');
    Route::delete('/seo/{seoPage}', [ContentController::class, 'deleteSeo'])->name('seo.destroy');
    Route::post('/translations', [ContentController::class, 'translation'])->name('translations.store');
    Route::delete('/translations/{translation}', [ContentController::class, 'deleteTranslation'])->name('translations.destroy');
    Route::post('/email-templates', [ContentController::class, 'email'])->name('email-templates.store');
    Route::delete('/email-templates/{emailTemplate}', [ContentController::class, 'deleteEmail'])->name('email-templates.destroy');
    Route::post('/mail/test', [ContentController::class, 'sendTestEmail'])->name('mail.test');

    // Legal / footer CMS pages
    Route::get('/pages/create', [LegalPagesController::class, 'create'])->name('pages.create');
    Route::post('/pages', [LegalPagesController::class, 'store'])->name('pages.store');
    Route::get('/pages/{page}/edit', [LegalPagesController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{page}', [LegalPagesController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{page}', [LegalPagesController::class, 'destroy'])->name('pages.destroy');
    Route::patch('/pages/{page}/toggle', [LegalPagesController::class, 'toggle'])->name('pages.toggle');

    // Moderation hub + queues
    Route::get('/moderation', [ModerationController::class, 'hub'])->name('moderation.hub');
    Route::get('/moderation/reports', [ModerationController::class, 'reports'])->name('moderation.reports');
    Route::patch('/moderation/reports/{report}', [ModerationController::class, 'updateReport'])->name('moderation.reports.update');
    Route::get('/moderation/reviews', [ModerationController::class, 'reviews'])->name('moderation.reviews');
    Route::patch('/moderation/reviews/{review}', [ModerationController::class, 'updateReview'])->name('moderation.reviews.update');
    Route::delete('/moderation/reviews/{review}', [ModerationController::class, 'destroyReview'])->name('moderation.reviews.destroy');
    Route::get('/moderation/comments', [ModerationController::class, 'comments'])->name('moderation.comments');
    Route::patch('/moderation/comments/{comment}', [ModerationController::class, 'updateComment'])->name('moderation.comments.update');
    Route::delete('/moderation/comments/{comment}', [ModerationController::class, 'destroyComment'])->name('moderation.comments.destroy');
    Route::get('/moderation/makes', [ModerationController::class, 'makes'])->name('moderation.makes');
    Route::patch('/moderation/makes/{make}', [ModerationController::class, 'updateMake'])->name('moderation.makes.update');
    Route::delete('/moderation/makes/{make}', [ModerationController::class, 'destroyMake'])->name('moderation.makes.destroy');

    // Bulk actions
    Route::post('/bulk/users', [BulkActionController::class, 'users'])->name('bulk.users');
    Route::post('/bulk/products', [BulkActionController::class, 'products'])->name('bulk.products');

    // CSV export
    Route::get('/export/users', [ExportController::class, 'users'])->name('export.users');
    Route::get('/export/orders', [ExportController::class, 'orders'])->name('export.orders');
    Route::get('/export/payments', [ExportController::class, 'payments'])->name('export.payments');
    Route::get('/export/payouts', [ExportController::class, 'payouts'])->name('export.payouts');

    // System tools
    Route::get('/system', [SystemController::class, 'index'])->name('system');
    Route::post('/system/maintenance', [SystemController::class, 'toggleMaintenance'])->name('system.maintenance');
    Route::post('/system/cache', [SystemController::class, 'clearCache'])->name('system.cache');
    Route::get('/system/failed-jobs', [SystemController::class, 'failedJobs'])->name('system.failed-jobs');
    Route::post('/system/queue/retry-all', [SystemController::class, 'retryJob'])->name('system.queue.retry-all');
    Route::post('/system/queue/retry/{id}', [SystemController::class, 'retryJob'])->name('system.queue.retry');
    Route::post('/system/queue/delete/{id}', [SystemController::class, 'deleteJob'])->name('system.queue.delete');
    Route::post('/system/queue/flush', [SystemController::class, 'flushFailed'])->name('system.queue.flush');
    Route::get('/system/logs', [SystemController::class, 'logs'])->name('system.logs');

    // Featured manager
    Route::get('/products/featured', [FeaturedProductsController::class, 'index'])->name('products.featured');
    Route::patch('/products/{product}/toggle-featured', [FeaturedProductsController::class, 'toggle'])->name('products.toggle-featured');
    Route::post('/products/featured/reorder', [FeaturedProductsController::class, 'reorder'])->name('products.featured.reorder');

    // Tips
    Route::get('/tips', [TipsAdminController::class, 'index'])->name('tips');

    // Manual verification override
    Route::patch('/users/{user}/toggle-manual-verification', [AdminController::class, 'toggleManualVerification'])->name('users.toggle-manual-verification');

    // Announcements
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::patch('/announcements/{announcement}/toggle', [AnnouncementController::class, 'toggle'])->name('announcements.toggle');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // Newsletter (admin)
    Route::get('/newsletter', [AdminNewsletterController::class, 'index'])->name('newsletter');
    Route::get('/newsletter/blast', [AdminNewsletterController::class, 'blastForm'])->name('newsletter.blast.form');
    Route::delete('/newsletter/subscribers/{subscriber}', [AdminNewsletterController::class, 'destroy'])->name('newsletter.destroy');
    Route::post('/newsletter/blast', [AdminNewsletterController::class, 'blast'])->name('newsletter.blast');
    Route::get('/newsletter/template/{key}', [AdminNewsletterController::class, 'template'])->name('newsletter.template');
    Route::post('/newsletter/preview', [AdminNewsletterController::class, 'preview'])->name('newsletter.preview');

    // Analytics
    Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics');

    // Advertisements
    Route::get('/ads', [AdvertisementController::class, 'index'])->name('ads.index');
    Route::get('/ads/create', [AdvertisementController::class, 'create'])->name('ads.create');
    Route::post('/ads', [AdvertisementController::class, 'store'])->name('ads.store');
    Route::get('/ads/{ad}/edit', [AdvertisementController::class, 'edit'])->name('ads.edit');
    Route::put('/ads/{ad}', [AdvertisementController::class, 'update'])->name('ads.update');
    Route::delete('/ads/{ad}', [AdvertisementController::class, 'destroy'])->name('ads.destroy');
    Route::patch('/ads/{ad}/toggle', [AdvertisementController::class, 'toggle'])->name('ads.toggle');

    // Print Challenges
    Route::get('/challenges', [PrintChallengeAdminController::class, 'index'])->name('challenges.index');
    Route::get('/challenges/create', [PrintChallengeAdminController::class, 'create'])->name('challenges.create');
    Route::post('/challenges', [PrintChallengeAdminController::class, 'store'])->name('challenges.store');
    Route::get('/challenges/{challenge}/edit', [PrintChallengeAdminController::class, 'edit'])->name('challenges.edit');
    Route::put('/challenges/{challenge}', [PrintChallengeAdminController::class, 'update'])->name('challenges.update');
    Route::patch('/challenges/entries/{entry}/moderate', [PrintChallengeAdminController::class, 'moderateEntry'])->name('challenges.entries.moderate');

    // Bundles
    Route::get('/bundles', [BundleAdminController::class, 'index'])->name('bundles.index');
    Route::get('/bundles/create', [BundleAdminController::class, 'create'])->name('bundles.create');
    Route::post('/bundles', [BundleAdminController::class, 'store'])->name('bundles.store');
    Route::get('/bundles/{bundle}/edit', [BundleAdminController::class, 'edit'])->name('bundles.edit');
    Route::put('/bundles/{bundle}', [BundleAdminController::class, 'update'])->name('bundles.update');
    Route::delete('/bundles/{bundle}', [BundleAdminController::class, 'destroy'])->name('bundles.destroy');

    // API tokens
    Route::get('/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens');
    Route::post('/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
});

require __DIR__.'/auth.php';
