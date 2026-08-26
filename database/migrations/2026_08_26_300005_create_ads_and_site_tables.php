<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Simple Ads Manager: advertising spaces between categories,
        // temporary promotional banners, scrolling announcement text.
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 24)->default('banner');       // banner|promo|sponsor
            $table->string('placement', 48)->default('home_between_categories');
            $table->json('title')->nullable();
            $table->json('subtitle')->nullable();
            $table->json('cta_label')->nullable();
            $table->string('image')->nullable();
            $table->string('mobile_image')->nullable();
            $table->string('target_url')->nullable();
            $table->string('background_color', 32)->nullable();
            $table->string('text_color', 32)->nullable();
            $table->json('locales')->nullable();                 // null = every language
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'placement', 'sort_order']);
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->json('text');                                 // per-locale ticker text
            $table->string('url')->nullable();
            $table->string('emoji', 16)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('group', 48)->default('general');
            $table->timestamps();
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('favoritable_type');
            $table->unsignedBigInteger('favoritable_id');
            $table->timestamps();

            $table->unique(['user_id', 'favoritable_type', 'favoritable_id'], 'favorites_unique');
            $table->index(['favoritable_type', 'favoritable_id']);
        });

        // Official Android build published from goodtriplove.com (SHA-256 shown
        // on the download banner so visitors can verify the file).
        Schema::create('app_releases', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 16)->default('android');
            $table->string('version', 32);
            $table->unsignedInteger('version_code')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->string('store_url')->nullable();
            $table->json('release_notes')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedBigInteger('downloads')->default(0);
            $table->timestamps();

            $table->index(['platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('ads');
    }
};
