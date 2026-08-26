<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('feature_flags', function(Blueprint $t){
   $t->id(); $t->string('key',120)->unique(); $t->boolean('enabled')->default(false)->index();
   $t->json('config')->nullable(); $t->foreignId('updated_by')->nullable(); $t->timestamps();
  });
  Schema::create('technical_error_events', function(Blueprint $t){
   $t->id(); $t->string('source',80)->index(); $t->string('severity',20)->index();
   $t->string('fingerprint',64)->nullable()->index(); $t->text('message');
   $t->json('context')->nullable(); $t->unsignedInteger('occurrences')->default(1);
   $t->timestamp('last_seen_at')->index(); $t->string('status',30)->default('open')->index(); $t->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('technical_error_events'); Schema::dropIfExists('feature_flags'); }
};