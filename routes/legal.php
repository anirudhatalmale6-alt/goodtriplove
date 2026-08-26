<?php

use App\Http\Controllers\ContentNoticeController;
use Illuminate\Support\Facades\Route;

/*
 * Content notice endpoint from the legal module.
 *
 * 'web' added deliberately: as shipped, this route had no middleware group at
 * all, which meant no CSRF token was required on a public POST and the
 * confirmation flash had no session to live in.
 */
Route::post('/report-content', [ContentNoticeController::class, 'store'])
    ->middleware(['web', 'throttle:10,1'])
    ->name('content.report');
