<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('operation_scope', 120);
            $table->string('idempotency_key', 255);
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('completed_at', 3)->nullable()->index();
            $table->timestamps(3);
            $table->unique(['operation_scope', 'idempotency_key'], 'uq_idempotency_scope_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_records');
    }
};
