<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seo_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('page_type',80)->index();
            $table->string('page_key',255)->index();
            $table->string('locale',8)->index();
            $table->string('title',255)->nullable();
            $table->text('description')->nullable();
            $table->string('canonical_url',500)->nullable();
            $table->boolean('indexable')->default(true)->index();
            $table->json('structured_data')->nullable();
            $table->timestamps();

            $table->unique(['page_type','page_key','locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_overrides');
    }
};
