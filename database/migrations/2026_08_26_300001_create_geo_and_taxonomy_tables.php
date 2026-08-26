<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();          // ISO 3166-1 alpha-2
            $table->string('slug')->unique();
            $table->json('name');                          // {"fr":"Portugal","en":"Portugal",...}
            $table->json('description')->nullable();
            $table->string('flag_emoji', 16)->nullable();
            $table->string('cover_image')->nullable();
            $table->string('default_locale', 5)->default('en');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('population')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->timestamps();

            $table->unique(['country_id', 'slug']);
            $table->index(['is_active', 'is_popular']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon', 64)->nullable();        // inline svg key
            $table->string('accent_color', 16)->nullable();
            $table->string('cover_image')->nullable();
            $table->json('search_terms')->nullable();       // per-locale keywords fed to the collector
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_home')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
