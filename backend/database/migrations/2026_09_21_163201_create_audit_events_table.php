<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('event_type', 120)->index();
            $table->ulid('actor_id')->nullable()->index();
            $table->json('actor_role_snapshot');
            $table->string('aggregate_type', 120)->nullable();
            $table->string('aggregate_id', 64)->nullable();
            $table->string('operation', 120);
            $table->string('outcome', 40)->index();
            $table->string('correlation_id', 128)->index();
            $table->string('source_surface', 40)->index();
            $table->json('context');
            $table->timestamp('occurred_at', 3)->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
