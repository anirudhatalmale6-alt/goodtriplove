<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_user_id')->nullable()->index();
            $table->string('target_type',50)->index();
            $table->unsignedBigInteger('target_id')->index();
            $table->string('reason',80)->index();
            $table->text('details')->nullable();
            $table->string('status',30)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reports');
    }
};
