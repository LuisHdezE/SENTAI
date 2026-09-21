<?php

namespace Sentai\Modules\Identity\Application\Contracts;

use Sentai\Modules\Identity\Application\DTO\AuthenticatedIdentity;

interface CredentialVerifier
{
    public function verify(string $email, string $password): ?AuthenticatedIdentity;

    public function byId(string $userId): ?AuthenticatedIdentity;
}
