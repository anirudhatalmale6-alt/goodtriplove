<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_moderation_queue', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type',80)->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('reason',100)->index();
            $table->string('priority',20)->default('normal')->index();
            $table->string('status',30)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->index();
            $table->foreignId('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_moderation_queue');
    }
};
