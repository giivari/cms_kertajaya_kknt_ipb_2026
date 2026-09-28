<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            // Availability stays on the existing status/derivative record.
            // A failed replacement must not hide a previously approved file.
            $table->uuid('processing_token')->nullable();
            $table->string('last_processing_status', 30)->nullable();
            $table->string('last_processing_error', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropColumn(['processing_token', 'last_processing_status', 'last_processing_error']);
        });
    }
};
