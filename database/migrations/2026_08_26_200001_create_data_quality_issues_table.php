<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('data_quality_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_type',80)->index();
            $table->string('entity_type',80)->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->string('severity',20)->default('warning')->index();
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->string('status',30)->default('open')->index();
            $table->foreignId('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_quality_issues');
    }
};
