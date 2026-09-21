<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Sentai\Modules\Identity\Application\Contracts\CredentialVerifier;
use Sentai\Modules\Identity\Application\DTO\AuthenticatedIdentity;
use Sentai\Modules\Identity\Application\Exceptions\AuthenticationFailed;
use Sentai\Modules\Identity\Application\Exceptions\AuthorizationDenied;
use Sentai\Modules\Identity\Application\Services\AuthorizationAudit;
use Sentai\Modules\Identity\Application\Services\AuthorizationService;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequireCapability
{
    public function __construct(
        private AuthorizationService $authorization,
        private AuthorizationAudit $audit,
        private CredentialVerifier $credentials,
    ) {}

    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $identity = $request->attributes->get('sentai.identity');

        if (! $identity instanceof AuthenticatedIdentity) {
            $webId = Auth::guard('web')->id();

            if ($webId === null) {
                throw new AuthenticationFailed;
            }

            $identity = $this->credentials->byId((string) $webId);

            if ($identity === null) {
                throw new AuthenticationFailed;
            }
        }

        try {
            $this->authorization->assertCapability($identity->id, $capability);
        } catch (AuthorizationDenied $exception) {
            $this->audit->denied(
                identity: $identity,
                operation: (string) ($request->route()?->getName() ?? $request->method().' '.$request->path()),
                capability: $capability,
                resourceRef: '/'.$request->path(),
                correlationId: (string) $request->attributes->get('correlation_id', 'unavailable'),
                surface: $request->attributes->has('sentai.mobile_access') ? 'mobile' : $identity->webSurface(),
            );

            throw $exception;
        }

        return $next($request);
    }
}
