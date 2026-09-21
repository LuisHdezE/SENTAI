<?php

namespace Sentai\Modules\Identity\Presentation\Http\Mobile;

use Illuminate\Http\Request;
use Sentai\Modules\Identity\Application\DTO\MobileAccessContext;
use Sentai\Modules\Identity\Application\Exceptions\AuthenticationFailed;
use Sentai\Modules\Identity\Application\Services\MobileAuthenticationService;
use Symfony\Component\HttpFoundation\Response;

final readonly class MobileLogoutController
{
    public function __construct(private MobileAuthenticationService $authentication) {}

    public function __invoke(Request $request): Response
    {
        $context = $request->attributes->get('sentai.mobile_access');

        if (! $context instanceof MobileAccessContext) {
            throw new AuthenticationFailed;
        }

        $this->authentication->logout(
            $context,
            (string) $request->attributes->get('correlation_id', 'unavailable'),
        );

        return response()->noContent();
    }
}
