<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structural Warehouse -> reception Location mapping (Master Data).
 *
 * The composite foreign key guarantees that the designated Location belongs to
 * the same Warehouse. No duplicated zone_id is stored, so structural hierarchy
 * remains authoritative in locations/zones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->unique(['warehouse_id', 'id'], 'uq_locations_warehouse_id');
        });

        Schema::create('warehouse_reception_locations', function (Blueprint $table): void {
            $table->ulid('warehouse_id')->primary();
            $table->ulid('location_id');
            $table->timestamps(3);

            $table->unique('location_id', 'uq_reception_location');

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->restrictOnDelete();

            $table->foreign(['warehouse_id', 'location_id'], 'fk_reception_location_same_warehouse')
                ->references(['warehouse_id', 'id'])
                ->on('locations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_reception_locations');

        Schema::table('locations', function (Blueprint $table): void {
            $table->dropUnique('uq_locations_warehouse_id');
        });
    }
};
