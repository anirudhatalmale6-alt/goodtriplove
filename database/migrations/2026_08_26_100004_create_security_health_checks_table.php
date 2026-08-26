<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('security_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('service',80)->index();
            $table->string('status',20)->index(); // ok|warning|down
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('checked_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_health_checks');
    }
};
