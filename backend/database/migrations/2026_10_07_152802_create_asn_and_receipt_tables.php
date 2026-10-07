<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inbound receipt process: ASN aggregate, expected lines, receipts and receipt lines.
 *
 * ASN authoring/approval is explicitly deferred outside the receipt contract; these
 * tables only make a valid ASN referenceable and a receipt durable (API-INV-001).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asns', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('reference', 64);
            $table->ulid('warehouse_id');
            $table->string('status', 32)->default('approved');
            $table->timestamps(3);
            $table->unique('reference', 'uq_asns_reference');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
        });

        Schema::create('asn_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('asn_id');
            $table->ulid('product_id');
            $table->string('lot_ref', 64)->nullable();
            $table->string('serial_ref', 64)->nullable();
            $table->decimal('expected_qty', 18, 4);
            $table->decimal('received_qty', 18, 4)->default(0);
            $table->timestamps(3);
            $table->foreign('asn_id')->references('id')->on('asns')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::create('receipts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('asn_id');
            $table->ulid('warehouse_id');
            $table->ulid('reception_location_id');
            $table->string('status', 32)->default('received');
            $table->ulid('received_by');
            $table->string('correlation_id', 128);
            $table->timestamp('received_at', 3);
            $table->timestamps(3);
            $table->foreign('asn_id')->references('id')->on('asns')->restrictOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('reception_location_id')->references('id')->on('locations')->restrictOnDelete();
        });

        Schema::create('receipt_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('receipt_id');
            $table->ulid('asn_line_id')->nullable();
            $table->ulid('product_id');
            $table->string('lot_ref', 64)->nullable();
            $table->string('serial_ref', 64)->nullable();
            $table->decimal('received_qty', 18, 4);
            $table->ulid('inventory_item_id');
            $table->string('discrepancy_note', 255)->nullable();
            $table->timestamps(3);
            $table->foreign('receipt_id')->references('id')->on('receipts')->cascadeOnDelete();
            $table->foreign('asn_line_id')->references('id')->on('asn_lines')->restrictOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::create('putaway_tasks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('receipt_id');
            $table->ulid('receipt_line_id');
            $table->ulid('inventory_item_id');
            $table->ulid('source_location_id');
            $table->ulid('destination_location_id');
            $table->decimal('quantity', 18, 4);
            $table->ulid('actor_id');
            $table->string('correlation_id', 128);
            $table->timestamp('completed_at', 3);
            $table->timestamps(3);
            $table->foreign('receipt_id')->references('id')->on('receipts')->restrictOnDelete();
            $table->foreign('receipt_line_id')->references('id')->on('receipt_lines')->restrictOnDelete();
            $table->foreign('source_location_id')->references('id')->on('locations')->restrictOnDelete();
            $table->foreign('destination_location_id')->references('id')->on('locations')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('putaway_tasks');
        Schema::dropIfExists('receipt_lines');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('asn_lines');
        Schema::dropIfExists('asns');
    }
};
