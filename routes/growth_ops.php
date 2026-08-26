<?php

use App\Http\Controllers\Admin\DataQualityController;
use App\Http\Controllers\Admin\GrowthOpsDashboardController;
use App\Http\Controllers\Admin\ModerationController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web','auth','role:admin,super_admin,moderator','require.2fa','security.block'])
    ->group(function () {
        Route::get('/growth-ops', [GrowthOpsDashboardController::class,'index'])
            ->name('growth-ops');

        Route::get('/data-quality', [DataQualityController::class,'index'])
            ->name('data-quality');
        Route::post('/data-quality/{issue}/resolve', [DataQualityController::class,'resolve'])
            ->name('data-quality.resolve');

        Route::get('/moderation', [ModerationController::class,'index'])
            ->name('moderation');
        Route::post('/moderation/{item}/resolve', [ModerationController::class,'resolve'])
            ->name('moderation.resolve');
    });
