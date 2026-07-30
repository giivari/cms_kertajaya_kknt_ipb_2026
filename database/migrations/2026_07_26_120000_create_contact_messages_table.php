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
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('contact_type');
            $table->string('contact_value', 150);
            $table->string('subject', 150);
            $table->text('message');
            $table->string('status')->default('new')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();
            
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};