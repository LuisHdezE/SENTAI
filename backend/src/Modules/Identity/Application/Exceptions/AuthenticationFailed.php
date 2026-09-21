<?php

namespace Sentai\Modules\Identity\Application\Exceptions;

use RuntimeException;

final class AuthenticationFailed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Authentication failed.');
    }
}
