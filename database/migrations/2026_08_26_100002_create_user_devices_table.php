<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('device_hash',64)->index();
            $table->string('name')->nullable();
            $table->string('ip_address',45)->nullable();
            $table->string('user_agent',1000)->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id','device_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
