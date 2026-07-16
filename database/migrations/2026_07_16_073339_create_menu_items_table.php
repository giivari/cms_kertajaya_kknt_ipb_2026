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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('label', 150);
            $table->string('link_type', 50)->index();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->text('custom_url')->nullable();
            $table->string('target', 20)->default('_self');
            $table->integer('position')->default(0)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['menu_id', 'parent_id', 'position'], 'menu_items_unique_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
