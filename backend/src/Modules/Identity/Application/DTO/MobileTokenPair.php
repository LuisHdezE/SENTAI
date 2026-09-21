<?php

namespace Sentai\Modules\Identity\Application\DTO;

use DateTimeImmutable;

final readonly class MobileTokenPair
{
    public function __construct(
        public string $accessToken,
        public DateTimeImmutable $accessExpiresAt,
        public string $refreshToken,
        public DateTimeImmutable $refreshExpiresAt,
    ) {
    }
}
