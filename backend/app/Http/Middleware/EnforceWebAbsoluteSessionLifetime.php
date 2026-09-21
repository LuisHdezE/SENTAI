<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Sentai\Modules\Identity\Application\Contracts\CredentialVerifier;
use Sentai\Modules\Identity\Application\Exceptions\AuthenticationFailed;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnforceWebAbsoluteSessionLifetime
{
    public const AUTHENTICATED_AT = 'sentai.authenticated_at';

    public function __construct(private CredentialVerifier $credentials) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            return $next($request);
        }

        $userId = Auth::guard('web')->id();
        $identity = $userId === null ? null : $this->credentials->byId((string) $userId);
        $authenticatedAt = (int) $request->session()->get(self::AUTHENTICATED_AT, 0);
        $absoluteSeconds = max(1, (int) config('sentai.security.web_absolute_lifetime_minutes', 480)) * 60;

        if ($identity === null || $authenticatedAt <= 0 || (time() - $authenticatedAt) >= $absoluteSeconds) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw new AuthenticationFailed;
        }

        return $next($request);
    }
}
