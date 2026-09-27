<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exports', function (Blueprint $table): void {
            $table->string('lifecycle_state', 24)->default('pending');
            $table->string('requested_format', 8)->nullable();
            $table->unsignedInteger('expected_chunks')->nullable();
            $table->string('artifact_path')->nullable();
            $table->unsignedBigInteger('artifact_size')->nullable();
            $table->char('artifact_sha256', 64)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->string('cleanup_error', 255)->nullable();
        });

        Schema::create('export_chunks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('export_id')->constrained('exports')->cascadeOnDelete();
            $table->unsignedInteger('page');
            $table->unsignedInteger('processed_rows');
            $table->unsignedInteger('successful_rows');
            $table->char('sha256', 64);
            $table->unique(['export_id', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_chunks');
        Schema::table('exports', function (Blueprint $table): void {
            $table->dropColumn([
                'lifecycle_state', 'requested_format', 'expected_chunks', 'artifact_path',
                'artifact_size', 'artifact_sha256', 'verified_at', 'failure_reason', 'cleanup_error',
            ]);
        });
    }
};
