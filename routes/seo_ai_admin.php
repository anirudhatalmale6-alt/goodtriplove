<?php

use App\Http\Controllers\Admin\SeoAiAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')
    ->middleware(['web', 'auth', 'role:moderator,admin,super_admin', 'require.2fa', 'security.block', 'security.log', 'audit.admin'])
    ->group(function () {
        Route::get('/seo-ai', [SeoAiAdminController::class, 'index'])->name('seo-ai.index');
        Route::put('/seo-ai/settings', [SeoAiAdminController::class, 'settings'])->name('seo-ai.settings');
        Route::post('/seo-ai/generate', [SeoAiAdminController::class, 'generate'])->name('seo-ai.generate');
        Route::post('/seo-ai/{page:id}/publish', [SeoAiAdminController::class, 'publish'])->name('seo-ai.publish');
        Route::post('/seo-ai/{page:id}/unpublish', [SeoAiAdminController::class, 'unpublish'])->name('seo-ai.unpublish');
    });
