<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('video_imports', function(Blueprint $t){
   $t->id(); $t->string('platform',40)->index(); $t->string('external_id',255)->index();
   $t->string('source_url',1000); $t->string('status',40)->default('FOUND')->index();
   $t->json('source_metadata')->nullable(); $t->json('ai_result')->nullable();
   $t->text('failure_reason')->nullable(); $t->timestamp('last_checked_at')->nullable();
   $t->timestamps(); $t->unique(['platform','external_id']);
  });
  Schema::create('video_import_transitions', function(Blueprint $t){
   $t->id(); $t->foreignId('video_import_id')->index(); $t->string('from_status',40)->nullable();
   $t->string('to_status',40)->index(); $t->foreignId('actor_user_id')->nullable();
   $t->text('note')->nullable(); $t->timestamps();
  });
 }
 public function down(): void {
  Schema::dropIfExists('video_import_transitions'); Schema::dropIfExists('video_imports');
 }
};