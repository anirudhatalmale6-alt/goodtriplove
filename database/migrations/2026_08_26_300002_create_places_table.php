<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('slug');
            $table->string('name');
            $table->json('description')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('website')->nullable();
            $table->json('social_links')->nullable();
            $table->unsignedTinyInteger('price_level')->nullable();   // 1..4
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();
            $table->json('opening_hours')->nullable();

            // Admin approval before publication (V1 requirement).
            $table->string('status', 24)->default('pending');          // pending|published|rejected|draft
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->unsignedBigInteger('gtl_views')->default(0);
            $table->unsignedInteger('videos_count')->default(0);
            $table->unsignedInteger('favorites_count')->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->string('source', 32)->default('admin');            // admin|business|collector
            $table->timestamps();

            $table->unique(['city_id', 'slug']);
            $table->index(['status', 'published_at']);
            $table->index(['country_id', 'category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
