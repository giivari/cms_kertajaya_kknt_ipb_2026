<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preview_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->foreignUuid('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('session_fingerprint', 64);
            $table->string('preview_type');
            $table->text('encrypted_payload');
            $table->integer('payload_bytes');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at');

            $table->index(['admin_id', 'session_fingerprint']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preview_tokens');
    }
};
