<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unified Inventory adjustment ledger.
 *
 * Manual adjustments, state changes and physical moves share one header/line model.
 * A MOVE is represented by source and destination line effects under one adjustment
 * record; no separate inventory_movements table exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipt_lines', function (Blueprint $table): void {
            $table->foreign('inventory_item_id', 'fk_receipt_lines_inventory_item')
                ->references('id')
                ->on('inventory_items')
                ->restrictOnDelete();
        });

        Schema::table('putaway_tasks', function (Blueprint $table): void {
            $table->foreign('inventory_item_id', 'fk_putaway_tasks_inventory_item')
                ->references('id')
                ->on('inventory_items')
                ->restrictOnDelete();
        });

        Schema::create('inventory_adjustments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('kind', 40);
            $table->string('reason', 255);
            $table->ulid('actor_id');
            $table->string('correlation_id', 128);
            $table->timestamp('occurred_at', 3);
            $table->timestamps(3);
            $table->index(['kind', 'occurred_at'], 'ix_inventory_adjustments_kind_occurred');
        });

        Schema::create('inventory_adjustment_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('adjustment_id');
            $table->ulid('inventory_item_id');
            $table->ulid('source_location_id')->nullable();
            $table->ulid('destination_location_id')->nullable();
            $table->decimal('quantity_delta', 18, 4);
            $table->decimal('on_hand_before', 18, 4)->nullable();
            $table->decimal('on_hand_after', 18, 4)->nullable();
            $table->string('state_before', 32)->nullable();
            $table->string('state_after', 32)->nullable();
            $table->timestamps(3);

            $table->index('adjustment_id', 'ix_inventory_adjustment_lines_adjustment');
            $table->index('inventory_item_id', 'ix_inventory_adjustment_lines_item');

            $table->foreign('adjustment_id')
                ->references('id')
                ->on('inventory_adjustments')
                ->restrictOnDelete();
            $table->foreign('inventory_item_id')
                ->references('id')
                ->on('inventory_items')
                ->restrictOnDelete();
            $table->foreign('source_location_id')
                ->references('id')
                ->on('locations')
                ->restrictOnDelete();
            $table->foreign('destination_location_id')
                ->references('id')
                ->on('locations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustment_lines');
        Schema::dropIfExists('inventory_adjustments');

        Schema::table('putaway_tasks', function (Blueprint $table): void {
            $table->dropForeign('fk_putaway_tasks_inventory_item');
        });

        Schema::table('receipt_lines', function (Blueprint $table): void {
            $table->dropForeign('fk_receipt_lines_inventory_item');
        });
    }
};
