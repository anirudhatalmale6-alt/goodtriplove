<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 24)->default('youtube');
            $table->string('provider_video_id', 64);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('channel_id', 64)->nullable();
            $table->string('channel_title')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('thumbnail_hq_url')->nullable();
            $table->string('language', 8)->nullable();

            // Embed-only posture: we never download or re-host. If the creator
            // disallows embedding we keep the record but link out instead.
            $table->boolean('embeddable')->default(true);
            $table->boolean('is_available')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->string('unavailable_reason', 64)->nullable();

            // Platform metrics, refreshed by the collector.
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('like_count')->default(0);
            $table->unsignedBigInteger('comment_count')->default(0);
            $table->timestamp('metrics_updated_at')->nullable();
            $table->unsignedBigInteger('previous_view_count')->default(0);
            $table->timestamp('previous_metrics_at')->nullable();

            // Derived rankings — "most viewed / popular / trending / relevant".
            $table->decimal('popularity_score', 8, 4)->default(0);
            $table->decimal('trending_score', 8, 4)->default(0);
            $table->decimal('relevance_score', 8, 4)->default(0);
            $table->decimal('quality_score', 8, 4)->default(0);
            $table->timestamp('scored_at')->nullable();

            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('status', 24)->default('pending');   // pending|approved|rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->json('classification')->nullable();          // raw AI output kept for audit
            $table->string('classified_by', 32)->nullable();     // ollama|heuristic|admin
            $table->decimal('classification_confidence', 5, 4)->nullable();
            $table->timestamp('classified_at')->nullable();

            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 32)->default('collector');  // collector|admin|user
            $table->unsignedBigInteger('gtl_views')->default(0); // views counted on GoodTripLove
            $table->unsignedInteger('favorites_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_tv_eligible')->default(true);
            $table->timestamps();

            $table->unique(['provider', 'provider_video_id']);
            $table->index(['status', 'is_available']);
            $table->index(['country_id', 'city_id', 'category_id', 'status'], 'videos_place_context_index');
            $table->index(['status', 'popularity_score']);
            $table->index(['status', 'trending_score']);
            $table->index(['status', 'view_count']);
        });

        Schema::create('place_video', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->decimal('match_score', 5, 4)->default(0);
            $table->string('match_reason', 64)->nullable();   // title|description|ai|manual
            $table->boolean('is_primary')->default(false);
            $table->boolean('confirmed')->default(false);     // admin-confirmed association
            $table->timestamps();

            $table->unique(['place_id', 'video_id']);
            $table->index(['video_id', 'match_score']);
        });

        Schema::create('video_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_hash', 64);              // hashed session+ip, no raw PII stored
            $table->date('viewed_on');
            $table->timestamps();

            $table->unique(['video_id', 'visitor_hash', 'viewed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_views');
        Schema::dropIfExists('place_video');
        Schema::dropIfExists('videos');
    }
};
