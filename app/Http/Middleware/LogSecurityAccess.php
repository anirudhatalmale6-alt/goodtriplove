<?php

namespace App\Http\Middleware;

use App\Services\SecurityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias: security.log — records that an administration area was reached.
 * Only write operations and the entry point are logged, so the security log
 * stays readable instead of filling with GET noise.
 */
class LogSecurityAccess
{
    public function __construct(private SecurityLogger $logger) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            $this->logger->log('admin_write_access', true, 'info', [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
            ]);
        }

        return $next($request);
    }
}
