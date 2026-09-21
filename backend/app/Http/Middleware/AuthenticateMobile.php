<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Sentai\Modules\Identity\Application\Contracts\MobileTokenStore;
use Sentai\Modules\Identity\Application\Exceptions\AuthenticationFailed;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthenticateMobile
{
    public function __construct(private MobileTokenStore $tokens)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            throw new AuthenticationFailed();
        }

        $context = $this->tokens->authenticateAccess($token);

        if ($context === null) {
            throw new AuthenticationFailed();
        }

        $request->attributes->set('sentai.identity', $context->identity);
        $request->attributes->set('sentai.mobile_access', $context);

        return $next($request);
    }
}
