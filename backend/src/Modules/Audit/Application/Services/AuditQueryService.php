<?php

namespace Sentai\Modules\Audit\Application\Services;

use Sentai\Modules\Audit\Application\Contracts\AuditEventRepository;
use Sentai\Modules\Audit\Application\DTO\AuditAccessContext;
use Sentai\Modules\Audit\Domain\Entities\AuditEvent;

final readonly class AuditQueryService
{
    public function __construct(
        private AuditEventRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, meta: array{page: int, per_page: int, total: int}}
     */
    public function list(array $filters, AuditAccessContext $context): array
    {
        $page = $this->repository->list($filters);
        $this->repository->recordAccess($filters, $context);

        return [
            'data' => array_map(
                static fn (AuditEvent $event): array => $event->toArray(),
                $page['items'],
            ),
            'meta' => [
                'page' => $page['page'],
                'per_page' => $page['per_page'],
                'total' => $page['total'],
            ],
        ];
    }
}
