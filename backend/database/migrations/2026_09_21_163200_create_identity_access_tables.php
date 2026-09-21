<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('email', 254)->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 80)->unique();
            $table->string('name', 160);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 120)->unique();
            $table->string('name', 180);
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->ulid('user_id');
            $table->ulid('role_id');
            $table->primary(['user_id', 'role_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->ulid('role_id');
            $table->ulid('permission_id');
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->ulid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('mobile_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->char('refresh_token_hash', 64);
            $table->timestamp('refresh_expires_at', 3)->index();
            $table->timestamp('revoked_at', 3)->nullable()->index();
            $table->timestamp('last_rotated_at', 3);
            $table->timestamps(3);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('mobile_access_tokens', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('mobile_session_id')->index();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at', 3)->index();
            $table->timestamp('revoked_at', 3)->nullable()->index();
            $table->timestamps(3);
            $table->foreign('mobile_session_id')->references('id')->on('mobile_sessions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_access_tokens');
        Schema::dropIfExists('mobile_sessions');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }
};
