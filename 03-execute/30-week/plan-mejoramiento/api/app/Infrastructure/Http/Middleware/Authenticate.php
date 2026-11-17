<?php

namespace App\Infrastructure\Http\Middleware;

use App\Domain\Exceptions\InvalidCredentials;
use App\Infrastructure\Security\JwtTokenIssuer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CA-01.3: every route except login and health needs `Authorization: Bearer <token>`. A missing, forged or expired
 * token is a 401 INVALID_CREDENTIALS. The user id and role travel to the controller as request attributes.
 */
final readonly class Authenticate
{
    public const USER_ID = 'auth_user_id';

    public const ROLE = 'auth_role';

    public function __construct(private JwtTokenIssuer $tokens)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $claims = $token === null || $token === '' ? null : $this->tokens->claimsOf($token);

        if ($claims === null) {
            throw InvalidCredentials::missingToken();
        }

        $request->attributes->set(self::USER_ID, $claims['sub']);
        $request->attributes->set(self::ROLE, $claims['role']);

        return $next($request);
    }
}
