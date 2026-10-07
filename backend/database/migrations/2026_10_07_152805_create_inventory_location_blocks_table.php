<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operational Location block state owned by Inventory (EVD-ARCH-001, ADR-006).
 *
 * The structural `locations` row stays owned by Master Data and is never updated by
 * Inventory. Blocking is an operational fact persisted here, so that "blocked" can be
 * applied to and released from a Location without mutating Master Data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_location_blocks', function (Blueprint $table): void {
            $table->ulid('location_id')->primary();
            $table->string('reason', 255);
            $table->ulid('blocked_by');
            $table->string('blocked_correlation_id', 128);
            $table->timestamp('blocked_at', 3);
            $table->timestamps(3);
            $table->foreign('location_id')->references('id')->on('locations')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_location_blocks');
    }
};
