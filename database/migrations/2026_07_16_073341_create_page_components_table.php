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
        Schema::create('page_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('page_sections')->cascadeOnDelete();
            $table->string('component_type', 50)->index();
            $table->integer('column_position')->default(1)->index();
            $table->integer('position')->default(0)->index();
            $table->jsonb('content_data')->default('{}');
            $table->jsonb('component_settings')->default('{}');
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();

            $table->unique(['section_id', 'column_position', 'position'], 'page_components_unique_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_components');
    }
};
