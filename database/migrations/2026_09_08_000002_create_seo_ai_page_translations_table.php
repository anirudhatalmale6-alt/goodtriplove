<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seo_ai_page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_ai_page_id')->constrained('seo_ai_pages')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('slug', 191);
            $table->string('title', 180);
            $table->string('meta_description', 320);
            $table->string('h1', 220);
            $table->text('excerpt')->nullable();
            $table->json('content_json');
            $table->json('faq_json')->nullable();
            $table->json('keywords_json')->nullable();
            $table->timestamps();
            $table->unique(['locale', 'slug']);
            $table->unique(['seo_ai_page_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_ai_page_translations');
    }
};
