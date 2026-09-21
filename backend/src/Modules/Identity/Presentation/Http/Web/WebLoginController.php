<?php

namespace Sentai\Modules\Identity\Presentation\Http\Web;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Sentai\Modules\Identity\Application\Contracts\CredentialVerifier;
use Sentai\Modules\Identity\Application\Exceptions\AuthenticationFailed;
use Sentai\Modules\Identity\Application\Services\AuthenticationAudit;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class WebLoginController
{
    public function __construct(
        private CredentialVerifier $credentials,
        private AuthenticationAudit $audit,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $payload = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:4096'],
        ]);

        $email = (string) $payload['email'];
        $identity = $this->credentials->verify($email, (string) $payload['password']);
        $correlationId = (string) $request->attributes->get('correlation_id', 'unavailable');

        if ($identity === null) {
            $this->audit->loginFailure($email, 'webLogin', 'web', $correlationId);
            throw new AuthenticationFailed();
        }

        $user = Auth::guard('web')->loginUsingId($identity->id);

        if ($user === false) {
            $this->audit->loginFailure($email, 'webLogin', 'web', $correlationId);
            throw new AuthenticationFailed();
        }

        $request->session()->regenerate();
        $request->session()->put(\App\Http\Middleware\EnforceWebAbsoluteSessionLifetime::AUTHENTICATED_AT, time());

        try {
            $this->audit->loginSuccess($identity, 'webLogin', $identity->webSurface(), $correlationId);
        } catch (Throwable $throwable) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw $throwable;
        }

        return response()->noContent();
    }
}
