<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analytics_rollups', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('metric',80)->index();
            $table->string('dimension',255)->nullable()->index();
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();

            $table->unique(['date','metric','dimension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_rollups');
    }
};
