<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->index();
            $table->string('action',100)->index();
            $table->string('auditable_type')->nullable()->index();
            $table->unsignedBigInteger('auditable_id')->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address',45)->nullable()->index();
            $table->string('user_agent',1000)->nullable();
            $table->boolean('success')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_entries');
    }
};
