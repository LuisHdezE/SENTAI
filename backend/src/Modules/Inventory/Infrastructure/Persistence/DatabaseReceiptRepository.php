<?php

namespace Sentai\Modules\Inventory\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sentai\Modules\Inventory\Application\Contracts\ReceiptRepository;
use Sentai\Modules\Inventory\Domain\Entities\Receipt;
use Sentai\Modules\Inventory\Domain\Entities\ReceiptLine;
use Sentai\Modules\Inventory\Domain\ValueObjects\ReceiptStatus;
use stdClass;

final class DatabaseReceiptRepository implements ReceiptRepository
{
    public function find(string $id): ?Receipt
    {
        return $this->hydrateReceipt(DB::table('receipts')->where('id', $id)->first());
    }

    public function lock(string $id): ?Receipt
    {
        return $this->hydrateReceipt(DB::table('receipts')->where('id', $id)->lockForUpdate()->first());
    }

    public function storeReceipt(
        string $id,
        string $asnId,
        string $warehouseId,
        string $receptionLocationId,
        string $actorId,
        string $correlationId,
        array $lines,
    ): void {
        $now = now();

        DB::table('receipts')->insert([
            'id' => $id,
            'asn_id' => $asnId,
            'warehouse_id' => $warehouseId,
            'reception_location_id' => $receptionLocationId,
            'status' => ReceiptStatus::Received->value,
            'received_by' => $actorId,
            'correlation_id' => $correlationId,
            'received_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $rows = [];

        foreach ($lines as $line) {
            $rows[] = [
                'id' => (string) Str::ulid(),
                'receipt_id' => $id,
                'asn_line_id' => $line['asn_line_id'],
                'product_id' => $line['product_id'],
                'lot_ref' => $line['lot_ref'],
                'serial_ref' => $line['serial_ref'],
                'received_qty' => $this->decimal($line['received_qty']),
                'inventory_item_id' => $line['inventory_item_id'],
                'discrepancy_note' => $line['discrepancy_note'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('receipt_lines')->insert($rows);
        }
    }

    public function linesForReceipt(string $receiptId): array
    {
        return DB::table('receipt_lines')
            ->where('receipt_id', $receiptId)
            ->orderBy('id')
            ->get()
            ->map(fn (stdClass $row): ReceiptLine => $this->hydrate($row))
            ->all();
    }

    public function lockLinesForReceipt(string $receiptId): array
    {
        return DB::table('receipt_lines')
            ->where('receipt_id', $receiptId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->map(fn (stdClass $row): ReceiptLine => $this->hydrate($row))
            ->all();
    }

    public function lockLine(string $receiptLineId): ?ReceiptLine
    {
        $row = DB::table('receipt_lines')->where('id', $receiptLineId)->lockForUpdate()->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function updateReceiptStatus(string $receiptId, ReceiptStatus $status): void
    {
        DB::table('receipts')
            ->where('id', $receiptId)
            ->update(['status' => $status->value, 'updated_at' => now()]);
    }

    public function storePutAway(
        string $receiptId,
        string $receiptLineId,
        string $inventoryItemId,
        string $sourceLocationId,
        string $destinationLocationId,
        string $quantity,
        string $actorId,
        string $correlationId,
    ): string {
        $now = now();
        $taskId = (string) Str::ulid();

        DB::table('putaway_tasks')->insert([
            'id' => $taskId,
            'receipt_id' => $receiptId,
            'receipt_line_id' => $receiptLineId,
            'inventory_item_id' => $inventoryItemId,
            'source_location_id' => $sourceLocationId,
            'destination_location_id' => $destinationLocationId,
            'quantity' => $this->decimal($quantity),
            'actor_id' => $actorId,
            'correlation_id' => $correlationId,
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $taskId;
    }

    private function hydrateReceipt(?stdClass $row): ?Receipt
    {
        if ($row === null) {
            return null;
        }

        return new Receipt(
            (string) $row->id,
            (string) $row->asn_id,
            (string) $row->warehouse_id,
            (string) $row->reception_location_id,
            ReceiptStatus::from((string) $row->status),
        );
    }

    private function hydrate(stdClass $row): ReceiptLine
    {
        return new ReceiptLine(
            (string) $row->id,
            (string) $row->receipt_id,
            $row->asn_line_id === null ? null : (string) $row->asn_line_id,
            (string) $row->product_id,
            $row->lot_ref === null ? null : (string) $row->lot_ref,
            $row->serial_ref === null ? null : (string) $row->serial_ref,
            $this->decimal((string) $row->received_qty),
            (string) $row->inventory_item_id,
            $row->discrepancy_note === null ? null : (string) $row->discrepancy_note,
        );
    }

    private function decimal(string $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
