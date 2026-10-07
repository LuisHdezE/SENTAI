<?php

namespace Sentai\Modules\Inventory\Application\Contracts;

use Sentai\Modules\Inventory\Application\DTO\InventoryAuditEvent;

interface InventoryAuditSink
{
    public function record(InventoryAuditEvent $event): void;
}
