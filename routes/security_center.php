<?php

use App\Http\Controllers\Admin\SecurityCenterController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware([
        'web',
        'auth',
        'role:admin,super_admin',
        'require.2fa',
        'security.block',
        'security.log',
    ])
    ->group(function () {
        Route::get('/security-center', [SecurityCenterController::class,'index'])
            ->name('security-center');
    });
