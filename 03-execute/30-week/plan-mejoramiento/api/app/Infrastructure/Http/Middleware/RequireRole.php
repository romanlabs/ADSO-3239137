<?php

namespace App\Infrastructure\Http\Middleware;

use App\Domain\Exceptions\ForbiddenRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contract §4: authorization by role on every endpoint (`role:admin` or `role:admin,assistant`). It runs before the
 * controller, so a 403 never reveals whether the resource exists.
 */
final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! in_array($request->attributes->get(Authenticate::ROLE), $roles, true)) {
            throw ForbiddenRole::create();
        }

        return $next($request);
    }
}
