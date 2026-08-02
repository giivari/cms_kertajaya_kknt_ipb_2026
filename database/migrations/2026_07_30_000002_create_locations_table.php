<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_category_id')->constrained('location_categories')->restrictOnDelete();
            $table->string('name')->index();
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 9, 7);
            $table->decimal('longitude', 10, 7);
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->timestampTz('published_at')->nullable()->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->index();
            $table->softDeletesTz()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
