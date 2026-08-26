<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias: audit.admin — writes an audit entry for every state-changing action
 * performed in the administration. Read requests are ignored; the per-field
 * old/new comparison is done in the controllers that edit a model.
 */
class AuditAdminAction
{
    public function __construct(private AuditService $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethodSafe()) {
            $this->audit->record(
                action: $request->route()?->getName() ?? $request->path(),
                success: $response->getStatusCode() < 400,
                request: $request,
            );
        }

        return $response;
    }
}
