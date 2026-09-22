<?php

namespace Sentai\Modules\Audit\Application\Contracts;

use Sentai\Modules\Audit\Application\DTO\AuditAccessContext;
use Sentai\Modules\Audit\Domain\Entities\AuditEvent;

interface AuditEventRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: list<AuditEvent>, page: int, per_page: int, total: int}
     */
    public function list(array $filters): array;

    /** @param  array<string, mixed>  $filters */
    public function recordAccess(array $filters, AuditAccessContext $context): void;
}
