<?php

namespace Sentai\Modules\Identity\Application\Contracts;

use Sentai\Modules\Identity\Application\DTO\AuthenticatedIdentity;
use Sentai\Modules\Identity\Application\DTO\MobileAccessContext;
use Sentai\Modules\Identity\Application\DTO\MobileTokenPair;

interface MobileTokenStore
{
    public function issue(AuthenticatedIdentity $identity): MobileTokenPair;

    public function rotate(string $refreshToken): ?MobileTokenPair;

    public function authenticateAccess(string $accessToken): ?MobileAccessContext;

    public function revokeSession(string $sessionId): void;
}
