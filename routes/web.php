<?php

use App\Http\Controllers\AdClickController;
use App\Http\Controllers\AppDownloadController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Business\BusinessPlaceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TvController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::pattern('locale', implode('|', array_keys(config('goodtriplove.locales'))));

// Language-less entry points hand the visitor to their own language.
Route::get('/', fn () => redirect()->route('home', ['locale' => app()->getLocale()]));
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-{section}.xml', [SitemapController::class, 'section'])->name('sitemap.section');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::prefix('{locale}')->group(function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');

    /* ----------------------------------------------------------------- *
     | Discovery
     * ----------------------------------------------------------------- */
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/video/{video}', [VideoController::class, 'show'])->name('video.show');
    Route::post('/video/{video}/play', [VideoController::class, 'play'])->name('video.play');

    Route::get('/tv', [TvController::class, 'index'])->name('tv');
    Route::get('/tv/playlist', [TvController::class, 'playlist'])->name('tv.playlist');

    Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');
    Route::get('/country/{country}', [CountryController::class, 'show'])->name('country.show');
    Route::get('/city/{country}/{city}', [CityController::class, 'show'])
        ->scopeBindings()->name('city.show');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/category/{category}', [CategoryController::class, 'show'])->name('category.show');

    Route::get('/place/{country}/{city}/{place}', [PlaceController::class, 'show'])
        ->scopeBindings()->name('place.show');

    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

    Route::get('/app', [AppDownloadController::class, 'index'])->name('app.download');
    Route::get('/app/android', [AppDownloadController::class, 'android'])->name('app.android');

    Route::get('/go/ad/{ad}', [AdClickController::class, 'redirect'])->name('ad.click');

    /* ----------------------------------------------------------------- *
     | Legal, cookies and content notices
     * ----------------------------------------------------------------- */
    Route::get('/legal', [LegalController::class, 'index'])->name('legal.index');
    Route::get('/legal/report', [LegalController::class, 'reportForm'])->name('legal.report');
    Route::get('/legal/{key}', [LegalController::class, 'show'])->name('legal.show');

    Route::post('/cookies', [CookieConsentController::class, 'store'])->name('cookies.store');
    Route::post('/cookies/withdraw', [CookieConsentController::class, 'withdraw'])->name('cookies.withdraw');

    /* ----------------------------------------------------------------- *
     | Account
     * ----------------------------------------------------------------- */
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])
            ->middleware(['throttle:login', 'turnstile']);

        Route::get('/register', [RegisterController::class, 'create'])->name('register');
        Route::post('/register', [RegisterController::class, 'store'])
            ->middleware(['throttle:register', 'turnstile']);

        Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'email'])
            ->middleware(['throttle:password-reset', 'turnstile'])->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'update'])
            ->middleware('throttle:password-reset')->name('password.update');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

        // Email verification by 6-digit code.
        Route::get('/verify-email', [EmailVerificationController::class, 'show'])->name('verification.notice');
        Route::post('/verify-email', [EmailVerificationController::class, 'verify'])
            ->middleware('throttle:6,1')->name('verification.verify');
        Route::post('/verify-email/resend', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:3,10')->name('verification.resend');

        // Two-factor for staff. Deliberately outside the admin group: the
        // admin middleware redirects here, so it must not guard these routes.
        Route::get('/security/2fa/setup', [TwoFactorController::class, 'setup'])->name('security.2fa.setup');
        Route::post('/security/2fa/setup', [TwoFactorController::class, 'confirm'])
            ->middleware('throttle:6,1')->name('security.2fa.confirm');
        Route::get('/security/2fa', [TwoFactorController::class, 'challenge'])->name('security.2fa.challenge');
        Route::post('/security/2fa', [TwoFactorController::class, 'verify'])
            ->middleware('throttle:6,1')->name('security.2fa.verify');

        Route::post('/favorite', [FavoriteController::class, 'toggle'])->name('favorite.toggle');
        Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites');

        // Free business registration — the listing goes to the moderation queue.
        Route::get('/business', [BusinessPlaceController::class, 'index'])->name('business.index');
        Route::get('/business/new', [BusinessPlaceController::class, 'create'])->name('business.create');
        Route::post('/business', [BusinessPlaceController::class, 'store'])
            ->middleware(['throttle:submission', 'turnstile'])->name('business.store');
        Route::get('/business/{place}/edit', [BusinessPlaceController::class, 'edit'])->name('business.edit');
        Route::put('/business/{place}', [BusinessPlaceController::class, 'update'])->name('business.update');
        Route::post('/business/{place}/video', [BusinessPlaceController::class, 'attachVideo'])
            ->middleware('throttle:submission')->name('business.video');
    });
});
