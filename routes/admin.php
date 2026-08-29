<?php

use App\Http\Controllers\Admin\AdsController;
use App\Http\Controllers\Admin\BusinessAdminController;
use App\Http\Controllers\Admin\CollectorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DuplicateAdminController;
use App\Http\Controllers\Admin\GeographyController;
use App\Http\Controllers\Admin\PlaceAdminController;
use App\Http\Controllers\Admin\AuditAdminController;
use App\Http\Controllers\Admin\SeoAdminController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\VideoAdminController;
use Illuminate\Support\Facades\Route;

/*
 * The administration is deliberately outside the /{locale} prefix: it is one
 * back office, not six. Every route sits behind auth + role + 2FA, and every
 * write is audited.
 */
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'role:moderator,admin,super_admin', 'require.2fa', 'security.block', 'security.log', 'audit.admin'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        /* Videos ------------------------------------------------------- */
        Route::get('/videos', [VideoAdminController::class, 'index'])->name('videos.index');
        // Before /videos/{video}, or "duplicates" would be read as an id.
        Route::get('/videos/duplicates', [DuplicateAdminController::class, 'index'])->name('videos.duplicates');
        Route::post('/videos/duplicates', [DuplicateAdminController::class, 'resolve'])->name('videos.duplicates.resolve');
        Route::post('/videos/bulk', [VideoAdminController::class, 'bulk'])->name('videos.bulk');
        Route::get('/videos/{video:id}/edit', [VideoAdminController::class, 'edit'])->name('videos.edit');
        Route::put('/videos/{video:id}', [VideoAdminController::class, 'update'])->name('videos.update');
        Route::post('/videos/{video:id}/approve', [VideoAdminController::class, 'approve'])->name('videos.approve');
        Route::post('/videos/{video:id}/reject', [VideoAdminController::class, 'reject'])->name('videos.reject');
        Route::post('/videos/{video:id}/places', [VideoAdminController::class, 'attachPlace'])->name('videos.places.attach');
        Route::delete('/videos/{video:id}/places/{place:id}', [VideoAdminController::class, 'detachPlace'])->name('videos.places.detach');

        /* Places ------------------------------------------------------- */
        Route::get('/places', [PlaceAdminController::class, 'index'])->name('places.index');
        Route::get('/places/new', [PlaceAdminController::class, 'create'])->name('places.create');
        Route::post('/places', [PlaceAdminController::class, 'store'])->name('places.store');
        Route::get('/places/{place:id}/edit', [PlaceAdminController::class, 'edit'])->name('places.edit');
        Route::put('/places/{place:id}', [PlaceAdminController::class, 'update'])->name('places.update');
        Route::post('/places/{place:id}/approve', [PlaceAdminController::class, 'approve'])->name('places.approve');
        Route::post('/places/{place:id}/reject', [PlaceAdminController::class, 'reject'])->name('places.reject');

        /* Geography and taxonomy --------------------------------------- */
        Route::get('/countries', [GeographyController::class, 'countries'])->name('countries.index');
        Route::post('/countries', [GeographyController::class, 'storeCountry'])->name('countries.store');
        Route::put('/countries/{country:id}', [GeographyController::class, 'updateCountry'])->name('countries.update');
        Route::get('/countries/{country:id}/cities', [GeographyController::class, 'citiesFor'])->name('countries.cities');

        Route::get('/cities', [GeographyController::class, 'cities'])->name('cities.index');
        Route::post('/cities', [GeographyController::class, 'storeCity'])->name('cities.store');
        Route::put('/cities/{city:id}', [GeographyController::class, 'updateCity'])->name('cities.update');

        Route::get('/categories', [GeographyController::class, 'categories'])->name('categories.index');
        Route::put('/categories/{category:id}', [GeographyController::class, 'updateCategory'])->name('categories.update');

        /* Video collector ---------------------------------------------- */
        Route::get('/collector', [CollectorController::class, 'index'])->name('collector.index');
        Route::post('/collector', [CollectorController::class, 'store'])->name('collector.store');
        Route::post('/collector/generate', [CollectorController::class, 'generate'])->name('collector.generate');
        Route::put('/collector/{query:id}', [CollectorController::class, 'update'])->name('collector.update');
        Route::delete('/collector/{query:id}', [CollectorController::class, 'destroy'])->name('collector.destroy');
        Route::post('/collector/{query:id}/run', [CollectorController::class, 'run'])->name('collector.run');

        /* Ads manager --------------------------------------------------- */
        // SEO metadata per page and per language. Bound by id like every other
        // admin route: the public site binds models by slug.
        // Read-only: an audit log an administrator can edit is not an audit log.
        Route::get('/audit', [AuditAdminController::class, 'index'])->name('audit.index');

        Route::get('/seo', [SeoAdminController::class, 'index'])->name('seo.index');
        Route::post('/seo', [SeoAdminController::class, 'store'])->name('seo.store');
        Route::put('/seo/{seo:id}', [SeoAdminController::class, 'update'])->name('seo.update');
        Route::delete('/seo/{seo:id}', [SeoAdminController::class, 'destroy'])->name('seo.destroy');

        Route::get('/ads', [AdsController::class, 'index'])->name('ads.index');
        Route::post('/ads', [AdsController::class, 'store'])->name('ads.store');
        Route::put('/ads/{ad:id}', [AdsController::class, 'update'])->name('ads.update');
        Route::delete('/ads/{ad:id}', [AdsController::class, 'destroy'])->name('ads.destroy');
        Route::post('/announcements', [AdsController::class, 'storeAnnouncement'])->name('announcements.store');
        Route::put('/announcements/{announcement:id}', [AdsController::class, 'updateAnnouncement'])->name('announcements.update');
        Route::delete('/announcements/{announcement:id}', [AdsController::class, 'destroyAnnouncement'])->name('announcements.destroy');

        /* Users and settings -------------------------------------------- */
        Route::get('/businesses', [BusinessAdminController::class, 'index'])->name('businesses.index');

        Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
        // Bound by id rather than by model so a suspended (soft-deleted)
        // account can still be opened and restored.
        Route::get('/users/{id}', [UserAdminController::class, 'show'])->name('users.show');
        Route::put('/users/{user:id}', [UserAdminController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserAdminController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/restore', [UserAdminController::class, 'restore'])->name('users.restore');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/releases', [SettingsController::class, 'storeRelease'])->name('settings.releases.store');

        /* Keys and security switches, editable without touching the .env ---- */
        Route::get('/system', [SystemController::class, 'index'])->name('system.index');
        Route::put('/system', [SystemController::class, 'update'])->name('system.update');
        Route::post('/system/test/youtube', [SystemController::class, 'testYoutube'])->name('system.test.youtube');
        Route::post('/system/test/turnstile', [SystemController::class, 'testTurnstile'])->name('system.test.turnstile');
        Route::post('/system/test/mail', [SystemController::class, 'testMail'])->name('system.test.mail');
    });
