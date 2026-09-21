<?php

namespace Sentai\Shared\Infrastructure\Idempotency;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sentai\Shared\Application\Contracts\IdempotencyGate;
use Sentai\Shared\Application\DTO\IdempotentResponse;
use Sentai\Shared\Application\Exceptions\DomainConflict;
use Sentai\Shared\Application\Exceptions\IdempotencyConflict;
use stdClass;

final class DatabaseIdempotencyGate implements IdempotencyGate
{
    public function execute(
        string $operationScope,
        string $idempotencyKey,
        string $requestHash,
        callable $operation,
    ): IdempotentResponse {
        return DB::transaction(function () use ($operationScope, $idempotencyKey, $requestHash, $operation): IdempotentResponse {
            $existing = $this->find($operationScope, $idempotencyKey);

            if ($existing !== null) {
                return $this->replay($existing, $requestHash);
            }

            $id = (string) Str::ulid();
            $now = now();

            try {
                DB::table('idempotency_records')->insert([
                    'id' => $id,
                    'operation_scope' => $operationScope,
                    'idempotency_key' => $idempotencyKey,
                    'request_hash' => $requestHash,
                    'response_status' => null,
                    'response_body' => null,
                    'completed_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } catch (QueryException $exception) {
                if (! $this->isDuplicateKey($exception)) {
                    throw $exception;
                }

                $concurrent = $this->find($operationScope, $idempotencyKey);

                if ($concurrent === null) {
                    throw $exception;
                }

                return $this->replay($concurrent, $requestHash);
            }

            $response = $operation();

            DB::table('idempotency_records')
                ->where('id', $id)
                ->update([
                    'response_status' => $response->status,
                    'response_body' => json_encode($response->body, JSON_THROW_ON_ERROR),
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            return $response;
        }, 3);
    }

    private function find(string $operationScope, string $idempotencyKey): ?stdClass
    {
        return DB::table('idempotency_records')
            ->where('operation_scope', $operationScope)
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();
    }

    private function replay(stdClass $record, string $requestHash): IdempotentResponse
    {
        if (! hash_equals((string) $record->request_hash, $requestHash)) {
            throw new IdempotencyConflict('The idempotency key was already used with a different request.');
        }

        if ($record->completed_at === null || $record->response_status === null || $record->response_body === null) {
            throw new DomainConflict('The idempotent operation has no committed result available.');
        }

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $record->response_body, true, 512, JSON_THROW_ON_ERROR);

        return new IdempotentResponse($body, (int) $record->response_status, true);
    }

    private function isDuplicateKey(QueryException $exception): bool
    {
        return (int) ($exception->errorInfo[1] ?? 0) === 1062;
    }
}
