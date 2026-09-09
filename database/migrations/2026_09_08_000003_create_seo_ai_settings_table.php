<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seo_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->string('provider', 32)->default('openai');
            $table->string('model', 120)->nullable();
            $table->text('api_key')->nullable();
            $table->unsignedTinyInteger('quality_threshold')->default(72);
            $table->boolean('auto_publish')->default(true);
            $table->string('weekly_day', 16)->default('monday');
            $table->string('weekly_time', 5)->default('04:45');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_ai_settings');
    }
};
