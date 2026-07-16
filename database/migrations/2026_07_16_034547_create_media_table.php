<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 50)->default('public')->index();
            $table->string('directory', 255);
            $table->string('filename', 255)->unique();
            $table->string('original_filename', 255)->index();
            $table->string('mime_type', 100)->index();
            $table->string('extension', 20)->index();
            $table->bigInteger('size')->default(0);
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->text('caption')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->string('checksum', 128)->nullable()->index();
            $table->timestampTz('uploaded_at')->useCurrent()->index();
            $table->string('processing_status', 30)->default('pending'); // Extension for processing
            $table->string('invisible_watermark_status', 30)->default('pending'); // Extension for watermark
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
