<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seo_ai_pages', function (Blueprint $table) {
            $table->id();
            $table->string('topic_key', 191)->index();
            $table->string('topic_type', 64);
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedTinyInteger('quality_score')->default(0);
            $table->boolean('indexable')->default(false);
            $table->json('quality_details')->nullable();
            $table->json('generation_context')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_ai_pages');
    }
};
