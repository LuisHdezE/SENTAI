<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('name', 255);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps(3);
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('name', 255);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps(3);
        });

        Schema::create('warehouses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('name', 255);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps(3);
        });

        Schema::create('zones', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('warehouse_id')->index();
            $table->string('code', 64);
            $table->string('name', 255);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps(3);
            $table->unique(['warehouse_id', 'code'], 'uq_zones_warehouse_code');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
        });

        Schema::create('locations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('warehouse_id')->index();
            $table->ulid('zone_id')->index();
            $table->string('code', 64);
            $table->string('name', 255);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps(3);
            $table->unique(['warehouse_id', 'code'], 'uq_locations_warehouse_code');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('zone_id')->references('id')->on('zones')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('zones');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('products');
    }
};
