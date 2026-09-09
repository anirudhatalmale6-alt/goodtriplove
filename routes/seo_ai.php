<?php

use App\Http\Controllers\SeoAiPageController;
use App\Http\Controllers\SeoAiSitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap-seo-ai.xml', SeoAiSitemapController::class)->name('seo-ai.sitemap');

Route::prefix('{locale}')->group(function () {
    Route::get('/discover/{slug}', [SeoAiPageController::class, 'show'])->name('seo-ai.show');
});
