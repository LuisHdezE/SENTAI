<?php

namespace Sentai\Modules\Identity\Application\Services;

use Sentai\Modules\Identity\Application\Contracts\CredentialVerifier;
use Sentai\Modules\Identity\Application\Contracts\MobileTokenStore;
use Sentai\Modules\Identity\Application\DTO\MobileAccessContext;
use Sentai\Modules\Identity\Application\DTO\MobileTokenPair;
use Sentai\Modules\Identity\Application\Exceptions\AuthenticationFailed;
use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;
use Throwable;

final readonly class MobileAuthenticationService
{
    public function __construct(
        private CredentialVerifier $credentials,
        private MobileTokenStore $tokens,
        private AuthenticationAudit $audit,
    ) {}

    public function login(string $email, string $password, string $correlationId): MobileTokenPair
    {
        $identity = $this->credentials->verify($email, $password);

        if ($identity === null || ! $identity->hasRole(RoleCodes::WAREHOUSE_OPERATOR)) {
            $this->audit->loginFailure($email, 'mobileLogin', 'mobile', $correlationId);
            throw new AuthenticationFailed;
        }

        $pair = $this->tokens->issue($identity);

        try {
            $this->audit->loginSuccess($identity, 'mobileLogin', 'mobile', $correlationId);
        } catch (Throwable $throwable) {
            $sessionId = explode('.', $pair->refreshToken, 2)[0];
            $this->tokens->revokeSession($sessionId);

            throw $throwable;
        }

        return $pair;
    }

    public function refresh(string $refreshToken): MobileTokenPair
    {
        $pair = $this->tokens->rotate($refreshToken);

        if ($pair === null) {
            throw new AuthenticationFailed;
        }

        return $pair;
    }

    public function logout(MobileAccessContext $context, string $correlationId): void
    {
        $this->tokens->revokeSession($context->sessionId);
        $this->audit->logout($context->identity, 'mobileLogout', 'mobile', $correlationId, $context->sessionId);
        $this->audit->tokenRevoked($context->identity, $correlationId, $context->tokenId);
    }
}
