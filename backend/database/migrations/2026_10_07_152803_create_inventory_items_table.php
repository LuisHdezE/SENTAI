<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * InventoryItem aggregate: Product x Location x Lot? x Serial? x State (FR-004).
 *
 * Null-safe uniqueness
 * --------------------
 * MySQL unique indexes treat NULL values as distinct, so a plain
 * `UNIQUE(product_id, location_id, lot_ref, serial_ref, state)` would admit duplicate
 * aggregates whenever the optional Lot or Serial dimension is absent.
 *
 * The effective identity is therefore persisted in the STORED generated column
 * `identity_key`, which collapses both optional dimensions to an empty sentinel with
 * `COALESCE` and joins the five components with a delimiter. The ULID primary keys
 * belong to Crockford base32 and the domain references cannot contain the `|`
 * delimiter, so the encoding is unambiguous. A single unique index over that column
 * then enforces exactly the domain identity, without changing domain semantics and
 * without adding any MySQL-version-specific functional-index syntax.
 *
 * Invariants `OnHand >= 0`, `Reserved >= 0` and `Reserved <= OnHand` (BR-001) are
 * additionally enforced by a database CHECK constraint, because the aggregate can be
 * mutated concurrently by allocation and dispatch operations in later increments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('product_id');
            $table->ulid('location_id');
            $table->string('lot_ref', 64)->nullable();
            $table->string('serial_ref', 64)->nullable();
            $table->string('state', 32);
            $table->decimal('on_hand_qty', 18, 4)->default(0);
            $table->decimal('reserved_qty', 18, 4)->default(0);
            $table->timestamps(3);
            $table->index('location_id', 'ix_inventory_items_location');
            $table->index('product_id', 'ix_inventory_items_product');
            $table->index('state', 'ix_inventory_items_state');
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->restrictOnDelete();
        });

        DB::statement(
            'ALTER TABLE inventory_items ADD COLUMN identity_key VARCHAR(320) '
            ."AS (CONCAT_WS('|', product_id, location_id, COALESCE(lot_ref, ''), COALESCE(serial_ref, ''), state)) STORED"
        );

        DB::statement('ALTER TABLE inventory_items ADD UNIQUE KEY uq_inventory_items_identity (identity_key)');

        DB::statement(
            'ALTER TABLE inventory_items ADD CONSTRAINT ck_inventory_items_quantities '
            .'CHECK (on_hand_qty >= 0 AND reserved_qty >= 0 AND reserved_qty <= on_hand_qty)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
