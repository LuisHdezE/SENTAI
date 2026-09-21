<?php

namespace Sentai\Modules\Identity\Application\Exceptions;

use RuntimeException;

final class AuthorizationDenied extends RuntimeException
{
    public function __construct(public readonly string $capability)
    {
        parent::__construct('Authorization denied.');
    }
}
