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
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150)->nullable();
            $table->string('layout_type', 50)->default('single_column')->index();
            $table->integer('position')->default(0)->index();
            $table->jsonb('section_settings')->default('{}');
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();

            $table->unique(['page_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
