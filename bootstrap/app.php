<?php

use App\Http\Middleware\AuditAdminAction;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureNotSecurityBlocked;
use App\Http\Middleware\LogSecurityAccess;
use App\Http\Middleware\MaintenanceGate;
use App\Http\Middleware\RequireAdminTwoFactor;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\VerifyTurnstile;
use App\Support\LocaleUrl;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            require __DIR__.'/../routes/admin.php';
            require __DIR__.'/../routes/security_center.php';
            require __DIR__.'/../routes/growth_ops.php';
            require __DIR__.'/../routes/legal.php';
            require __DIR__.'/../routes/core_operations.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            EnsureNotSecurityBlocked::class,
            MaintenanceGate::class,
        ]);

        // Authenticate sits in Laravel's priority list and therefore runs
        // BEFORE appended middleware — so the locale URL default may not exist
        // yet when it builds the login redirect. These two closures resolve the
        // language themselves instead of relying on middleware ordering.
        $middleware->redirectGuestsTo(
            fn (Request $request) => route('login', ['locale' => LocaleUrl::resolve($request)])
        );

        $middleware->redirectUsersTo(
            fn (Request $request) => route('home', ['locale' => LocaleUrl::resolve($request)])
        );

        // The consent cookie is read by the front-end to decide whether an
        // embed may be created, so it must not be encrypted — otherwise the
        // banner reappears on every page and consent is never honoured.
        $middleware->encryptCookies(except: [
            \App\Http\Controllers\CookieConsentController::COOKIE,
        ]);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'role' => RequireRole::class,
            'require.2fa' => RequireAdminTwoFactor::class,
            'security.block' => EnsureNotSecurityBlocked::class,
            'security.log' => LogSecurityAccess::class,
            'audit.admin' => AuditAdminAction::class,
            'turnstile' => VerifyTurnstile::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
