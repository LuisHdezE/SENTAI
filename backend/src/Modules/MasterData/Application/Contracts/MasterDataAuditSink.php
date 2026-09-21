<?php

namespace Sentai\Modules\MasterData\Application\Contracts;

use Sentai\Modules\MasterData\Application\DTO\MasterDataAuditEvent;

interface MasterDataAuditSink
{
    public function record(MasterDataAuditEvent $event): void;
}
