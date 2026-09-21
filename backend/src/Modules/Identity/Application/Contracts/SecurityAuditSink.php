<?php

namespace Sentai\Modules\Identity\Application\Contracts;

use Sentai\Modules\Identity\Application\DTO\SecurityAuditEvent;

interface SecurityAuditSink
{
    public function record(SecurityAuditEvent $event): void;
}
