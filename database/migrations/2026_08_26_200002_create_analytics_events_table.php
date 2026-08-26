<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('session_key',64)->nullable()->index();
            $table->string('event',80)->index();
            $table->string('page_type',80)->nullable()->index();
            $table->string('page_key',255)->nullable()->index();
            $table->string('country_code',8)->nullable()->index();
            $table->string('city',120)->nullable()->index();
            $table->string('device_type',30)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
