<?php

use App\Http\Controllers\Admin\ContentNoticeAdminController;
use App\Http\Controllers\Admin\LegalAdminController;
use App\Http\Controllers\Admin\OperationsController;
use Illuminate\Support\Facades\Route;

/*
 * Operations, legal texts and the reporting queue.
 * Adapted from the client module: the original pointed at bare Route::view()
 * pages with no controller behind them.
 */
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'role:moderator,admin,super_admin', 'require.2fa', 'security.block', 'audit.admin'])
    ->group(function () {
        Route::get('/operations/status', [OperationsController::class, 'status'])->name('operations.status');
        Route::get('/operations/youtube-quota', [OperationsController::class, 'youtubeQuota'])->name('operations.youtube-quota');
        Route::get('/operations/features', [OperationsController::class, 'features'])->name('operations.features');
        Route::put('/operations/features', [OperationsController::class, 'updateFeatures'])->name('operations.features.update');
        Route::get('/operations/errors', [OperationsController::class, 'errors'])->name('operations.errors');

        Route::get('/legal', [LegalAdminController::class, 'index'])->name('legal.index');
        Route::get('/legal/{key}/{locale}', [LegalAdminController::class, 'edit'])->name('legal.edit');
        Route::post('/legal/{key}/{locale}', [LegalAdminController::class, 'store'])->name('legal.store');
        Route::post('/legal/document/{document:id}/publish', [LegalAdminController::class, 'publish'])->name('legal.publish');

        Route::get('/notices', [ContentNoticeAdminController::class, 'index'])->name('notices.index');
        Route::get('/notices/{notice:id}', [ContentNoticeAdminController::class, 'show'])->name('notices.show');
        Route::post('/notices/{notice:id}/triage', [ContentNoticeAdminController::class, 'triage'])->name('notices.triage');
        Route::post('/notices/{notice:id}/decide', [ContentNoticeAdminController::class, 'decide'])->name('notices.decide');
        Route::post('/notices/{notice:id}/notified', [ContentNoticeAdminController::class, 'markNotified'])->name('notices.notified');
    });
