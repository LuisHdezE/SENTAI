<?php

namespace Sentai\Modules\MasterData\Application\Services;

use Sentai\Modules\MasterData\Application\Contracts\MasterDataAuditSink;
use Sentai\Modules\MasterData\Application\Contracts\MasterDataRepository;
use Sentai\Modules\MasterData\Application\DTO\MasterDataAuditEvent;
use Sentai\Modules\MasterData\Application\DTO\MasterMutationContext;
use Sentai\Modules\MasterData\Domain\Entities\MasterDataRecord;
use Sentai\Modules\MasterData\Domain\ValueObjects\MasterType;
use Sentai\Shared\Application\Contracts\IdempotencyGate;
use Sentai\Shared\Application\DTO\IdempotentResponse;

final readonly class MasterDataService
{
    public function __construct(
        private MasterDataRepository $repository,
        private MasterDataAuditSink $audit,
        private IdempotencyGate $idempotency,
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array{data: list<array<string, mixed>>, meta: array{page: int, per_page: int, total: int}}
     */
    public function list(MasterType $type, array $filters): array
    {
        $page = $this->repository->list($type, $filters);

        return [
            'data' => array_map(
                static fn (MasterDataRecord $record): array => $record->toArray(),
                $page['items'],
            ),
            'meta' => [
                'page' => $page['page'],
                'per_page' => $page['per_page'],
                'total' => $page['total'],
            ],
        ];
    }

    /** @param array<string, mixed> $payload */
    public function create(
        MasterType $type,
        string $operationId,
        array $payload,
        MasterMutationContext $context,
    ): IdempotentResponse {
        $requestHash = $this->hashRequest([
            'type' => $type->value,
            'payload' => $payload,
        ]);

        return $this->idempotency->execute(
            $operationId,
            $context->idempotencyKey,
            $requestHash,
            function () use ($type, $operationId, $payload, $context): IdempotentResponse {
                $record = $this->repository->create($type, $payload);
                $this->audit->record(new MasterDataAuditEvent(
                    $type,
                    $record->id,
                    $operationId,
                    $context->actorId,
                    $context->correlationId,
                    array_values(array_keys($payload)),
                ));

                return new IdempotentResponse(['data' => $record->toArray()], 201);
            },
        );
    }

    /** @param array<string, mixed> $payload */
    public function update(
        MasterType $type,
        string $operationId,
        string $id,
        array $payload,
        MasterMutationContext $context,
    ): IdempotentResponse {
        $requestHash = $this->hashRequest([
            'type' => $type->value,
            'id' => $id,
            'payload' => $payload,
        ]);

        return $this->idempotency->execute(
            $operationId,
            $context->idempotencyKey,
            $requestHash,
            function () use ($type, $operationId, $id, $payload, $context): IdempotentResponse {
                $record = $this->repository->update($type, $id, $payload);
                $this->audit->record(new MasterDataAuditEvent(
                    $type,
                    $record->id,
                    $operationId,
                    $context->actorId,
                    $context->correlationId,
                    array_values(array_keys($payload)),
                ));

                return new IdempotentResponse(['data' => $record->toArray()], 200);
            },
        );
    }

    /** @param array<string, mixed> $payload */
    private function hashRequest(array $payload): string
    {
        return hash('sha256', json_encode($this->canonicalize($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
