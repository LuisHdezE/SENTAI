<?php

namespace Sentai\Modules\Identity\Presentation\Http\Web;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Sentai\Modules\Identity\Application\Contracts\CredentialVerifier;
use Sentai\Modules\Identity\Application\Exceptions\AuthenticationFailed;
use Sentai\Modules\Identity\Application\Services\AuthenticationAudit;
use Symfony\Component\HttpFoundation\Response;

final readonly class WebLogoutController
{
    public function __construct(
        private CredentialVerifier $credentials,
        private AuthenticationAudit $audit,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $userId = Auth::guard('web')->id();
        $identity = $userId === null ? null : $this->credentials->byId((string) $userId);

        if ($identity === null) {
            throw new AuthenticationFailed();
        }

        $sessionRef = hash('sha256', $request->session()->getId());
        $correlationId = (string) $request->attributes->get('correlation_id', 'unavailable');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->audit->logout($identity, 'webLogout', $identity->webSurface(), $correlationId, $sessionRef);

        return response()->noContent();
    }
}
