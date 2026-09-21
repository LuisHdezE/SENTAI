<?php

namespace Sentai\Modules\Identity\Application\DTO;

final readonly class MobileAccessContext
{
    public function __construct(
        public AuthenticatedIdentity $identity,
        public string $sessionId,
        public string $tokenId,
    ) {}
}
