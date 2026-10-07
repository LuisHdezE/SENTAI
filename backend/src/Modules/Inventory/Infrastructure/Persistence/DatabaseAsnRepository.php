<?php

namespace Sentai\Modules\Inventory\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Sentai\Modules\Inventory\Application\Contracts\AsnRepository;
use Sentai\Modules\Inventory\Domain\Entities\Asn;
use Sentai\Modules\Inventory\Domain\Entities\AsnLine;
use Sentai\Modules\Inventory\Domain\ValueObjects\AsnStatus;
use stdClass;

final class DatabaseAsnRepository implements AsnRepository
{
    public function find(string $id): ?Asn
    {
        return $this->hydrateAsn(DB::table('asns')->where('id', $id)->first());
    }

    public function lock(string $id): ?Asn
    {
        return $this->hydrateAsn(DB::table('asns')->where('id', $id)->lockForUpdate()->first());
    }

    public function lines(string $asnId): array
    {
        return DB::table('asn_lines')
            ->where('asn_id', $asnId)
            ->orderBy('id')
            ->get()
            ->map(fn (stdClass $row): AsnLine => new AsnLine(
                (string) $row->id,
                (string) $row->asn_id,
                (string) $row->product_id,
                $this->decimal((string) $row->expected_qty),
                $row->lot_ref === null ? null : (string) $row->lot_ref,
                $row->serial_ref === null ? null : (string) $row->serial_ref,
            ))
            ->all();
    }

    public function incrementReceivedQty(string $asnLineId, string $delta): void
    {
        DB::update(
            'UPDATE asn_lines SET received_qty = received_qty + ?, updated_at = ? WHERE id = ?',
            [$this->decimal($delta), now(), $asnLineId],
        );
    }

    public function updateStatus(string $asnId, string $status): void
    {
        DB::table('asns')->where('id', $asnId)->update(['status' => $status, 'updated_at' => now()]);
    }

    public function isFullyReceived(string $asnId): bool
    {
        return DB::table('asn_lines')
            ->where('asn_id', $asnId)
            ->whereColumn('received_qty', '<', 'expected_qty')
            ->doesntExist();
    }

    private function hydrateAsn(?stdClass $row): ?Asn
    {
        if ($row === null) {
            return null;
        }

        return new Asn(
            (string) $row->id,
            (string) $row->warehouse_id,
            AsnStatus::from((string) $row->status),
        );
    }

    private function decimal(string $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
